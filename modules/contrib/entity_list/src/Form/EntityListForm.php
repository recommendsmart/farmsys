<?php

namespace Drupal\entity_list\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_list\Entity\EntityListInterface;
use Drupal\entity_list\Plugin\EntityListDisplayManager;
use Drupal\entity_list\Plugin\EntityListExtraDisplayManager;
use Drupal\entity_list\Plugin\EntityListQueryManager;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListForm.
 */
class EntityListForm extends EntityForm {

  const HIDDEN_PLUGIN_SELECTION = [
    'filters_entity_list_extra_display',
    'sortable_filters_entity_list_extra_display'
  ];

  protected $bundleInfo;

  protected $entityListQueryManager;

  protected $entityListDisplayManager;

  protected $languageManager;

  protected $entityListExtraDisplayManager;

  /**
   * EntityListForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   * @param \Drupal\entity_list\Plugin\EntityListQueryManager $entity_list_query_manager
   *   The entity list query manager.
   * @param \Drupal\entity_list\Plugin\EntityListDisplayManager $entity_list_display_manager
   *   The entity list display manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\entity_list\Plugin\EntityListExtraDisplayManager $entity_list_extra_display_manager
   *   The entity list extra display manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, EntityListQueryManager $entity_list_query_manager, EntityListDisplayManager $entity_list_display_manager, LanguageManagerInterface $language_manager, EntityListExtraDisplayManager $entity_list_extra_display_manager) {
    $this->bundleInfo = $bundle_info;
    $this->entityListQueryManager = $entity_list_query_manager;
    $this->entityListDisplayManager = $entity_list_display_manager;
    $this->languageManager = $language_manager;
    $this->entityListExtraDisplayManager = $entity_list_extra_display_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Override default create method to inject the cup of tea command service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.entity_list_query'),
      $container->get('plugin.manager.entity_list_display'),
      $container->get('language_manager'),
      $container->get('plugin.manager.entity_list_extra_display')
    );
  }

  /**
   * Return an array representing the tabs and fieldset for convenience.
   *
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   An entity list object.
   *
   * @return array
   *   An array representing the tabs and fieldset.
   */
  protected function getFormTabs(EntityListInterface $entity_list) {
    return [
      'query_details' => [
        '#title' => $this->t('Source'),
        '#description' => $this->t('Select one or more content types to fill the list'),
        'query' => [
          '#title' => $this->t('Query'),
          '#prefix' => '<div id="query-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListQueryManager,
          '#get_selected_plugin' => [
            ['query', 'plugin'],
            $entity_list->get('query')['plugin'] ?? 'filter_entity_list_query',
          ],
          '#get_settings' => [
            ['query'],
            $entity_list->get('query') ?? [],
          ],
          '#ajax_update' => [
            'query-wrapper' => ['query_details', 'query'],
          ],
        ],
      ],
      'display_details' => [
        '#title' => $this->t('Display'),
        '#description' => $this->t('Manage display settings.'),
        'display' => [
          '#title' => $this->t('Display'),
          '#prefix' => '<div id="display-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListDisplayManager,
          '#get_selected_plugin' => [
            ['display', 'plugin'],
            $entity_list->get('display')['plugin'] ?? 'filter_entity_list_display',
          ],
          '#get_settings' => [
            ['display'],
            $entity_list->get('display') ?? [],
          ],
          '#ajax_update' => [
            'display-wrapper' => ['display_details', 'display'],
          ],
        ],
      ],
      'filters_details' => [
        '#title' => $this->t('Filters'),
        '#description' => $this->t('Manage filters settings.'),
        'filter' => [
          '#title' => $this->t('Filters'),
          '#prefix' => '<div id="filters-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListExtraDisplayManager,
          '#get_selected_plugin' => [
            ['filter', 'plugin'],
            'filters_entity_list_extra_display',
          ],
          '#get_settings' => [
            ['filter'],
            $entity_list->get('filter') ?? [],
          ],
          '#ajax_update' => [
            'filters-wrapper' => ['filters_details', 'filter'],
          ],
        ],
      ],
      'sortable_filters_details' => [
        '#title' => $this->t('Sortable Filters'),
        '#description' => $this->t('Manage sortable filters settings.'),
        'sortable_filter' => [
          '#title' => $this->t('Sortable Filters'),
          '#prefix' => '<div id="sortable-filters-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListExtraDisplayManager,
          '#get_selected_plugin' => [
            ['sortableFilter', 'plugin'],
            'sortable_filters_entity_list_extra_display',
          ],
          '#get_settings' => [
            ['sortable_filter'],
            $entity_list->get('sortableFilter') ?? [],
          ],
          '#ajax_update' => [
            'sortable-filters-wrapper' => ['sortable_filters_details', 'sortable_filter'],
          ],
        ],
      ],
    ];
  }

  /**
   * Build the tab plugin.
   *
   * @param array $tab
   *   The tab info from $this->getFormTabs().
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   The current entity list object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the vertical tab.
   */
  protected function buildTab(array $tab, EntityListInterface $entity_list, FormStateInterface $form_state) {
    foreach ($tab as $key => &$item) {
      if (strpos($key, '#') !== 0) {
        $item = $this->buildFieldset($item, $entity_list, $form_state);
      }
    }
    $element = [
      '#type' => 'details',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => FALSE,
      '#group' => 'vertical_tabs',
    ];
    return $element + $tab;
  }

