<?php

namespace Drupal\yasm\Services;

/**
 * Defines users statistics interface.
 */
interface UsersStatisticsInterface {

  /**
   * Count users by email domain.
   */
  public function countUsersByEmailDomain();

}
