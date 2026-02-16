<?php

namespace Drupal\entity_list\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterBase;
use Drupal\entity_list\Plugin\EntityListFilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListFilterForm.
 */
class EntityListFilterForm extends EntityListFilterFormBase {

  /**
   * This is entity list filter manager;
   *
   * @var \Drupal\entity_list\Plugin\EntityListFilterManager
   */
  protected $entityListFilterManager;

  /**
   * EntityListFilterForm constructor.
   *
   * @param \Drupal\entity_list\Plugin\EntityListFilterManager $entity_list_filter_manager
   *   The entity list filter manager.
   *
   */
  public function __construct(EntityListFilterManager $entity_list_filter_manager) {
    $this->entityListFilterManager = $entity_list_filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_list_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_list_filter_form';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityList $entity_list = null, $filters = NULL, $parameters = []) {
    if (!empty($filters)) {
      $form['#attributes']['class'] = explode(' ', $entity_list->filter['settings']['class']) ?? [];
      $form['#attributes']['data-entity-list-id'] = $entity_list->id();

      $form['fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t($entity_list->filter['settings']['title'] ?? 'Filter'),
      ];

      $filters_rendered = [];
      foreach ($filters as $filter) {
        /** @var EntityListFilterBase $instance */
        if (isset($parameters[$filter]['settings']['plugin'])) {
          $instance = $this->entityListFilterManager->createInstance($parameters[$filter]['settings']['plugin']);
          if (!empty($instance->buildFilter($parameters[$filter], $entity_list))) {
            $filters_rendered = array_merge($filters_rendered, $instance->buildFilter($parameters[$filter], $entity_list));
          }
        }
      }

      $form['fieldset'] = array_merge($form['fieldset'], $filters_rendered);

      $form['fieldset']['actions'] = [
        '#type' => 'actions',
      ];

      $form['fieldset']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t($entity_list->filter['settings']['submit_title'] ?? 'Submit'),
      ];

      $form['fieldset']['actions']['reset'] = [
        '#markup' => '<a class="form-reset" href="' . Url::fromRoute('<current>')->toString() . '">' . $this->t('Reset') . '</a>'
      ];

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
