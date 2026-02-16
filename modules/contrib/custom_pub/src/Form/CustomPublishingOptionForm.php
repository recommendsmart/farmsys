<?php

namespace Drupal\custom_pub\Form;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomPublishingOptionForm.
 *
 * @package Drupal\custom_pub\Form
 */
class CustomPublishingOptionForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CustomPublishingOptionForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entity_definition_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityDefinitionManager = $entity_definition_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_definition_manager = $container->get('entity.definition_update_manager');
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_definition_manager, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("The label for this publishing option."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\custom_pub\Entity\CustomPublishingOption::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['publish_under_promote_options'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place under "Promote options"'),
      '#size' => 10,
      '#maxlength' => 255,
      '#default_value' => $this->entity->isPublishUnderPromoteOptions(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t("Add a description for this publishing option."),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // check to see if a field definition exists by this machine name to prevent collisions/overwrites
    $entity = $this->entity;
    $storage_definition = $this->entityDefinitionManager->getFieldStorageDefinition($entity->id(), 'node');

    if ($entity->isNew() && isset($storage_definition)) {
      $form_state->setError($form['id'], $this->t('Cannot use machine name %name - field definition already exists.', ['%name' => $entity->id()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $label = $entity->label();
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label custom publishing option.', [
          '%label' => $label,
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label custom publishing option.', [
          '%label' => $label,
        ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
