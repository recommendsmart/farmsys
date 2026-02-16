<?php

namespace Drupal\basket\Plugin\Basket\Popup;

use Drupal\basket\Plugins\Popup\BasketPopupSystemInterface;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Basic Cart Popup Plugin.
 *
 * @BasketPopupSystem(
 *          id        = "basket_popup",
 *          name      = "Basket popup",
 * )
 */
class BasketPopupBase implements BasketPopupSystemInterface {

  /**
   * {@inheritdoc}
   */
  public function open(&$response, $title, $html, $options) {
    $response->addCommand(new OpenModalDialogCommand(
      $title,
      $html,
      [
        'width' => !empty($options['width']) ? $options['width'] : 600,
      ]
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCloseOnclick() {
    return 'jQuery(\'.ui-dialog-titlebar-close\').click();';
  }

  /**
   * {@inheritdoc}
   */
  public function attached(&$attached) {}

}
