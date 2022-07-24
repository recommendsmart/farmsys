<?php

namespace Drupal\basket\Ajax;

use Drupal\Core\Ajax\ReplaceCommand;

/**
 * AJAX command for calling the jQuery replace() method.
 */
class BasketReplaceCommand extends ReplaceCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command'   => 'basketReplaceWith',
      'selector'  => $this->selector,
      'data'      => $this->getRenderedContent(),
      'settings'  => $this->settings,
    ];
  }

}
