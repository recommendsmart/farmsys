<?php

namespace Drupal\buttons_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure form_id settings.
 */
class ButtonsConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'buttons_config.settings';

  /**
   * Form table name.
   *
   * @var string
   */
  const FORM_TABLE = 'form_ids';

  /**
   * Column form_id.
   *
   * @var string
   */
  const FORM_COLUMN_FORM_ID = 'form_id';

  /**
   * Column form_id.
   *
   * @var string
   */
  const FORM_COLUMN_FORM_TYPE = 'form_type';

  /**
   * Column enabled.
   *
   * @var string
   */
  const FORM_COLUMN_ENABLED = 'enabled';

  /**
   * Column role.
   *
   * @var string
   */
  const FORM_COLUMN_CUSTOM_TEXT = 'custom_text';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'buttons_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form[self::FORM_TABLE] = [
      '#type' => 'table',
      '#header' => [
        self::FORM_COLUMN_FORM_ID => $this->t('Entity Type'),
        self::FORM_COLUMN_FORM_TYPE => $this->t('Form'),
        self::FORM_COLUMN_ENABLED => $this->t('Enabled'),
        self::FORM_COLUMN_CUSTOM_TEXT => $this->t('Custom Text'),
      ],
    ];

    $form_ids = $config->get('form_ids');
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    $content = [];
    foreach ($content_types as $key => $values) {
      $content["node_" . $key] = $values->label();
    }
    foreach ($media_types as $key => $values) {
      $content["media_" . $key . "_add"] = $values->label();
    }
    $entities = [];
    foreach ($content as $key => $value) {
      $entities[$key] = $value;
    }
    $form_types = ["Edit", "Save"];

    if (!is_null($form_ids)) {
      foreach ($form_ids as $custom_text => $options) {
        $form[self::FORM_TABLE][$custom_text][self::FORM_COLUMN_FORM_ID] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#options' => $entities,
          '#default_value' => $form_ids[$custom_text][self::FORM_COLUMN_FORM_ID] ?: '',
        ];
        $form[self::FORM_TABLE][$custom_text][self::FORM_COLUMN_FORM_TYPE] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#options' => $form_types,
          '#default_value' => $form_ids[$custom_text][self::FORM_COLUMN_FORM_TYPE] ?: '',
        ];
        $form[self::FORM_TABLE][$custom_text][self::FORM_COLUMN_ENABLED] = [
          '#type' => 'checkbox',
          '#default_value' => $form_ids[$custom_text][self::FORM_COLUMN_ENABLED] ?: FALSE,
        ];
        $form[self::FORM_TABLE][$custom_text][self::FORM_COLUMN_CUSTOM_TEXT] = [
          '#type' => 'textfield',
          '#maxlength' => 50,
          '#default_value' => $custom_text ?: 'Custom text',
        ];
      }
    }
    $form[self::FORM_TABLE][0][self::FORM_COLUMN_FORM_ID] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $entities,
      '#default_value' => '',
    ];
    $form[self::FORM_TABLE][0][self::FORM_COLUMN_FORM_TYPE] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $form_types,
      '#default_value' => '',
    ];
    $form[self::FORM_TABLE][0][self::FORM_COLUMN_ENABLED] = [
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    ];
    $form[self::FORM_TABLE][0][self::FORM_COLUMN_CUSTOM_TEXT] = [
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#default_value' => '',
      '#placeholder' => 'Custom text',
      '#description' => $this->t('Text that will appear on the Save button.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue(static::FORM_TABLE);
    $aux_table = [];
    foreach ($table as $options) {
      if ($options[self::FORM_COLUMN_CUSTOM_TEXT]) {
        $aux_table[$options[self::FORM_COLUMN_CUSTOM_TEXT]][self::FORM_COLUMN_FORM_TYPE] = $options[self::FORM_COLUMN_FORM_TYPE];
        $aux_table[$options[self::FORM_COLUMN_CUSTOM_TEXT]][self::FORM_COLUMN_FORM_ID] = $options[self::FORM_COLUMN_FORM_ID];
        $aux_table[$options[self::FORM_COLUMN_CUSTOM_TEXT]][self::FORM_COLUMN_ENABLED] = $options[self::FORM_COLUMN_ENABLED];
      }
    }

    $this->configFactory->getEditable(static::SETTINGS)
      ->set(static::FORM_TABLE, $aux_table)
      ->save();
  }

}
