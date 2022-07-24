<?php

namespace Drupal\basket;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * {@inheritdoc}
 */
class BasketTranslatableMarkup extends TranslatableMarkup {
  
  /**
   * {@inheritdoc}
   */
  public function __construct($string, array $arguments = [], array $options = [], TranslationInterface $string_translation = NULL) {
    if (!is_string($string)) {
      $string = '';
    }
    parent::__construct($string, $arguments, $options, $string_translation);
  }
}
