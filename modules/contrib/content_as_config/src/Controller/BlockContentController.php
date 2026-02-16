<?php

namespace Drupal\content_as_config\Controller;

/**
 * Controller for syncing feeds.
 */
class BlockContentController extends EntityControllerBase {

  const ENTITY_TYPE = 'block_content';

  const FIELD_NAMES = [
    'info',
    'langcode',
    'type',
    'reusable',
  ];

}
