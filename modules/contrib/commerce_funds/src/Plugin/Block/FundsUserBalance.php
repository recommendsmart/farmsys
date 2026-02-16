<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\commerce_funds\TransactionManagerInterface;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "user_balance",
 *   admin_label = @Translation("User balance")
 * )
 */
class FundsUserBalance extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The transaction manager.
   *
   * @var \Drupal\commerce_funds\TransactionManagerInterface
   */
  protected $transactionManager;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account, TransactionManagerInterface $transaction_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->transactionManager = $transaction_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('commerce_funds.transaction_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'deposit funds');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $balance = $this->transactionManager->loadAccountBalance($this->account);

    foreach ($balance as $currency_code => $amount) {
      $symbol = Currency::load($currency_code)->getSymbol();
      $balance[$currency_code] = $symbol . $amount;
    }

    return [
      '#theme' => 'user_balance',
      '#balance' => $balance ?: 0,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
