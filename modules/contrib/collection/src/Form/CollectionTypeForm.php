<?php

namespace Drupal\collection\Form;

use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Class CollectionTypeForm.
 */
class CollectionTypeForm extends EntityForm {

  /**
   * The entity type bundle info manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $setEntityTypeBundleInfoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->setEntityTypeBundleInfoManager = $container->get('entity_type.bundle.info');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $collection_type = $this->entity;
    $collection_item_type_storage = $this->entityTypeManager->getStorage('collection_item_type');
    $bundle_info = $this->setEntityTypeBundleInfoManager->getAllBundleInfo();
    $allowed_collection_item_types = [];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $collection_type->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $collection_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\collection\Entity\CollectionType::load',
      ],
      '#disabled' => !$collection_type->isNew(),
    ];

    foreach ($collection_item_type_storage->loadMultiple() as $collection_item_type) {
      $allowed_collection_item_types[$collection_item_type->id()] = $collection_item_type->label();
      $allowed_content = [];

      // Store the allowed bundles per entity type in a nested array.
      foreach ($collection_item_type->getAllowedBundles() as $entity_and_bundle) {
        list($entity_type_id, $bundle) = explode('.', $entity_and_bundle);
        $entity_type_label = (string) $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
        $allowed_content[$entity_type_label][] = $bundle_info[$entity_type_id][$bundle]['label'];
      }

      // Flatten the allowed bundles per entity type.
      $allowed_content = array_map(function($v) {
        return implode(', ', array_filter($v));
      }, $allowed_content);

      // Prepend the entity type label to the flattened bundles.
      array_walk($allowed_content, function(&$v, $k) {
        $v = '• <em>' . $k . ':</em> ' . $v;
      });

      // Flatten the list of entity types and allowed bundles into an array
      // keyed by the collection item type. This will be used as descriptions
      // for each checkbox.
      $collection_item_allowed_content[$collection_item_type->id()] = implode('<br /> ', $allowed_content);
    }

    $form['allowed_collection_item_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed collection item types'),
      '#options' => $allowed_collection_item_types,
      '#default_value' => $collection_type->getAllowedCollectionItemTypes(),
      '#required' => TRUE,
    ];

    // Add descriptions for each option here. See
    // https://www.drupal.org/project/drupal/issues/2779999
    foreach (array_keys($allowed_collection_item_types) as $allowed_collection_item_type) {
      $form['allowed_collection_item_types'][$allowed_collection_item_type]['#description'] = $collection_item_allowed_content[$allowed_collection_item_type];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\collection\Entity\CollectionTypeInterface $entity */
    $values = $form_state->getValues();
    $entity->set('label', $values['label']);
    $entity->set('id', $values['id']);
    $entity->set('allowed_collection_item_types', array_keys(array_filter($values['allowed_collection_item_types'])));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $collection_type = $this->entity;
    $status = $collection_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Collection type.', [
          '%label' => $collection_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Collection type.', [
          '%label' => $collection_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($collection_type->toUrl('collection'));
  }

}
