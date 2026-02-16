<?php

namespace Drupal\content_as_config\Controller;

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
