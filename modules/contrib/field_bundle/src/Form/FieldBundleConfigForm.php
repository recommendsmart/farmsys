<?php

namespace Drupal\field_bundle\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for field bundle config forms.
 */
class FieldBundleConfigForm extends BundleEntityFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the FieldBundleConfigForm object.
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

    /** @var \Drupal\field_bundle\FieldBundleConfigInterface $bundle_config */
    $bundle_config = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add field bundle config');
    }
    else {
      $form['#title'] = $this->t(
        'Edit configuration of %label field bundle',
        ['%label' => $bundle_config->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Config label'),
      '#type' => 'textfield',
      '#default_value' => $bundle_config->label(),
      '#description' => $this->t('The human-readable name of this field bundle configuration.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bundle_config->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\field_bundle\Entity\FieldBundleConfig', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this field bundle config. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $bundle_config->getDescription(),
      '#description' => $this->t('Describe this field bundle config. The text will be displayed on the <em>Add field bundle item</em> page.'),
    ];

    $form['label_pattern'] = [
      '#title' => $this->t('Pattern for automatic label generation'),
      '#type' => 'textfield',
      '#description' => $this->t('Instead of manually entering a label on each field bundle item within a form, you can define a label pattern here for auto-generating a value for it. This pattern will be applied everytime a field bundle item is being saved. Tokens are allowed, e.g. [bundle:string-representation]. Leave empty to not use a name pattern for items of this bundle config. If a label pattern is being used, you may optionally hide the label field in the <em>Manage form display</em> settings.'),
      '#default_value' => $bundle_config->getLabelPattern(),
      '#size' => 255,
      '#maxlength' => 255,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['label_pattern_help'] = [
        '#type' => 'container',
        'token_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => ['bundle'],
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
        'status' => $this->t('Published'),
        'new_revision' => $this->t('Create new revision'),
      ],
    ];

    $form['workflow']['options']['status']['#description'] = $this->t('Field bundle items will be automatically published when created.');
    $form['workflow']['options']['new_revision']['#description'] = $this->t('Automatically create new revisions. Users with the "Administer field bundles" permission will be able to override this option.');

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('field_bundle', $bundle_config->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'field_bundle',
          'bundle' => $bundle_config->id(),
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
    $actions['submit']['#value'] = $this->t('Save field bundle config');
    $actions['delete']['#value'] = $this->t('Delete field bundle config');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\field_bundle\FieldBundleConfigInterface $bundle_config */
    $bundle_config = $this->entity;

    $bundle_config->set('id', trim($bundle_config->id()));
    $bundle_config->set('label', trim($bundle_config->label()));
    $bundle_config->set('status', (bool) $form_state->getValue(['options', 'status']));
    $bundle_config->set('new_revision', (bool) $form_state->getValue(['options', 'new_revision']));
    $status = $bundle_config->save();

    $t_args = ['%name' => $bundle_config->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The configuration for the field bundle %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The configuration for the field bundle %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    // Update workflow options.
    $fields = $this->entityFieldManager->getFieldDefinitions('field_bundle', $bundle_config->id());
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $field_bundle = $this->entityTypeManager->getStorage('field_bundle')->create(['config' => $bundle_config->id()]);
    foreach (['status'] as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($field_bundle->$field_name->value != $value) {
        $fields[$field_name]->getConfig($bundle_config->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();

    $form_state->setRedirectUrl($bundle_config->toUrl('collection'));
  }

  /**
   * Prepares workflow options to be used in the 'checkboxes' form element.
   *
   * @return array
   *   Array of options ready to be used in #options.
   */
  protected function getWorkflowOptions() {
    /** @var \Drupal\field_bundle\FieldBundleConfigInterface $field_bundle_config */
    $field_bundle_config = $this->entity;
    $workflow_options = [
      'status' => $field_bundle_config->getStatus(),
      'new_revision' => $field_bundle_config->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    return array_combine($keys, $keys);
  }

}
