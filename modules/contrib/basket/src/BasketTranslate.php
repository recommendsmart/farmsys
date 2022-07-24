<?php

namespace Drupal\basket;

use Drupal\Core\Url;
use Drupal\basket\BasketTranslatableMarkup;

/**
 * {@inheritdoc}
 */
class BasketTranslate {

  /**
   * Set isTranslate.
   *
   * @var bool
   */
  protected $isTranslate;

  /**
   * Set contextModule.
   *
   * @var string
   */
  protected $contextModule;

  /**
   * {@inheritdoc}
   */
  public function __construct($contextModule = 'basket') {
    $this->contextModule = $contextModule;
  }

  /**
   * {@inheritdoc}
   */
  public function title(array $_title_arguments = [], $_title = '') {
    return $this->trans($_title);
  }

  /**
   * {@inheritdoc}
   */
  public function t($text, $args = [], $options = []) {
    $options['context'] = $this->contextModule;
    return $this->trans(trim($text), $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function trans($text, $args = [], $options = []) {
    $options['context'] = $this->contextModule;
    return new BasketTranslatableMarkup($text, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (!isset($this->isTranslate)) {
      $this->isTranslate = \Drupal::database()->schema()->tableExists('locales_source');
    }
    return $this->isTranslate;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateLink($text) {
    if ($this->isEnabled()) {
      return [
        '#type'        => 'inline_template',
        '#template'    => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" title="{{title}}">{{ico|raw}}</a>',
        '#context'    => [
          'title'         => $this->t('Translation'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
            'page_type'     => 'api-translation_popup',
          ], [
            'query'            => [
              'string'    => $text,
            ],
          ])->toString() . '\')',
          'ico'           => \Drupal::service('Basket')->getIco('google.svg', 'base'),
        ],
      ];
    }
  }

}