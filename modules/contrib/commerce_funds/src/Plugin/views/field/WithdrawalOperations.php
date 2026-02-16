<?php

namespace Drupal\commerce_funds\Plugin\views\field;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Custom;
use Drupal\commerce_funds\Entity\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide withdrawal operations for admins.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_funds_withdrawal_operations")
 */
class WithdrawalOperations extends Custom {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Override options.
    $options['alter']['contains']['alter_text']['default'] = FALSE;
    $options['hide_alter_empty']['default'] = TRUE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->field_alias = 'operations';
  }

  /**
   * Return the operations for withdrawal operations.
   *
   * @param Drupal\views\ResultRow $values
   *   Views handler values to be modified.
   *
   * @return array
   *   Renderable dropbutton.
   */
  protected function renderWithdrawalOperations(ResultRow $values) {
    $request_hash = Transaction::load($values->transaction_id)->getHash();
    $status = $values->_entity->getStatus();
    $links = [];
    $args = ['request_hash' => $request_hash];

    if ($this->currentUser->hasPermission(['administer withdraw requests'])) {
      if ($status == 'Pending') {
        $links['approve'] = [
          'title' => $this->t('Approve'),
          'url' => Url::fromRoute('commerce_funds.admin.withdrawal_requests.approve', $args),
        ];
        $links['decline'] = [
          'title' => $this->t('Decline'),
          'url' => Url::fromRoute('commerce_funds.admin.withdrawal_requests.decline', $args),
        ];
      }
      else {
        return $this->t('None');
      }
    }

    $dropbutton = [
      '#type' => 'dropbutton',
      '#links' => $links,
      '#attributes' => [
        'class' => [
          'escrow-link',
        ],
      ],
    ];

    return $dropbutton;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->renderWithdrawalOperations($values);
  }

}
