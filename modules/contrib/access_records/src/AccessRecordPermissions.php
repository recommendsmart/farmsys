<?php

namespace Drupal\access_records;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access record permissions builder.
 */
class AccessRecordPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AccessRecordPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of access record permissions.
   *
   * @return array
   *   The access record permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getAccessRecordPermissions() {
    $permissions = [];
    // Generate permissions for all access record types.
    $bundle_configs = $this->entityTypeManager
      ->getStorage('access_record_type')->loadMultiple();
    foreach ($bundle_configs as $type) {
      $permissions += $this->buildPermissions($type);
    }
    return $permissions;
  }

  /**
   * Returns a list of permissions for a given access record config.
   *
   * @param \Drupal\access_records\AccessRecordTypeInterface $type
   *   The access record type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(AccessRecordTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id access_record" => [
        'title' => $this->t('%type_name: Create new access record', $type_params),
        'restrict access' => TRUE,
      ],
      "view own $type_id access_record" => [
        'title' => $this->t('%type_name: View own access record', $type_params),
        'restrict access' => TRUE,
      ],
      "view $type_id access_record" => [
        'title' => $this->t('%type_name: View all access records', $type_params),
        'restrict access' => TRUE,
      ],
      "update own $type_id access_record" => [
        'title' => $this->t('%type_name: Update own access record', $type_params),
        'restrict access' => TRUE,
      ],
      "update $type_id access_record" => [
        'title' => $this->t('%type_name: Update any access record', $type_params),
        'restrict access' => TRUE,
      ],
      "delete own $type_id access_record" => [
        'title' => $this->t('%type_name: Delete own access record', $type_params),
        'restrict access' => TRUE,
      ],
      "delete $type_id access_record" => [
        'title' => $this->t('%type_name: Delete any access record', $type_params),
        'restrict access' => TRUE,
      ],
      "view $type_id access_record revisions" => [
        'title' => $this->t('%type_name: View access record revisions', $type_params),
        'restrict access' => TRUE,
      ],
      "revert $type_id access_record revisions" => [
        'title' => $this->t('%type_name: Revert access record revisions', $type_params),
        'restrict access' => TRUE,
      ],
      "delete $type_id access_record revisions" => [
        'title' => $this->t('%type_name: Delete access record revisions', $type_params),
        'restrict access' => TRUE,
      ],
    ];
  }

}
