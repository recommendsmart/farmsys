<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketCron {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    /*
     * Delete, abandoned goods anonymously, through
     */
    if (!empty($delete_anonim_days = $this->basket->getSettings('empty_trash', 'config.delete_anonim'))) {
      $query = \Drupal::database()->select('basket', 'b');
      $query->fields('b', ['id']);
      $query->condition('b.add_time', strtotime('-' . $delete_anonim_days . ' days'), '<=');
      // users_field_data.
      $query->leftJoin('users_field_data', 'u', 'b.sid = CONCAT(u.uid, \'\')');
      $query->isNull('u.uid');
      // ---
      $ids = $query->range(0, 100)->execute()->fetchCol();
      if (!empty($ids)) {
        $this->delete($ids);
      }
    }
    /*
     * Empty items in the basket
     */
    if (!empty($this->basket->getSettings('empty_trash', 'config.delete_nodes'))) {
      $query = \Drupal::database()->select('basket', 'b');
      $query->fields('b', ['id']);
      // users_field_data.
      $query->leftJoin('node', 'n', 'n.nid = b.nid');
      $query->isNull('n.nid');
      // ---
      $ids = $query->range(0, 100)->execute()->fetchCol();
      if (!empty($ids)) {
        $this->delete($ids);
      }
    }
    /*
     * Delete order items temps
     */
    $tmpDir = \Drupal::service('file_system')->realpath('temporary://OrderTempItems_' . date('d_m_Y', strtotime('-1 days')));
    if (is_dir($tmpDir)) {
      \Drupal::service('file_system')->deleteRecursive($tmpDir);
    }
  }

  /**
   * {@inheritdoc}
   */
  private function delete($ids) {
    \Drupal::database()->delete('basket')
      ->condition('id', $ids, 'in')
      ->execute();
  }

}
