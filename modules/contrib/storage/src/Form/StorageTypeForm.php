<?php

namespace Drupal\storage\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form class for a Storage type.
 */
class StorageTypeForm extends BundleEntityFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the StorageTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\storage\Entity\StorageTypeInterface $storage_type */
    $storage_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add storage type');
      $fields = $this->entityFieldManager->getBaseFieldDefinitions('storage');
    }
    else {
      $form['#title'] = $this->t('Edit %label storage type', ['%label' => $storage_type->label()]);
      $fields = $this->entityFieldManager->getFieldDefinitions('storage', $storage_type->id());
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type label'),
      '#maxlength' => 255,
      '#default_value' => $storage_type->label(),
      '#description' => $this->t('The human-readable label of this storage type. This text will be displayed as part of the list on the <em><a href="/storage/add" target="_blank">Add storage</a></em> page. This name must be unique.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $storage_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$storage_type->isNew(),
      '#machine_name' => [
        'exists' => ['\Drupal\storage\Entity\StorageType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this storage type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %storage-add page.', [
        '%storage-add' => $this->t('Add storage data'),
      ]),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $storage_type->getDescription(),
      '#description' => $this->t('This description text will be displayed on the <em><a href="/storage/add" target="_blank">Add storage</a></em> page.'),
    ];

    $form['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $storage_type->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the form when creating or editing data of this storage type.'),
    ];

    $form['name_label'] = [
      '#title' => $this->t('Form label for name field'),
      '#type' => 'textfield',
      '#default_value' => $fields['name']->getLabel(),
      '#description' => $this->t('This text will be used as the form label for the name field when creating or editing data of this storage type.'),
      '#required' => TRUE,
    ];

    $form['name_pattern'] = [
      '#title' => $this->t('Pattern for automatic name generation'),
      '#type' => 'textfield',
      '#description' => $this->t('Instead of manually entering a name on each Storage entity within a form, you can define a name pattern here for auto-generating a value for it. This pattern will be applied everytime a Storage entity is being saved. Tokens are allowed, e.g. [storage:string-representation]. Leave empty to not use a name pattern for entities of this Storage type. If a name pattern is being used, you may optionally hide the name field in the <em>Manage form display</em> settings.'),
      '#default_value' => $storage_type->getNamePattern(),
      '#attributes' => [
        'style' => ['width: 100%'],
      ],
      '#maxlength' => 255,
    ];

    // Display the list of available placeholders if token module is installed.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['name_pattern_help'] = [
        // Put token replacement link inside a container so it can be used with #states.
        '#type' => 'container',
        'token_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => ['storage'],
          '#dialog' => TRUE,
        ],
      ];
    }
    else {
      $form['name_pattern']['#description'] .= ' ' . $this->t('To get a list of available tokens, install the <a href=":drupal-token" target="blank">Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']);
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Published'),
      '#default_value' => $storage_type->getStatus(),
      '#description' => t('Whether Storage items should be published by default.'),
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $storage_type->get('new_revision'),
      '#description' => t('Create a new revision by default for this storage type.'),
    ];

    $form['revision_expose'] = [
      '#type' => 'checkbox',
      '#title' => t('Expose revision checkbox'),
      '#default_value' => $storage_type->get('revision_expose'),
      '#description' => t('Whether or not a checkbox will be visible in the form. If not exposed, the default value above will always be used.'),
    ];

    $form['revision_log'] = [
      '#type' => 'checkbox',
      '#title' => t('Expose revision log'),
      '#default_value' => $storage_type->get('revision_log'),
      '#description' => t('Whether or not the editor can write a revision log message.'),
      '#states' => [
        // Show this textarea only if the 'repeat' select has a value.
        'visible' => [
          'input[name="revision_expose"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['has_canonical'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow direct viewing of entities'),
      '#default_value' => $storage_type->hasCanonical(),
      '#description' => t('Whether items of this Storage type have a canonical URL for being viewed (accessible via /storage/{id}). When enabled, <a href=":permissions_url" target="_blank">permissions</a> need to be properly defined. If disabled, attempts to view an entity at /storage/{id} will redirect to /storage/{id}/edit potentially returning a 404 if the user lacks the required permissions.', [
        ':permissions_url' => '/admin/people/permissions',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\storage\Entity\StorageTypeInterface $storage_type */
    $storage_type = $this->entity;
    $storage_type->setStatus((bool) $form_state->getValue('status'));
    $storage_type->set('has_canonical', (bool) $form_state->getValue('has_canonical'));
    $status = $storage_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Storage type.', [
          '%label' => $storage_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Storage type.', [
          '%label' => $storage_type->label(),
        ]));
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('storage', $storage_type->id());
    // Update name field definition.
    $name_field = $fields['name'];
    $name_label = $form_state->getValue('name_label');
    if ($name_field && $name_field->getLabel() != $name_label) {
      $name_field->getConfig($storage_type->id())->setLabel($name_label)->save();
    }
    // Update the status field definition.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    /** @var \Drupal\storage\Entity\StorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('storage')->create(['type' => $storage_type->id()]);
    if ($storage->isPublished() != $storage_type->getStatus()) {
      $fields['status']->getConfig($storage_type->id())->setDefaultValue($storage_type->getStatus())->save();
    }
    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($storage_type->toUrl('collection'));
  }

}
