<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_funds\ProductManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to deposit money on user account.
 */
class FundsDeposit extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The product manager.
   *
   * @var \Drupal\commerce_funds\ProductManagerInterface
   */
  protected $productManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProductManagerInterface $product_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->productManager = $product_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_funds.product_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_deposit';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.deposit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currencyCodes = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->getCurrencyCode();
      $currencyCodes[$currency_code] = $currency_code;
    }

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0.0,
      '#title' => $this->t('Deposit Amount'),
      '#description' => $this->t('Please enter the amount you wish to deposit.'),
      '#step' => 0.01,
      '#default_value' => 0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Currency'),
      '#description' => $this->t('Select the currency you want to deposit.'),
      '#options' => $currencyCodes,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product_variation = $this->productManager->createProduct('deposit', $form_state->getValue('amount'), $form_state->getValue('currency'));
    /** @var Drupal\commerce_product\Entity\ProductVariation $product_variation */
    $order = $this->productManager->createOrder($product_variation);

    // Redirect to checkout.
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $order->id(),
    ]);
  }

}
