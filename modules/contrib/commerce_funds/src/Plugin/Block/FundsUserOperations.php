<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "funds_operations",
 *   admin_label = @Translation("Funds operations")
 * )
 */
class FundsUserOperations extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
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
    $config = $this->config->get('commerce_funds.settings');
    $withdrawal_methods = $config->get('withdrawal_methods');
    $exchange_rates = $config->get('exchange_rates');

    return [
      '#theme' => 'user_operations',
      '#withdrawal_methods' => $withdrawal_methods,
      '#exchange_rates' => $exchange_rates,
    ];
  }

}
