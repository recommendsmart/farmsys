<?php

namespace Drupal\commerce_funds\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_funds\Entity\Transaction;
use Drupal\commerce_price\Calculator;

/**
 * Perform the transaction.
 *
 * @RulesAction(
 *   id = "commerce_funds_perform_transaction",
 *   label = @Translation("Perform transaction"),
 *   category = @Translation("Transaction"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:commerce_funds_transaction",
 *       label = @Translation("Transaction"),
 *       description = @Translation("Specifies the transaction that should be performed.")
 *     )
 *   }
 * )
 */
class TransactionPerform extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
    if ($selected_data && isset($selected_data['transaction'])) {
      $type = $selected_data['transaction']->getDataType();
      $this->getPluginDefinition()['context']['transaction']->setDataType($type);
    }
  }

  /**
   * Perform transaction.
   *
   * @param \Drupal\commerce_funds\Entity\Transaction $transaction
   *   The transaction to be performed.
   */
  protected function doExecute(Transaction $transaction) {
    // Fee was set in the rules.
    if ($transaction->getFee()) {
      $transaction->setFee($transaction->getFee());
      $transaction->setNetAmount(
        $this->addRulesFee(
          $transaction->getBrutAmount(),
          $transaction->getFee()
      ));
    }
    // No Rule fee, we use fees set in config.
    else {
      $fee_applied = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee(
        $transaction->getBrutAmount(),
        $transaction->getCurrencyCode(),
        $transaction->bundle()
      );
      $transaction->setFee($fee_applied['fee']);
      $transaction->setNetAmount($fee_applied['net_amount']);
    }

    $transaction->save();
    \Drupal::service('commerce_funds.transaction_manager')->performTransaction($transaction);
  }

  /**
   * Apply rule fee to brut amount.
   *
   * @param float $brut_amount
   *   The amount entered by the user.
   * @param float $fee
   *   The fee set in the rule.
   *
   * @return string
   *   The net amount of the transaction.
   */
  protected function addRulesFee($brut_amount, $fee) {
    return Calculator::add((string) $brut_amount, (string) $fee);
  }

}
