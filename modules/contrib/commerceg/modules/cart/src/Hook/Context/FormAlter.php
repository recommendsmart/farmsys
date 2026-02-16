<?php

namespace Drupal\commerceg_cart\Hook\Context;

use Drupal\commerceg\MachineName\Config\PersonalContextDisabledMode as DisabledModeConfig;
use Drupal\commerceg_context\Context\ManagerInterface as ContextManagerInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Holds methods implementing form alteration hooks.
 *
 * This service is defined only when the Context submodule is enabled; we don't
 * make any alterations to cart-related forms otherwise.
 */
class FormAlter {

  use StringTranslationTrait;

  /**
   * The shopping context manager.
   *
   * @var \Drupal\commerceg_context\Context\ManagerInterface
   */
  protected $contextManager;

  /**
   * The context module settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new FormAlter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\commerceg_context\Context\ManagerInterface $context_manager
   *   The shopping context manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ContextManagerInterface $context_manager,
    TranslationInterface $string_translation
  ) {
    $this->config = $config_factory->get('commerceg_context.settings');
    $this->contextManager = $context_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_form_alter().
   *
   * Delegates to specific form alteration methods in the case the form ID is
   * dynamically generated and we cannot use `hook_form_FORM_ID_alter()`.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function formAlter(
    array &$form,
    FormStateInterface $form_state,
    $form_id
  ) {
    if (strpos($form_id, 'commerce_order_item_add_to_cart_form_') !== 0) {
      return;
    }

    $this->addToCartFormAlter($form, $form_state);
  }

  /**
   * Implements hook_form_alter() for the Add To Cart forms.
   *
   * If the personal shopping context is disabled in this module's configuration
   * we want to disable or hide the Add To Cart button. Other features in this
   * or other modules may be deciding on whether the user should be prevented
   * from accessing the Add To Cart form in the first place e.g. only allowing
   * users that have a context to log in, or enforcing that a context is always
   * set etc. Here we just make sure that the user does not add products to the
   * cart.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function addToCartFormAlter(
    array &$form,
    FormStateInterface $form_state
  ) {
    if (!$this->config->get('status')) {
      return;
    }

    $settings = $this->config->get('personal_context');

    // No need to proceed further if personal context is enabled, nothing to do.
    if ($settings['status']) {
      return;
    }

    // Otherwise, if the user does not have a group context i.e. is in personal
    // shopping context, disable or hide the button based on what is defined in
    // configuration.
    $context = $this->contextManager->get();
    if ($context) {
      return;
    }

    $supported_modes = [
      DisabledModeConfig::MODE_DISABLE,
      DisabledModeConfig::MODE_HIDE,
    ];
    if (!in_array($settings['disabled_mode']['mode'], $supported_modes)) {
      return;
    }

    // @I Make disabled mode IDs available as constants
    //    type     : task
    //    priority : low
    //    labels   : coding-standards
    if ($settings['disabled_mode']['mode'] === DisabledModeConfig::MODE_HIDE) {
      $form['actions']['submit']['#access'] = FALSE;
    }
    else {
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    if (!$settings['disabled_mode']['add_to_cart_message']) {
      return;
    }

    $form['actions']['message'] = [
      '#markup' => $this->t($settings['disabled_mode']['add_to_cart_message']),
      '#weight' => 100,
    ];
  }

}
