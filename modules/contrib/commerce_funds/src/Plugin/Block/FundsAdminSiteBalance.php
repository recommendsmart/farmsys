<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_funds\TransactionManagerInterface;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "admin_site_balance",
 *   admin_label = @Translation("Site balance")
 * )
 */
class FundsAdminSiteBalance extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The transaction manager.
   *
   * @var \Drupal\commerce_funds\TransactionManagerInterface
   */
  protected $transactionManager;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransactionManagerInterface $transaction_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('commerce_funds.transaction_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer transactions');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $balance = $this->transactionManager->loadSiteBalance();

    foreach ($balance as $currency_code => $amount) {
      $symbol = Currency::load($currency_code)->getSymbol();
      $balance[$currency_code] = $symbol . $amount;
    }

    return [
      '#theme' => 'admin_site_balance',
      '#balance' => $balance ?: 0,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
