<?php

namespace Drupal\commerceg_context\Form;

use Drupal\commerceg\MachineName\Config\PersonalContextDisabledMode as DisabledModeConfig;

use Drupal\commerce\EntityHelper;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure shopping context-related settings.
 */
class Settings extends ConfigFormBase {

  /**
   * The group type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $groupTypeStorage;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->setConfigFactory($config_factory);
    $this->groupTypeStorage = $entity_type_manager->getStorage('group_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerceg_context_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerceg_context.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerceg_context.settings');

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable shopping context'),
      '#description' => $this->t(
        'Enabling shopping context will activate cart isolation per context. By
         default, when a user is acting within a context only carts that belong
         to that context will be visible, and when a product is added to a cart
         it will be added automatically to a cart that belongs to that context.'
      ),
      '#default_value' => $config->get('status'),
    ];

    $group_types = $this->groupTypeStorage->loadMultiple();
    $form['group_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Shopping context group type'),
      '#description' => $this->t(
        'The groups that will be the available shopping contexts for an
        authenticated user will be the ones that are of the selected group type
        and that the user is a member of.'
      ),
      '#options' => EntityHelper::extractLabels($group_types),
      '#default_value' => $config->get('group_context.group_type'),
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form = $this->buildPersonalContextForm($form, $form_state, $config);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('commerceg_context.settings');

    if (!$values['status']) {
      $config->set('status', FALSE)->save();
      parent::submitForm($form, $form_state);
      return;
    }

    $config
      ->set('status', TRUE)
      ->set('group_context.group_type', $values['group_type']);

    $this->submitPersonalContextForm($form, $form_state, $config);

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Builds the form elements related to personal context settings.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Config $config
   *   The context configuration.
   */
  protected function buildPersonalContextForm(
    array $form,
    FormStateInterface $form_state,
    Config $config
  ) {
    $settings = $config->get('personal_context');
    $form['personal_context'] = [
      '#type' => 'details',
      '#title' => $this->t('Personal shopping context'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['personal_context']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow personal shopping context'),
      '#description' => $this->t(
        'Allowing personal shopping context will permit users to make purchases
        for themselves i.e. outside of the context of a group.'
      ),
      '#default_value' => $settings['status'],
    ];

    $form['personal_context']['disabled_mode']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Disabled mode'),
      '#description' => $this->t(
        'When the personal shopping context is disabled and the "Disable" mode
         is selected, any UI elements that allow users to create carts (such as
         the "Add to cart" button on product pages) will remain visible but they
         will be disabled if the user is not acting within an active context. If
         the "Hide" mode is selected, the UI elements will be hidden instead.'
      ),
      '#default_value' => $settings['disabled_mode']['mode'],
      '#options' => [
        DisabledModeConfig::MODE_DISABLE => $this->t('Disable'),
        DisabledModeConfig::MODE_HIDE => $this->t('Hide'),
      ],
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-personal-context-status"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['personal_context']['disabled_mode']['add_to_cart_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disabled mode message'),
      '#description' => $this->t(
        'Optionally, a message that will be displayed on the "Add to cart" form.
         If the "Disabled" mode is selected, the message will be displayed next
         to the "Add to cart" button; if the "Hide" mode is selected the message
         will be displayed instead of the "Add to cart" button.'
      ),
      '#default_value' => $settings['disabled_mode']['add_to_cart_message'],
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-personal-context-status"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Submit actions related to personal context settings.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Config $config
   *   The context configuration.
   */
  public function submitPersonalContextForm(
    array &$form,
    FormStateInterface $form_state,
    Config $config
  ) {
    $values = $form_state->getValues();

    if ($values['personal_context']['status']) {
      $config->set('personal_context.status', TRUE);
      return;
    }

    $config
      ->set('personal_context.status', FALSE)
      ->set(
        'personal_context.disabled_mode.mode',
        $values['personal_context']['disabled_mode']['mode']
      )
      ->set(
        'personal_context.disabled_mode.add_to_cart_message',
        $values['personal_context']['disabled_mode']['add_to_cart_message']
      );
  }

}
