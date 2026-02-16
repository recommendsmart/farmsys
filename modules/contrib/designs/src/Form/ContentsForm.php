<?php

namespace Drupal\designs\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides the content form handler.
 */
class ContentsForm extends FormBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderVisibility'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Custom Content');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugins(): array {
    return $this->contentManager->getSourceDefinitions(
      'content',
      $this->design->getSourcePlugin()->getPluginId(),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginManager(): PluginManagerInterface {
    return $this->contentManager;
  }

  /**
   * Build a content form for each of the sources with content plugins.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form render array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $wrapper = $form['#contents_wrapper'];

    // Get the plugins for the content.
    $options = [];
    foreach ($this->getPlugins() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    $design = $this->getDesign();
    foreach ($design->getContents() as $content_id => $plugin) {
      $parents = array_merge($form['#parents'], [$content_id]);

      $form[$content_id] = self::getChildElement($parents, $form);
      $form_handler = new ContentForm(
        $this->manager,
        $this->settingManager,
        $this->contentManager
      );
      $form[$content_id] = $form_handler
        ->setDesign($this->design)
        ->setContent($plugin)
        ->buildForm($form[$content_id], $form_state);

      $form[$content_id] += [
        'remove' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#op' => 'remove_content',
          '#index' => $content_id,
          '#name' => static::getElementId($form['#parents'], "-{$content_id}-remove"),
          '#submit' => static::getSubmit($form),
          '#ajax' => $wrapper + static::getAjax($form),
          '#design_parents' => $form['#design_parents'],
        ],
      ];
    }

    $addition_name = self::getElementId($form['#parents'], '-addition');
    $form['__addition__'] = [
      '#type' => 'details',
      '#title' => $this->t('Add content'),
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#size' => 30,
        '#parents' => ["{$addition_name}-label"],
      ],
      'machine' => [
        '#type' => 'machine_name',
        '#machine_name' => [
          'exists' => [static::class, 'checkCustomExists'],
          'source' => array_merge($form['#parents'], ['__addition__', 'label']),
        ],
        '#required' => FALSE,
        '#size' => 30,
        '#parents' => ["{$addition_name}-machine"],
        '#content_parents' => $form['#parents'],
      ],
      'submit' => [
        '#type' => 'submit',
        '#op' => 'add_content',
        '#value' => $this->t('Create'),
        '#submit' => static::getSubmit($form),
        '#name' => "{$addition_name}-create",
        '#ajax' => $wrapper + static::getAjax($form),
        '#design_parents' => $form['#design_parents'],
        '#validate' => [[static::class, 'validateCreationForm']],
      ],
    ];

    return $form;
  }

  /**
   * Massage the values for the content form as appropriate.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function massageFormValues(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    unset($values['__addition__']);

    // Process each source.
    foreach (Element::children($form) as $source) {
      unset($values[$source]['submit']);
      unset($values[$source]['remove']);
    }

    $form_state->setValue($form['#parents'], $values ?: []);

    // Get the input values.
    $completed = $form_state->getUserInput();
    $addition_name = self::getElementId($form['#parents'], '-addition');
    unset($completed["{$addition_name}-label"]);
    unset($completed["{$addition_name}-machine"]);
    $form_state->setUserInput($completed);
  }

  /**
   * Validation for ::buildForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    foreach (Element::children($form) as $child) {
      if (isset($form[$child]['#form_handler'])) {
        $form[$child]['#form_handler']->validateForm($form[$child], $form_state);
      }
    }
  }

  /**
   * Submission for ::buildForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   *
   * @return array
   *   The form values.
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $result = $form_state->getValue($form['#parents']);
    foreach (Element::children($form) as $child) {
      if (isset($form[$child]['#form_handler'])) {
        $result[$child] = $form[$child]['#form_handler']->submitForm($form[$child], $form_state);
      }
    }
    $form_state->setValue($form['#parents'], $result);
    return $result ?? [];
  }

  /**
   * Process each of the element values for visibility rules.
   *
   * @param array $element
   *   The base element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderVisibility(array $element) {
    $type_input = 'select[name="' . $element['plugin']['#name'] . '"]';
    foreach (Element::children($element['value']) as $child) {
      $element['value'][$child]['#states']['enabled'] = [
        $type_input => ['value' => $child],
      ];
    }
    return $element;
  }

  /**
   * Validate the creation of custom content.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateCreationForm(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $parents = array_slice($trigger_element['#array_parents'], 0, -1);

    // Validate the label.
    $element = NestedArray::getValue($form, array_merge($parents, ['label']));
    $value = $form_state->getValue($element['#parents']);
    if (!$value) {
      $form_state->setError($element, t('Label can not be empty.'));
    }

    // Validate the machine name.
    $element = NestedArray::getValue($form, array_merge($parents, ['machine']));
    $value = $form_state->getValue($element['#parents']);
    if (!$value) {
      $form_state->setError($element, t('Machine name can not be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    switch ($trigger_element['#op']) {
      case 'add_content':
      case 'remove_content':
        $wrapper = $trigger_element['#ajax'];
        if (isset($wrapper['parents'])) {
          $target = $wrapper['parents'];
        }
        else {
          $target = array_slice($trigger_element['#array_parents'], 0, -3);
        }
        return NestedArray::getValue($form, $target);
    }

    return parent::multistepAjax($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    switch ($trigger_element['#op']) {
      case 'add_content':
        $parents = array_slice($trigger_element['#parents'], 0, -2);

        // Get the addition values.
        $addition_name = self::getElementId($parents, '-addition');
        $label = $form_state->getValue(["{$addition_name}-label"]);
        $machine = $form_state->getValue(["{$addition_name}-machine"]);

        // Update the values with the addition.
        $values = $form_state->getValue($parents);
        if (!isset($values[$machine]) && $machine) {
          $values[$machine] = [
            'plugin' => 'text',
            'config' => [
              'label' => $label,
            ],
          ];
          $form_state->setValue($parents, $values);
        }

        // Rebuild the form.
        $form_state->setRebuild();
        break;

      case 'remove_content':
        // Parents for remove content are a little deeper in the tree.
        $parents = array_slice($trigger_element['#parents'], 0, -2);

        // Get the region from the trigger element.
        $index = $trigger_element['#index'];

        // Get the values and additions.
        $values = $form_state->getValue($parents);
        unset($values[$index]);
        $form_state->setValue($parents, $values);

        // Rebuild the form.
        $form_state->setRebuild();
        break;

      default:
        parent::multistepSubmit($form, $form_state);
    }
  }

  /**
   * Check for duplication of custom key values.
   *
   * @param string $value
   *   The value for the element.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   The result.
   */
  public static function checkCustomExists($value, array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#content_parents']);
    return !empty($values[$value]);
  }

}
