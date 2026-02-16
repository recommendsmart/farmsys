<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Render\Markup;

/**
 * {@inheritdoc}
 */
class FreeAdditional {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * View.
   */
  public function view() {
    $config = [];
    $path = drupal_get_path('module', 'basket') . '/config/basket_install/additional.free.yml';
    if( file_exists($path) ) {
      $yml = file_get_contents($path);
      if (!empty($yml)) {
        $config = Yaml::decode($yml);
      }
    }
    $mail = $this->basket->getMail();
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      'content'       => [
        '#theme'        => 'basket_admin_additional_free',
        '#info'         => $config + [
          'image_path'  => drupal_get_path('module', 'basket') . '/misc/images/free/',
          'ico'         => $this->basket->getIco('help.svg', 'base'),
          'mail'        => Markup::create('<a href="mailto:'.$mail.'">'.$mail.'</a>'),
          'youtubeLink' => Markup::create('<a href="'.$config['youtube']['url'].'" target="_blank">'.$config['youtube']['name'].'</a>'),
        ]
      ],
      '#attached'     => [
        'library'       => ['basket/additional.free']
      ]
    ];
  }

}
