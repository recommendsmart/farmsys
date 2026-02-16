<?php

namespace Drupal\commerce_funds\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;

/**
 * Provides the completion message and update user balance.
 *
 * @CommerceCheckoutPane(
 *   id = "deposit_completion_message",
 *   label = @Translation("Deposit completion message"),
 *   default_step = "complete",
 * )
 */
class DepositCompletionMessage extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $summary = $this->t('Provides custom completion message and template for deposits.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['message'] = [
      '#theme' => 'deposit_completion_message',
      '#order_entity' => $this->order,
      '#amount' => number_format($this->order->getItems()[0]->getTotalPrice()->getNumber(), '2'),
      '#currency_code' => $this->order->getItems()[0]->getTotalPrice()->getCurrencyCode(),
    ];

    return $pane_form;
  }

}
