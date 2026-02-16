<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_funds\WithdrawalMethodManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure the withdrawals methods allowed.
 */
class ConfigureWithdrawals extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\commerce_funds\WithdrawalMethodManager
   */
  protected $withdrawalManager;

  /**
   * Class constructor.
   */
  public function __construct(WithdrawalMethodManagerInterface $withdrawal_manager) {
    $this->withdrawalManager = $withdrawal_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.withdrawal_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_configure_withdrawal_methods';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_funds.settings');
    $methods = $this->withdrawalManager->getDefinitions();
    foreach ($methods as $key => $method) {
      $readable_methods[$key] = $method['name']->render();
    }

    $form['methods'] = [
      '#type' => 'checkboxes',
      '#options' => $readable_methods,
      '#default_value' => $config->get('withdrawal_methods'),
      '#title' => $this->t('Choose Payment methods allowed for withdrawals'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $this->config('commerce_funds.settings')
      ->set('withdrawal_methods', $values['methods'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
