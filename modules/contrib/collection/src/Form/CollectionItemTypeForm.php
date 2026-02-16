<?php

namespace Drupal\collection\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CollectionItemTypeForm.
 */
class CollectionItemTypeForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the entity_type.manager service.
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $collection_item_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $collection_item_type->label(),
      '#description' => $this->t("Label for the Collection item type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $collection_item_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\collection\Entity\CollectionItemType::load',
      ],
      '#disabled' => !$collection_item_type->isNew(),
    ];

    $form['allowed_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => 'Allowed content entity bundles',
      '#options' => $this->getBundleOptions(),
      '#default_value' => $collection_item_type->get('allowed_bundles'),
    ];

    $form['attributes_access'] = [
      '#type' => 'radios',
      '#title' => 'Attributes access',
      '#options' => [
        'none' => $this->t('None'),
        'owners' => $this->t('Collection owners only'),
        'administrators' => $this->t('Collections administrators only'),
      ],
      '#default_value' => $collection_item_type->isNew() ? 'administrators' : $collection_item_type->get('attributes_access'),
      '#description' => $this->t('Restrict access to the key/value attributes field for this collection item type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\collection\Entity\CollectionItemTypeInterface $entity */
    $values = $form_state->getValues();
    $entity->set('label', $values['label']);
    $entity->set('id', $values['id']);
    $entity->set('allowed_bundles', array_keys(array_filter($values['allowed_bundles'])));
    $entity->set('attributes_access', $values['attributes_access']);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $collection_item_type = $this->entity;
    $status = $collection_item_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Collection item type.', [
          '%label' => $collection_item_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Collection item type.', [
          '%label' => $collection_item_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($collection_item_type->toUrl('collection'));
  }

  /**
   * Get all content entity type names.
   *
   * @return array
   */
  protected function getBundleOptions() {
    $options = [];
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();

    foreach ($entity_type_definitions as $definition) {
      if ($definition instanceof ContentEntityType) {
        $entity_type = $definition->id();

        // Prevent collection items as an option.
        if ($entity_type === 'collection_item') {
          continue;
        }

        foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type) as $bundle_key => $bundle_info) {
          $options[$entity_type . '.' . $bundle_key] = $definition->getLabel() . ': ' . (string) $bundle_info['label'];
        }
      }
    }

    return $options;
  }
}
