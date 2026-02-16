<?php

namespace Drupal\feeds_as_config\Controller;

use Drupal\content_as_config\Controller\EntityControllerBase;

/**
 * Controller for syncing feeds.
 */
class FeedsController extends EntityControllerBase {

  const ENTITY_TYPE = 'feeds_feed';
  const FIELD_NAMES = [
    'title',
    'source',
    'type',
  ];

}
