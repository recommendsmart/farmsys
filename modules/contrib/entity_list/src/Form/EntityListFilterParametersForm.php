<?php

namespace Drupal\entity_list\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_list\Controller\EntityListFilterController;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterManager;
use Drupal\entity_list\Plugin\EntityListSortableFilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListFilterParametersForm.
 */
class EntityListFilterParametersForm extends FormBase {

  /**
   * This is entity list filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListFilterManager
   */
  public $entityListFilterManager;

  /**
   * This is entity list sortable filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListSortableFilterManager
   */
  public $entityListSortableManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_list_filter_parameters_form';
  }

  /**
   * EntityListFilterParameterForm constructor.
   *
   * @param \Drupal\entity_list\Plugin\EntityListFilterManager $entity_list_filter_manager
   *   This is entity list filter manager.
   * @param \Drupal\entity_list\Plugin\EntityListSortableFilterManager $entity_list_sortable_filter_manager
   *   This is entity list filter manager.
   */
  public function __construct(EntityListFilterManager $entity_list_filter_manager, EntityListSortableFilterManager $entity_list_sortable_filter_manager) {
    $this->entityListFilterManager = $entity_list_filter_manager;
    $this->entityListSortableManager = $entity_list_sortable_filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_list_filter'),
      $container->get('plugin.manager.entity_list_sortable_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $type = null, string $entity_list_id = null, string $filter_id = null, string $plugin_id = null, $bundle_element = null) {
    if ($plugin_id && $entity_list_id) {
      $entity_list = EntityList::load($entity_list_id);

      if ($type === EntityListFilterController::SORTABLE_FILTER_TYPE) {
        $filter = $this->entityListSortableManager->createInstance($plugin_id);
      } else if ($type === EntityListFilterController::FILTER_TYPE) {
        $filter = $this->entityListFilterManager->createInstance($plugin_id);
      }

      $parameters_form = $filter->configurationFilter([], $entity_list);
      $parameters_form['#type'] = 'fieldset';

      $form = array_merge(['settings' => $parameters_form], $form);

      $form['type'] = [
        '#type' => 'hidden',
        '#value' => $type,
      ];

      $form['filter_id'] = [
        '#type' => 'hidden',
        '#value' => $plugin_id === 'custom_list_entity_list_filter' ? $filter_id . '_' . uniqid() : $filter_id,
      ];

      $form['entity_list_id'] = [
        '#type' => 'hidden',
        '#value' => $entity_list_id,
      ];

      $form['plugin_id'] = [
        '#type' => 'hidden',
        '#value' => $plugin_id,
      ];

      $form['bundle_element'] = [
        '#type' => 'hidden',
        '#value' => $bundle_element,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var EntityList $entity_list */
    $entity_list_id = $form_state->getValue('entity_list_id');
    $type = $form_state->getValue('type');
    $entity_list = EntityList::load($entity_list_id);
    $plugin_id = $form_state->getValue('plugin_id');
    $filter_id = $form_state->getValue('filter_id');
    $bundle_element = $form_state->getValue('bundle_element');
    $settings = $form_state->getValues(['settings']);

    if ($entity_list) {
      if ($type === EntityListFilterController::SORTABLE_FILTER_TYPE) {
        $form_name = 'sortable_filters_exposed';
        $filters = $entity_list->get('sortableFilter');
        $settings_type = 'sortableFilter';
        $settings['settings']['type'] = $settings_type;
        $definitions = $this->entityListSortableManager->getDefinitions();

      }
      else if ($type === EntityListFilterController::FILTER_TYPE) {
        $form_name = 'filters_exposed';
        $filters = $entity_list->get('filter');
        $settings_type = 'filter';
        $settings['settings']['type'] = $settings_type;
        $definitions = $this->entityListFilterManager->getDefinitions();
      }

      foreach ($definitions as $key => $plugins) {
        if ($key === $plugin_id && !empty($form_name) && !empty($settings_type)) {
          $filters[$form_name]['layout_items'] = is_null($filters) || is_string($filters[$form_name]['layout_items'])
            ? []
            : $filters[$form_name]['layout_items'];
          $filters[$form_name]['layout_items'][$filter_id] = [
            'region' => 'filters',
            'weight' => 0,
            'settings' => array_merge([
              'id' => $filter_id,
              'class' => $plugins['class'],
              'type' => $settings_type,
              'plugin' => $key,
              'bundles' => isset($bundle_element['bundles']) ? json_encode($bundle_element['bundles']) : '',
              'field_reference' => isset($bundle_element['vocabulary']) && is_string($bundle_element['vocabulary']) ? $bundle_element['vocabulary'] : '',
              'field_name' => isset($bundle_element['key']) && is_string($bundle_element['key']) ? $bundle_element['key'] : '',
            ], $settings['settings']),
          ];
        }
      }

      $entity_list->set($settings_type, $filters);
      $entity_list->save();
    }

    $url = Url::fromRoute('entity.entity_list.edit_form', ['entity_list' => $entity_list_id], ['absolute' => TRUE]);
    $form_state->setRedirectUrl($url);
  }

}
