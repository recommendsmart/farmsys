<?php

namespace Drupal\node_singles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Node Singles.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The settings service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesSettingsInterface
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->settings = $container->get('node_singles.settings');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_singles_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['node_singles.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_singles.settings');

    $form['strict_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict translation'),
      '#default_value' => $config->get('strict_translation'),
      '#description' => $this->t("Only return the single node if a translation is available in the requested language, don't fallback to the default translation."),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $config->get('label'),
      '#description' => $this->t('The human-readable name of a single node. For example, <i>Single</i> or <i>Fixed page</i>.'),
    ];

    $form['label_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection label'),
      '#default_value' => $config->get('label_collection'),
      '#description' => $this->t('The uppercase plural form of the name of a single node. For example, <i>Singles</i> or <i>Fixed pages</i>.'),
    ];

    $form['label_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular label'),
      '#default_value' => $config->get('label_singular'),
      '#description' => $this->t('The indefinite singular form of the name of a single node. For example, <i>single node</i> or <i>fixed page</i>.'),
    ];

    $form['label_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural label'),
      '#default_value' => $config->get('label_plural'),
      '#description' => $this->t('The indefinite plural form of the name of a single node. For example, <i>single nodes</i> or <i>fixed pages</i>.'),
    ];

    $form['label_count'] = [
      '#type' => 'details',
      '#title' => $this->t('Count label'),
      '#description' => $this->t("The label's definite article form for use with a count of single nodes."),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['label_count']['singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular'),
      '#default_value' => $config->get('label_count.singular'),
      '#description' => $this->t('The label for the singular form, with @count as the placeholder for the numeric count. For example, <i>@count single node</i> or <i>@count fixed page</i>.'),
    ];

    $form['label_count']['plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural'),
      '#default_value' => $config->get('label_count.plural'),
      '#description' => $this->t('The label for the plural form, with @count as the placeholder for the numeric count. For example, <i>@count single nodes</i> or <i>@count fixed pages</i>.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('node_singles.settings');

    $config
      ->set('strict_translation', $form_state->getValue('strict_translation'))
      ->set('label', $form_state->getValue('label'))
      ->set('label_collection', $form_state->getValue('label_collection'))
      ->set('label_singular', $form_state->getValue('label_singular'))
      ->set('label_singular', $form_state->getValue('label_singular'))
      ->set('label_plural', $form_state->getValue('label_plural'))
      ->set('label_count', [
        'singular' => $form_state->getValue(['label_count', 'singular']),
        'plural' => $form_state->getValue(['label_count', 'plural']),
      ])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the settings form title.
   */
  public function title() {
    return $this->settings->getCollectionLabel();
  }

}
