<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements entities statistics class.
 */
class UsersStatistics implements UsersStatisticsInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function countUsersByEmailDomain() {
    // Use static query for performance.
    $query = $this->connection
      ->query("SELECT
          count(DISTINCT uid) as count,
          SUBSTRING_INDEX(mail, '@', -1) as domain
        FROM {users_field_data}
        WHERE uid > 0
        GROUP BY domain");
    $results = $query->fetchAll();

    $count_by_domain = [];
    foreach ($results as $result) {
      $domain = empty($result->domain) ? '-' : $result->domain;
      $count_by_domain[$domain] = $result->count;
    }

    return $count_by_domain;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

}
