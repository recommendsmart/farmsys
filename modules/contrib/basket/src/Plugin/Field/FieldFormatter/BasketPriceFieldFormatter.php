<?php

namespace Drupal\basket\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'BasketPriceFieldFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "BasketPriceFieldFormatter",
 *   label = @Translation("Basket Price Field formatter"),
 *   field_types = {
 *     "basket_price_field"
 *   }
 * )
 */
class BasketPriceFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'disable_convert'       => FALSE,
      'twig_template'         => '{% if old_price %}
    <div class="price_old">
        {{ old_price|number_format(2, \',\', \' \') }} {{ currency }}
    </div>
{% endif %}
<div class="price">
    {{ price|number_format(2, \',\', \' \') }} {{ currency }}
</div>',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [
      'twig_template'         => [
        '#type'                 => 'textarea',
        '#title'                => 'Twig template',
        '#description'          => implode('<br/>', [
          '{{ price }}',
          '{{ old_price }}',
          '{{ currency }}',
        ]),
        '#default_value'        => $this->getSetting('twig_template'),
      ],
      'disable_convert'        => [
        '#type'                    => 'checkbox',
        '#title'                => t('Do not convert'),
        '#default_value'         => $this->getSetting('disable_convert'),
        '#weight'                 => 4,
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $basket = \Drupal::service('Basket');
    foreach ($items as $delta => $item) {
      $item = $item->getValue();
      $currencyID = $item['currency'];
      if (empty($this->getSetting('disable_convert'))) {
        if (!empty($item['value'])) {
          $basket->Currency()->priceConvert($item['value'], $currencyID);
        }
        if (!empty($item['old_value'])) {
          $basket->Currency()->priceConvert($item['old_value'], $item['currency']);
        }
      }
      $currencyTerm = $basket->Currency()->load($currencyID);
      if (!empty($currencyTerm)) {
        $currency = $basket->Translate()->trans(trim($currencyTerm->name));
      }
      $elements[$delta] = [
        '#type'            => 'inline_template',
        '#template'     => $this->getSetting('twig_template'),
        '#context'      => [
          'price'         => $item['value'],
          'old_price'     => $item['old_value'],
          'currency'      => $currency,
        ],
      ];
    }
    return $elements;
  }

}
