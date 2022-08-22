<?php

namespace Drupal\access_records\Form;

use Drupal\access_records\Entity\AccessRecord;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for access record type forms.
 */
class AccessRecordTypeForm extends BundleEntityFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the AccessRecordTypeForm object.
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
    $instance = new static(
      $container->get('entity_field.manager')
    );
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\access_records\AccessRecordTypeInterface $access_record_type */
    $access_record_type = $this->entity;
    if ($this->operation == 'add') {
      $has_data = FALSE;
      $form['#title'] = $this->t('Add access record type');
    }
    else {
      $has_data = !empty($this->entityTypeManager->getStorage('access_record')->getQuery()
        ->accessCheck(FALSE)
        ->condition('ar_type', $access_record_type->id())
        ->range(0, 1)
        ->execute());
      $form['#title'] = $this->t(
        'Edit type of %label access records',
        ['%label' => $access_record_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Type label'),
      '#type' => 'textfield',
      '#default_value' => $access_record_type->label(),
      '#description' => $this->t('The human-readable name of this access record type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $access_record_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\access_records\Entity\AccessRecordType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this access record type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $access_record_type->getDescription(),
      '#description' => $this->t('Describe this type of access records. The text will be displayed on the <em>Add new access record</em> page.'),
    ];

    $target_type_options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    // @todo Consider supporting config entities too.
    $target_type_options = reset($target_type_options);
    unset($target_type_options['access_record']);
    $form['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Target type'),
      '#default_value' => $access_record_type->getTargetTypeId(),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
      '#options' => $target_type_options,
    ];

    $form['operations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed operations'),
      '#options' => AccessRecord::availableOperations(),
      '#description' => $this->t('Users having access records of this type will be allowed to perform the checked operations on the target.'),
      '#default_value' => $access_record_type->getOperations(),
      '#disabled' => $has_data,
    ];

    $form['label_pattern'] = [
      '#title' => $this->t('Label pattern'),
      '#type' => 'textfield',
      '#description' => $this->t('Define a pattern to use for creating the label of the access record. Tokens are allowed, e.g. [access_record:ar_type_label]. Leave empty to not use any pattern.'),
      '#default_value' => $access_record_type->getLabelPattern(),
      '#size' => 255,
      '#maxlength' => 255,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['label_pattern_help'] = [
        '#type' => 'container',
        'token_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => ['access_record'],
          '#dialog' => TRUE,
        ],
      ];
    }
    else {
      $form['label_pattern']['#description'] .= ' ' . $this->t('To get a list of available tokens, install the <a target="_blank" rel="noreferrer noopener" href=":drupal-token" target="blank">contrib Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']);
    }

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing options'),
      '#group' => 'additional_settings',
    ];

    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#default_value' => $this->getWorkflowOptions(),
      '#options' => [
        'status' => $this->t('Enabled'),
        'new_revision' => $this->t('Create new revision'),
      ],
    ];

    $form['workflow']['options']['status']['#description'] = $this->t('Access records will be automatically enabled when created.');
    $form['workflow']['options']['new_revision']['#description'] = $this->t('Automatically create new revisions. Users with the "Administer access records" permission will be able to override this option.');

    // @todo Make access records translation-aware.
    // @see https://www.drupal.org/project/access_records/issues/3259584
    if (FALSE && $this->moduleHandler->moduleExists('language') && $this->entityTypeManager->getDefinition('access_record')->isTranslatable()) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('access_record', $access_record_type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'access_record',
          'bundle' => $access_record_type->id(),
        ],
        '#default_value' => $language_configuration,
      ];

      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save this type');
    $actions['delete']['#value'] = $this->t('Delete this type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\access_records\AccessRecordTypeInterface $access_record_type */
    $access_record_type = $this->entity;

    $access_record_type->set('id', trim($access_record_type->id()));
    $access_record_type->set('label', trim($access_record_type->label()));
    $access_record_type->set('operations', array_values(array_filter($form_state->getValue('operations'))));
    $access_record_type->set('status', (bool) $form_state->getValue(['options', 'status']));
    $access_record_type->set('new_revision', (bool) $form_state->getValue(['options', 'new_revision']));
    $status = $access_record_type->save();

    $t_args = ['%name' => $access_record_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The type for access records %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The type for access records %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    // Update workflow options.
    $fields = $this->entityFieldManager->getFieldDefinitions('access_record', $access_record_type->id());
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    /** @var \Drupal\access_records\AccessRecordInterface $access_record */
    $access_record = $this->entityTypeManager->getStorage('access_record')->create(['ar_type' => $access_record_type->id()]);

    $value = (bool) $form_state->getValue(['options', 'status']);
    $field_name = 'ar_enabled';
    if ($access_record->$field_name->value != $value) {
      $fields[$field_name]->getConfig($access_record_type->id())->setDefaultValue($value)->save();
    }

    $has_data = !empty($this->entityTypeManager->getStorage('access_record')->getQuery()
      ->accessCheck(FALSE)
      ->condition('ar_type', $access_record_type->id())
      ->range(0, 1)
      ->execute());

    if (!$has_data && ($value = $access_record_type->getSubjectTypeId())) {
      if ($this->entityTypeManager->hasDefinition($value)) {
        $entity_type_id = $value;
        if ($access_record->ar_subject_type->value != $entity_type_id) {
          $fields['ar_subject_type']->getConfig($access_record_type->id())
            ->setDefaultValue($entity_type_id)
            ->save();
        }
      }
    }

    if (!$has_data && ($value = $access_record_type->getTargetTypeId())) {
      if ($this->entityTypeManager->hasDefinition($value)) {
        $entity_type_id = $value;
        if ($access_record->ar_target_type->value != $entity_type_id) {
          $fields['ar_target_type']->getConfig($access_record_type->id())
            ->setDefaultValue($entity_type_id)
            ->save();
        }
      }
    }

    if (!$has_data && ($value = $form_state->getValue('operations'))) {
      $operations = $access_record_type->getOperations();
      $current_value = [];
      $field_item_list = $access_record->get('ar_operation');
      foreach ($field_item_list->getValue() as $item) {
        $current_value[] = $item['value'];
      }
      if ($current_value != $operations) {
        $field_item_list->setValue(NULL);
        foreach ($operations as $op) {
          $field_item_list->appendItem($op);
        }
        $fields['ar_operation']->getConfig($access_record_type->id())
          ->setDefaultValue($field_item_list->getValue())
          ->save();
      }
    }

    // Add some default fields.
    if ($status == SAVED_NEW) {
      $access_record_type->addDefaultFields();
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();

    $form_state->setRedirectUrl($access_record_type->toUrl('collection'));
  }

  /**
   * Prepares workflow options to be used in the 'checkboxes' form element.
   *
   * @return array
   *   Array of options ready to be used in #options.
   */
  protected function getWorkflowOptions() {
    /** @var \Drupal\access_records\AccessRecordTypeInterface $access_record_type */
    $access_record_type = $this->entity;
    $workflow_options = [
      'status' => $access_record_type->getStatus(),
      'new_revision' => $access_record_type->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    return array_combine($keys, $keys);
  }

}
