<?php

namespace Drupal\access_records\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A controller that returns an empty page for URLs ususally coming from a View.
 */
class AccessRecordEmptyAdminController {

  use StringTranslationTrait;

  /**
   * Returns markup for an empty page because Views is/was not available.
   */
  public function emptyPageBecauseNoViewsConfig() {
    return ['#markup' => $this->t('This page is empty because Views was not available when installing Access Records. If you think this page should exists event without Views, please do not hesitate to contribute to this project (have a look at the README how to contribute).')];
  }

}
