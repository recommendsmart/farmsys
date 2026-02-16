<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Exception\FlowEnqueueException;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\EntityRepositoryTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\FallbackSubjectTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\TokenTrait;
use Drupal\flow\Plugin\FlowSubjectBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subject for loaded content.
 *
 * @FlowSubject(
 *   id = "load",
 *   label = @Translation("Loaded content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\LoadDeriver"
 * )
 */
class Load extends FlowSubjectBase implements PluginFormInterface {

  use EntityFromStackTrait;
  use EntityTypeManagerTrait;
  use EntityRepositoryTrait;
  use FallbackSubjectTrait;
  use ModuleHandlerTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * The threshold for the maximum amount of subject items to return.
   *
   * @var int
   */
  protected int $listSizeThreshold;

  /**
   * The current offset when working on a large list.
   *
   * @var int
   */
  protected int $listOffset = 0;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Subject\Load $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->initEntityFromStack();
    $instance->setListSizeThreshold($container->getParameter('flow.load_list_threshold'));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setToken($container->get(self::$tokenServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    $items = [];
    $load_mode = $this->settings['mode'] ?? NULL;
    $definition = $this->getPluginDefinition();
    $entity_type_id = $definition['entity_type'];
    $bundle = $definition['bundle'];

    switch ($load_mode) {

      case 'id':
        $entity_id = isset($this->settings['entity_id']) ? $this->tokenReplace($this->settings['entity_id'], $this->entityFromStack) : '';
        if ($entity_id !== '') {
          if ($item = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id)) {
            $items[] = $item;
          }
          elseif ($this->getEntityFromStack() && ((string) $this->getEntityFromStack()->id() === $entity_id) && ($this->getEntityFromStack()->getEntityTypeId() === $entity_type_id)) {
            $items[] = $this->getEntityFromStack();
          }
          else {
            foreach (Flow::$stack as &$entities) {
              foreach ($entities as $entity) {
                if (((string) $entity->id() === $entity_id) && ($entity->getEntityTypeId() === $entity_type_id)) {
                  $items[] = $entity;
                  break 2;
                }
              }
            }
          }
        }
        break;

      case 'uuid':
        $uuid = isset($this->settings['entity_uuid']) ? $this->tokenReplace($this->settings['entity_uuid'], $this->entityFromStack) : '';
        if ($uuid !== '' && Uuid::isValid($uuid)) {
          if ($item = $this->getEntityRepository()->loadEntityByUuid($entity_type_id, $uuid)) {
            $items[] = $item;
          }
          elseif ($this->getEntityFromStack() && $this->getEntityFromStack()->uuid() === $uuid) {
            $items[] = $this->getEntityFromStack();
          }
          else {
            foreach (Flow::$stack as &$entities) {
              foreach ($entities as $entity) {
                if ($entity->uuid() === $uuid) {
                  $items[] = $entity;
                  break 2;
                }
              }
            }
          }
        }
        break;

      case 'view':
        $view_id = $this->settings['view']['id'] ?? NULL;
        $display_id = $this->settings['view']['display'] ?? NULL;
        if (isset($view_id, $display_id) && $view = Views::getView($view_id)) {
          $arguments = isset($this->settings['view']['arguments']) ? $this->tokenReplace($this->settings['view']['arguments'], $this->entityFromStack) : '';
          if ($arguments !== '') {
            $view->setArguments(explode('|', $arguments));
          }
          if ($view->setDisplay($display_id)) {
            $items = $this->getEntitiesFromView($view);
          }
        }
        break;

    }

    $is_empty = TRUE;
    foreach ($items as $item) {
      if (($item instanceof ContentEntityInterface) && ($item->getEntityTypeId() === $entity_type_id) && ($item->bundle() === $bundle)) {
        $is_empty = FALSE;
        yield $item;
      }
    }

    if (!$is_empty || $this->listOffset) {
      // Subject items are not empty, so no need to call for a fallback.
      return;
    }

    foreach ($this->getFallbackItems() as $item) {
      if (($item instanceof ContentEntityInterface) && ($item->getEntityTypeId() === $entity_type_id) && ($item->bundle() === $bundle)) {
        $is_empty = FALSE;
        yield $item;
      }
    }

    if ($is_empty) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $plugin_definition = $this->getPluginDefinition();
    $weight = 10;

    $load_options = [
      '_none' => $this->t('- Select -'),
      'id' => $this->t('Entity ID'),
      'uuid' => $this->t('Entity UUID'),
    ];
    if ($this->moduleHandler->moduleExists('views')) {
      $load_options['view'] = $this->t('View');
    }

    $load_mode = $this->settings['mode'] ?? '_none';
    if (!isset($load_options[$load_mode])) {
      $load_mode = '_none';
    }

    $wrapper_id = Html::getUniqueId('flow-load-subject');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['#subject'] = $this;
    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Load by'),
      '#options' => $load_options,
      '#default_value' => $load_mode,
      '#required' => TRUE,
      '#empty_value' => '_none',
      '#ajax' => [
        'callback' => [$this, 'selectAjax'],
        'wrapper' => $wrapper_id,
      ],
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'submitFormAjax']],
      '#weight' => $weight++,
    ];

    if ($load_mode !== '_none') {
      $form['token_info'] = [
        '#type' => 'container',
        'allowed_text' => [
          '#markup' => $this->t('Tokens are allowed.') . '&nbsp;',
          '#weight' => 10,
        ],
        '#weight' => $weight++,
      ];
      if (isset($this->configuration['entity_type_id']) && $this->moduleHandler->moduleExists('token')) {
        $form['token_info']['browser'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => [$this->getTokenTypeForEntityType($this->configuration['entity_type_id'])],
          '#dialog' => TRUE,
          '#weight' => 20,
        ];
      }
      else {
        $form['token_info']['no_browser'] = [
          '#markup' => $this->t('To get a list of available tokens, install the <a target="_blank" rel="noreferrer noopener" href=":drupal-token" target="blank">contrib Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']),
          '#weight' => 20,
        ];
      }
    }

    switch ($load_mode) {

      case 'id':
        $form['entity_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Entity ID'),
          '#default_value' => $this->settings['entity_id'] ?? NULL,
          '#required' => TRUE,
          '#weight' => $weight++,
        ];
        break;

      case 'uuid':
        $form['entity_uuid'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Entity UUID'),
          '#default_value' => $this->settings['entity_uuid'] ?? NULL,
          '#required' => TRUE,
          '#weight' => $weight++,
        ];
        break;

      case 'view':
        $entity_type_id = $plugin_definition['entity_type'];
        $view_wrapper_id = Html::getUniqueId('flow-load-subject-view');
        $selected_view_id = $this->settings['view']['id'] ?? '_none';
        $form['view'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Views configuration'),
          '#attributes' => ['id' => $view_wrapper_id],
          '#weight' => $weight++,
        ];
        $view_id_options = [
          '_none' => $this->t('- Select -'),
        ] + array_filter(Views::getViewsAsOptions(TRUE, 'enabled'), function ($view_id) use ($entity_type_id) {
          $entity_type = Views::getView($view_id)->getBaseEntityType();
          return $entity_type && $entity_type->id() == $entity_type_id;
        }, ARRAY_FILTER_USE_KEY);
        $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';
        $form['view']['id'] = [
          '#type' => $select_widget,
          '#title' => $this->t('View'),
          '#options' => $view_id_options,
          '#default_value' => $selected_view_id,
          '#required' => TRUE,
          '#empty_value' => '_none',
          '#ajax' => [
            'callback' => [$this, 'selectAjax'],
            'wrapper' => $view_wrapper_id,
          ],
          '#executes_submit_callback' => TRUE,
          '#limit_validation_errors' => [],
          '#submit' => [[$this, 'submitFormAjax']],
          '#weight' => 10,
        ];
        if ($selected_view_id !== '_none') {
          $selected_display_id = $this->settings['view']['display'] ?? '_none';
          $display_id_options = [
            '_none' => $this->t('- Select -'),
          ];
          foreach (Views::getViewsAsOptions(FALSE, 'enabled') as $view_id => $view_option_label) {
            if (strpos($view_id, $selected_view_id . ':') === 0) {
              $display_id = substr($view_id, strlen($selected_view_id) + 1);
              $display_id_options[$display_id] = $view_option_label;
            }
          }
          array_filter(Views::getViewsAsOptions(FALSE, 'enabled'), function ($view_id) use ($selected_view_id) {
            return strpos($view_id, $selected_view_id . ':') === 0;
          }, ARRAY_FILTER_USE_KEY);
          $form['view']['display'] = [
            '#type' => 'select',
            '#title' => $this->t('Display'),
            '#options' => $display_id_options,
            '#default_value' => $selected_display_id,
            '#required' => TRUE,
            '#empty_value' => '_none',
            '#ajax' => [
              'callback' => [$this, 'selectAjax'],
              'wrapper' => $view_wrapper_id,
            ],
            '#executes_submit_callback' => TRUE,
            '#limit_validation_errors' => [],
            '#submit' => [[$this, 'submitFormAjax']],
            '#weight' => 20,
          ];
          $defined_arguments = $user_input['subject']['settings']['view']['arguments'] ?? ($this->settings['view']['arguments'] ?? '');
          $form['view']['arguments'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Arguments'),
            '#description' => $this->t('Arguments to be passed to the view. Separate multiple arguments with "|". Example: "1|2|[node:author:account-name]".'),
            '#default_value' => $defined_arguments,
            '#weight' => 30,
          ];
          $view_config = Views::getView($selected_view_id)->storage;
          if ($selected_display_id !== '_none' && $view_config->access('update')) {
            $view = Views::getView($selected_view_id);
            $form['view']['link'] = [
              '#type' => 'link',
              '#title' => $this->t('Edit view'),
              '#url' => $view->storage->toUrl('edit-display-form')->setRouteParameter('display_id', $selected_display_id),
              '#attributes' => ['target' => '_blank'],
              '#weight' => 40,
            ];
          }
        }
        break;

    }

    $this->buildFallbackForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->validateFallbackForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->settings['mode'] = $form_state->getValue('mode');
    $this->settings['entity_id'] = $form_state->getValue('entity_id');
    $this->settings['entity_uuid'] = $form_state->getValue('entity_uuid');
    $this->settings['view']['id'] = $form_state->getValue(['view', 'id']);
    $this->settings['view']['display'] = $form_state->getValue(
      ['view', 'display']);
    $this->settings['view']['arguments'] = $form_state->getValue(
      ['view', 'arguments']);
    $this->submitFallbackForm($form, $form_state);
  }

  /**
   * Ajax submit callback.
   *
   * @param array &$form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state): void {
    $button = $form_state->getTriggeringElement();
    $button_parents = $button['#array_parents'];
    while ($element = &NestedArray::getValue($form, $button_parents)) {
      foreach (Element::children($element) as $child) {
        if (isset($element[$child]['#value'])) {
          $value = $element[$child]['#value'] === '_none' ? NULL : $element[$child]['#value'];
          $form_state->setValueForElement($element[$child], $value);
        }
      }
      if (isset($element['#subject']) && $element['#subject'] === $this) {
        break;
      }
      array_pop($button_parents);
    }
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $this->submitConfigurationForm($element, $subform_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback when changing a form input using a select widget.
   *
   * @param array &$form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public function selectAjax(array &$form, FormStateInterface $form_state): array {
    $button = $form_state->getTriggeringElement();
    $element = &NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Get the threshold for the maximum amount of subject items to return.
   *
   * @return int
   *   The threshold.
   */
  public function getListSizeThreshold(): int {
    return $this->listSizeThreshold;
  }

  /**
   * Set the threshold for the maximum amount of subject items to return.
   *
   * @param int $list_size_threshold
   *   The threshold.
   */
  public function setListSizeThreshold(int $list_size_threshold) {
    $this->listSizeThreshold = $list_size_threshold;
  }

  /**
   * Get entities from the given view.
   *
   * @param \Drupal\views\ViewExecutable|mixed $view
   *   The view to get the entities from.
   *
   * @return iterable
   *   An iterable list of entities.
   *
   * @todo This needs to be optimized in the following ways:
   *  - Wrap the query by an entity query that filters by the bundle beforehand.
   *  - Return a wrapper object that allows for calling a ::count().
   */
  protected function getEntitiesFromView($view): iterable {
    /** @var \Drupal\views\ViewExecutable $view */
    if (!$view->executed) {
      $view->setOffset($this->listOffset);
      $view->getQuery()->setOffset($this->listOffset);
      $view->getQuery()->setLimit($this->listSizeThreshold + 1);
      $view->execute();
    }

    $i = 0;
    foreach ($view->result as $row) {
      $i++;
      if ($i > $this->listSizeThreshold) {
        $this->listOffset += $this->listSizeThreshold;
        throw new FlowEnqueueException($this);
      }

      $entity = isset($row->_entity) ? $row->_entity : NULL;
      if (!$entity) {
        continue;
      }

      if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
        // Try to find a field alias for the langcode. Assumption: translatable
        // entities always have a langcode key.
        $language_field = '';
        $langcode_key = $entity->getEntityType()->getKey('langcode');
        $base_table = $view->storage->get('base_table');
        foreach ($view->query->fields as $field) {
          if (
            $field['field'] === $langcode_key && (
              empty($field['base_table']) ||
              $field['base_table'] === $base_table
            )
          ) {
            $language_field = $field['alias'];
            break;
          }
        }
        if (!$language_field) {
          $language_field = $langcode_key;
        }

        if (isset($row->{$language_field})) {
          $entity = $entity->getTranslation($row->{$language_field});
        }
      }

      yield $entity;
    }
  }

    /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if (!empty($this->settings['view']['id']) && \Drupal::moduleHandler()->moduleExists('views')) {
      $etm = \Drupal::entityTypeManager();
      if ($view_config = $etm->getStorage('view')->load($this->settings['view']['id'])) {
        $dependencies[$view_config->getConfigDependencyKey()][] = $view_config->getConfigDependencyName();
        $dependencies['module'][] = 'views';
      }
    }
    return $dependencies;
  }

}
