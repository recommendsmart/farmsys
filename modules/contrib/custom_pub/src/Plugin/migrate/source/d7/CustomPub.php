<?php

namespace Drupal\custom_pub\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 custom_pub from database.
 *
 * @MigrateSource(
 *   id = "d7_custom_pub",
 *   source_provider = "custom_pub"
 * )
 */
class CustomPub extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('node', 'n')->fields('n');
    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
    ];
    // Get the options
    $publishing_options = unserialize($this->getDatabase()
      ->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', 'custom_pub_types')
      ->execute()
      ->fetchField());
    foreach ($publishing_options as $key => $publishing_option) {
      $fields[$key] = $publishing_option['name'];
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}