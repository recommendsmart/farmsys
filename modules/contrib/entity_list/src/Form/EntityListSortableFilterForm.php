<?php

namespace Drupal\entity_list\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterBase;
use Drupal\entity_list\Plugin\EntityListSortableFilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListSortableFilterForm.
 */
class EntityListSortableFilterForm extends EntityListFilterFormBase {

  /**
   * This is entity list sortable filter manager;
   *
   * @var \Drupal\entity_list\Plugin\EntityListSortableFilterManager
   */
  protected $entityListSortableFilterManager;

  /**
   * EntityListFilterForm constructor.
   *
   * @param \Drupal\entity_list\Plugin\EntityListSortableFilterManager $entity_list_sortable_filter_manager
   *   The entity list filter manager.
   *
   */
  public function __construct(EntityListSortableFilterManager $entity_list_sortable_filter_manager) {
    $this->entityListSortableFilterManager = $entity_list_sortable_filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_list_sortable_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_list_sortable_filter_form';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityList $entity_list = null, $filters = NULL, $parameters = []) {
    if (!empty($filters)) {
      $form['#theme'] = 'entity_list_sortable_filters';

      foreach ($filters as $filter) {
        /** @var EntityListFilterBase $instance */
        if (isset($parameters[$filter]['settings']['plugin'])) {
          $instance = $this->entityListSortableFilterManager->createInstance($parameters[$filter]['settings']['plugin']);
          if (!empty($instance->buildFilter($parameters[$filter], $entity_list))) {
            $form = array_merge($form, $instance->buildFilter($parameters[$filter], $entity_list));
          }
        }
      }

      if ($entity_list->sortableFilter['settings']['enabled_js']) {
        $form['#attached']['library'][] = 'entity_list/sortable-filters';
      } else {
        $form['actions'] = [
          '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Sort'),
        ];

        $form['actions']['reset'] = [
          '#markup' => '<a class="reset-link" href="' . Url::fromRoute('<current>')->toString() . '">' . $this->t('Reset') . '</a>'
        ];
      }

      return $form;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
