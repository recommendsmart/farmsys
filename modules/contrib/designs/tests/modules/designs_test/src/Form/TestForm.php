<?php

namespace Drupal\designs_test\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a test form for testing the plugin form.
 */
class TestForm extends FormBase {

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * The design setting manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingManager;

  /**
   * The design content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->designManager = $container->get('plugin.manager.design');
    $instance->settingManager = $container->get('plugin.manager.design_setting');
    $instance->contentManager = $container->get('plugin.manager.design_content');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'designs_test_form';
  }

  /**
   * Get the parents from string.
   *
   * @param string $parents
   *   The parents as form string.
   *
   * @return string[]
   *   The parents array.
   */
  protected function getParents($parents) {
    $parents = explode('[', $parents);
    return array_map(function ($s) {
      return rtrim($s, ']');
    }, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parents = NULL, $array_parents = NULL, $design_source = NULL) {
    $form['#test_parents'] = $this->getParents($parents);
    $form['#test_array_parents'] = $this->getParents($array_parents);

    $plugin_form = new PluginForm(
      $this->designManager,
      $this->settingManager,
      $this->contentManager,
      '',
      [],
      $design_source,
      []
    );

    $element = [
      '#open' => TRUE,
      '#parents' => $form['#test_parents'],
      '#array_parents' => $form['#test_array_parents'],
    ];

    $element = $plugin_form->buildForm($element, $form_state);
    NestedArray::setValue($form, $form['#test_array_parents'], $element);

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Submit',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $element = NestedArray::getValue($form, $form['#test_array_parents']);

    /** @var \Drupal\designs\Form\PluginForm $form */
    $plugin_form = $element['#form_handler'];
    $plugin_form->validateForm($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = NestedArray::getValue($form, $form['#test_array_parents']);

    /** @var \Drupal\designs\Form\PluginForm $form */
    $plugin_form = $element['#form_handler'];
    $plugin_form->submitForm($element, $form_state);

    $values = $form_state->getValue($form['#test_parents']);
    \Drupal::messenger()->addMessage(json_encode($values));
  }

}