  /**
   * Build the fieldset inside the vertical tab.
   *
   * @param array $fieldset
   *   The fieldset info from $this->getFormTabs().
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   The current entity list object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the fieldset inside a vertical tab.
   */
  protected function buildFieldset(array $fieldset, EntityListInterface $entity_list, FormStateInterface $form_state) {
    $element = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];
    $element += $fieldset;

    /** @var \Drupal\Core\Plugin\DefaultPluginManager $manager */
    $manager = $fieldset['#manager'] ?? NULL;
    if (!empty($manager)) {
      $plugin_options = [];
      foreach ($manager->getDefinitions() as $key => $plugin) {
        $plugin_options[$key] = $plugin['label'];
      }

      $selected_plugin = call_user_func_array(
        [$form_state, 'getValue'],
        $fieldset['#get_selected_plugin']
      );

      $element['plugin'] = [
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#options' => $plugin_options,
        '#required' => TRUE,
        '#default_value' => $selected_plugin,
        '#ajax' => [
          'callback' => [get_class($this), 'update'],
        ],
        '#ajax_update' => $fieldset['#ajax_update'] ?? [],
      ];
      if (count($plugin_options) < 2 || in_array($selected_plugin, self::HIDDEN_PLUGIN_SELECTION)) {
        $element['plugin']['#type'] = 'hidden';
      }

      if (!empty($selected_plugin) && $manager->hasDefinition($selected_plugin)) {
        try {
          $instance = $manager->createInstance($selected_plugin, [
            'entity' => $entity_list,
            'settings' => call_user_func_array(
              [$form_state, 'getValue'],
              $fieldset['#get_settings']
            ),
          ]);
        }
        catch (PluginException $e) {
          $this->messenger()->addError($e->getMessage());
        }
        if (!empty($instance)) {
          $element += $instance->settingsForm($form_state);
        }
      }

      unset($element['#manager']);
      unset($element['#get_selected_plugin']);
      unset($element['#get_settings']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_list\Entity\EntityList $entity_list */
    $entity_list = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_list->label(),
      '#description' => $this->t("Label for the Entity list."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_list->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_list\Entity\EntityList::load',
      ],
      '#disabled' => !$entity_list->isNew(),
    ];

    $form['debug'] = [
      '#type' => 'container',
      '#prefix' => '<div id="debug">',
      '#suffix' => '</div>',
    ];

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-query-details',
    ];

    $step = $this->entity->get('step');
    $tabs = $this->getFormTabs($entity_list);

    if ($step !== 'finish') {
      $step = $step ?? 0;

      $form['step'] = [
        '#type' => 'hidden',
        '#value' => $step,
      ];

      if ($step == 0) {
        unset($tabs['display_details']);
        unset($tabs['query_details']);
        unset($tabs['filters_details']);
        unset($tabs['sortable_filters_details']);
      }

      if ($step == 1) {
        unset($tabs['display_details']);
        unset($tabs['filters_details']);
        unset($tabs['sortable_filters_details']);
      }
    }

    foreach ($tabs as $key => $tab) {
      $form[$key] = $this->buildTab($tab, $entity_list, $form_state);
    }

    return $form;
  }

  /**
   * Ajax callback to update a form elements.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function update(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $element = $form_state->getTriggeringElement();
    $ajax_update = self::getAjaxUpdate($element, $form);
    foreach ($ajax_update as $id => $path) {
      if (!empty($form[$path[0]])) {
        $response->addCommand(new ReplaceCommand("#$id", self::getFormElementFromPath($path, $form)));
      }
    }
    $response->addCommand(new AppendCommand('#debug', ['#type' => 'status_messages']));
    return $response;
  }

  /**
   * Try to find a property #ajax_update on the triggering element.
   *
   * If the property was not found in the triggering element, try to find it
   * recursively in the parent elements.
   *
   * @param array $element
   *   The triggering element or a parent.
   * @param array $form
   *   The complete form.
   *
   * @return array
   *   The #ajax_update array or an empty array.
   */
  public static function getAjaxUpdate(array $element, array $form) {
    if (!empty($element['#ajax_update'])) {
      return $element['#ajax_update'];
    }
    $parents = array_slice($element['#array_parents'] ?? [], 0, -1);
    if (!empty($parents)) {
      return self::getAjaxUpdate(self::getFormElementFromPath($parents, $form), $form);
    }
    return [];
  }

  /**
   * Helper method to get form element from a path.
   *
   * @param array $path
   *   The path to the form element.
   * @param array $form
   *   An array of the current form element according to the current path
   *   element.
   *
   * @return array
   *   A form element.
   */
  public static function getFormElementFromPath(array $path, array $form) {
    if (!empty($path)) {
      $key = array_shift($path);
      return self::getFormElementFromPath($path, $form[$key]);
    }
    return $form;
  }

  /**
   * Ajax callback to update the display settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajaxUpdateDisplayPlugin(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#display-wrapper', $form['display_details']['display']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_list = $this->entity;

    if (!empty($entity_list->sortable_filter)) {
      $entity_list->sortableFilter = $this->entity->sortable_filter;
    }

    $status = $entity_list->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Entity list.', [
          '%label' => $entity_list->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Entity list.', [
          '%label' => $entity_list->label(),
        ]));
    }

    $step = $form_state->getValue('step');

    if ($step < 2 && !is_null($step) && $step !== 'finish') {
      $step++;
      $entity_list->setStep($step);
      $entity_list->save();
      $form_state->setRedirectUrl(Url::fromRoute('entity.entity_list.edit_form', ['entity_list' => $entity_list->id()]));
    }
    else {
      $entity_list->setStep('finish');
      $entity_list->save();
      $form_state->setRedirectUrl($entity_list->toUrl('collection'));
    }
  }

}
