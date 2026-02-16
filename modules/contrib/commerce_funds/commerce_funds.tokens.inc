<?php

/**
 * @file
 * Token hooks for commerce_funds module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function commerce_funds_token_info() {
  $balance_type = [
    'name' => t('Balance'),
    'description' => t('User balances.'),
    'needs-data' => 'commerce_funds_user_funds',
  ];

  $balance = [];
  $balance['uid'] = [
    'name' => t('Owner'),
    'description' => t('The balance user ID.'),
  ];

  $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();

  foreach ($currencies as $currency_code => $currency) {
    $balance[$currency_code] = [
      'name' => t('@currency currency code.', [
        '@currency' => $currency_code,
      ]),
      'description' => t('@currency currency symbol.', [
        '@currency' => $currency_code,
      ]),
    ];
    $balance[$currency_code . '_amount'] = [
      'name' => t('@currency balance amount', [
        '@currency' => $currency_code,
      ]),
      'description' => t("User's @currency balance amount.", [
        '@currency' => $currency_code,
      ]),
    ];
    $balance[$currency_code . '_symbol'] = [
      'name' => t('@currency symbol', [
        '@currency' => $currency_code,
      ]),
      'description' => t('@currency currency code.', [
        '@currency' => $currency_code,
      ]),
    ];
  }

  return [
    'types' => [
      'commerce_funds_balance' => $balance_type,
    ],
    'tokens' => [
      'commerce_funds_balance' => $balance,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function commerce_funds_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();

  if ($type == 'commerce_funds_balance' && !empty($data['commerce_funds_balance'])) {
    $balance = $data['commerce_funds_balance'];
    $uid = $data['commerce_funds_balance_uid'];

    foreach ($tokens as $name => $original) {
      if ($name == 'uid') {
        $replacements[$original] = $uid;
      }
      foreach ($currencies as $currency_code => $currency) {
        switch ($name) {
          case $currency_code . '_amount':
            $replacements[$original] = $balance[$currency_code];
            break;

          case $currency_code . '_symbol':
            $replacements[$original] = $currency->getSymbol();
            break;

          case $currency_code:
            $replacements[$original] = $currency->getCurrencyCode();
            break;
        }
      }
    }
  }

  return $replacements;
}
