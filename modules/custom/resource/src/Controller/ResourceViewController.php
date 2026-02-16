<?php

/**
 * @file
 * Contains \Drupal\resource\Controller\ResourceViewController.
 */

namespace Drupal\resource\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single resource.
 */
class ResourceViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $resource, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($resource, $view_mode, $langcode);

    foreach ($resource->uriRelationships() as $rel) {
      // Set the resource path as the canonical URL to prevent duplicate content.
      $build['#attached']['html_head_link'][] = array(
        array(
          'rel' => $rel,
          'href' => $resource->toUrl($rel),
        ),
        TRUE,
      );

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $resource->toUrl($rel, array('alias' => TRUE)),
          ),
          TRUE,
        );
      }
    }

    return $build;
  }

}
