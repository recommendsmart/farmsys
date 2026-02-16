<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Groups statistics class.
 */
class GroupsStatistics implements GroupsStatisticsInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function countGroupsByUser(AccountInterface $user) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT count(DISTINCT gid) as count FROM {group_content_field_data} WHERE entity_id = :uid AND type LIKE :content', [
        ':uid' => $user->id(),
        ':content' => '%group_membership',
      ]);
    $result = $query->fetchAll();

    return $result[0]->count;
  }

  /**
   * {@inheritdoc}
   */
  public function countContents(GroupInterface $group, $minDate = NULL, $maxDate = NULL) {
    // Use static query for performance.
    if (!empty($minDate) && !empty($maxDate)) {
      $query = $this->connection
        ->query('SELECT
            count(id) as count
          FROM {group_content_field_data}
          WHERE gid = :id
          AND created >= :mindate
          AND created <= :maxdate
          AND (type LIKE :node OR type LIKE :content)', [
            ':id'      => $group->id(),
            ':node'    => $group->getGroupType()->id() . '-group_node-%',
            ':content' => 'group_content_type_%',
            ':mindate' => $minDate,
            ':maxdate' => $maxDate,
          ]);
    }
    else {
      $query = $this->connection
        ->query('SELECT 
            count(id) as count
          FROM {group_content_field_data}
          WHERE gid = :id
          AND (type LIKE :node OR type LIKE :content)', [
            ':id'      => $group->id(),
            ':node'    => $group->getGroupType()->id() . '-group_node-%',
            ':content' => 'group_content_type_%',
          ]);
    }

    $result = $query->fetchAll();

    return $result[0]->count;
  }

  /**
   * {@inheritdoc}
   */
  public function countContentsByBundle(GroupInterface $group, $minDate = NULL, $maxDate = NULL) {
    // Use static query for performance.
    if (!empty($minDate) && !empty($maxDate)) {
      $query = $this->connection
        ->query('SELECT
            count(id) as count,
            type,
            MIN(entity_id) as entity_id
          FROM {group_content_field_data}
          WHERE gid = :id
          AND created >= :mindate
          AND created <= :maxdate
          AND (type LIKE :node OR type LIKE :content)
          GROUP by type', [
            ':id'      => $group->id(),
            ':node'    => $group->getGroupType()->id() . '-group_node-%',
            ':content' => 'group_content_type_%',
            ':mindate' => $minDate,
            ':maxdate' => $maxDate,
          ]);
    }
    else {
      $query = $this->connection
        ->query('SELECT
            count(id) as count,
            type,
            MIN(entity_id) as entity_id
          FROM {group_content_field_data}
          WHERE gid = :id
          AND (type LIKE :node OR type LIKE :content)
          GROUP by type', [
            ':id'      => $group->id(),
            ':node'    => $group->getGroupType()->id() . '-group_node-%',
            ':content' => 'group_content_type_%',
          ]);
    }
    $results = $query->fetchAll();

    $count_types = [];
    if (!empty($results)) {
      foreach ($results as $result) {
        $type = 'content';
        if (!empty($result->entity_id)) {
          if ($entity = $this->entityTypeManager->getStorage('node')->loadMultiple([$result->entity_id])) {
            $entity = reset($entity);
            $type = $entity->bundle();
          }
        }

        $count_types[$type] = [
          'type'  => ucfirst($type),
          'count' => $result->count,
        ];
      }
    }

    return $count_types;
  }

  /**
   * {@inheritdoc}
   */
  public function countMembers(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT count(DISTINCT entity_id) as count FROM {group_content_field_data} WHERE gid = :id AND type = :type', [
        ':id' => $group->id(),
        ':type' => $group->getGroupType()->id() . '-group_membership',
      ]);
    $result = $query->fetchAll();

    return $result[0]->count;
  }

  /**
   * {@inheritdoc}
   */
  public function countMembersByRole(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT
          count(DISTINCT gd.entity_id) as count,
          gr.group_roles_target_id as role
        FROM {group_content__group_roles} gr
        INNER JOIN {group_content_field_data} gd
        ON gr.entity_id = gd.id
        WHERE gd.gid = :id
        AND gr.bundle = :type
        GROUP BY gr.group_roles_target_id', [
          ':id'   => $group->id(),
          ':type' => $group->getGroupType()->id() . '-group_membership',
        ]);
    $results = $query->fetchAll();

    $count_roles = [];
    if (!empty($results)) {
      $roles = $this->getGroupRoles();
      foreach ($results as $result) {
        $count_roles[$result->role] = [
          'role'  => isset($roles[$result->role]) ? $roles[$result->role] : $result->role,
          'count' => $result->count,
        ];
      }
    }

    return $count_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function countMembersByAccess(GroupInterface $group) {
    // Use static query for performance.
    $count_by_access = [];

    // Never access.
    $query = $query = $this->connection
      ->query('SELECT count(DISTINCT u.uid) as count
        FROM {users_field_data} u
        INNER JOIN {group_content_field_data} g
        ON u.uid = g.entity_id
        WHERE g.gid = :id
        AND g.type = :type
        AND u.access = 0', [
          ':id'   => $group->id(),
          ':type' => $group->getGroupType()->id() . '-group_membership',
        ]);
    $result = $query->fetchAll();
    $count_by_access['never'] = $result[0]->count;

    // Access by date.
    $access = [
      '1d' => strtotime('-1 day'),
      '1w' => strtotime('-1 week'),
      '1m' => strtotime('-1 month'),
      '1y' => strtotime('-1 year'),
      '3y' => strtotime('-3 years'),
    ];

    foreach ($access as $key => $access) {
      $query = $this->connection
        ->query('SELECT count(DISTINCT u.uid) as count
          FROM {users_field_data} u
          INNER JOIN {group_content_field_data} g
          ON u.uid = g.entity_id
          WHERE g.gid = :id
          AND g.type = :type
          AND u.access > :access', [
            ':id'     => $group->id(),
            ':type'   => $group->getGroupType()->id() . '-group_membership',
            ':access' => $access,
          ]);
      $results = $query->fetchAll();

      $count_by_access[$key] = $results[0]->count;
    }

    return $count_by_access;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupRoles() {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT group_roles_target_id as role FROM {group_content__group_roles}');
    $results = $query->fetchAll();

    $group_roles = [];
    if (!empty($results)) {
      foreach ($results as $result) {
        $role = $this->entityTypeManager->getStorage('group_role')->load($result->role);
        if (!empty($role)) {
          $group_roles[$result->role] = $role->label();
        }
      }
    }

    return $group_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function countUsersByEmailDomain(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query("SELECT
          count(DISTINCT u.uid) as count,
          SUBSTRING_INDEX(u.mail, '@', -1) as domain
        FROM {users_field_data} u
        INNER JOIN {group_content_field_data} g
        ON u.uid = g.entity_id
        WHERE g.gid = :id
        AND g.type = :type
        GROUP BY domain", [
          ':id'   => $group->id(),
          ':type' => $group->getGroupType()->id() . '-group_membership',
        ]);
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
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

}
