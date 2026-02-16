<?php

namespace Drupal\field_bundle;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field bundle permissions builder.
 */
class FieldBundlePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FieldBundlePermissions constructor.
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
   * Returns an array of field bundle permissions.
   *
   * @return array
   *   The field bundle permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getFieldBundlePermissions() {
    $perms = [];
    // Generate permissions for all field bundle configurations.
    $bundle_configs = $this->entityTypeManager
      ->getStorage('field_bundle_config')->loadMultiple();
    foreach ($bundle_configs as $config) {
      $perms += $this->buildPermissions($config);
    }
    return $perms;
  }

  /**
   * Returns a list of permissions for a given field bundle config.
   *
   * @param \Drupal\field_bundle\FieldBundleConfigInterface $config
   *   The field bundle config.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(FieldBundleConfigInterface $config) {
    $config_id = $config->id();
    $config_params = ['%config_name' => $config->label()];

    return [
      "create $config_id field_bundle" => [
        'title' => $this->t('%config_name: Create new field bundle', $config_params),
      ],
      "view own $config_id field_bundle" => [
        'title' => $this->t('%config_name: View own field bundle', $config_params),
      ],
      "view any $config_id field_bundle" => [
        'title' => $this->t('%config_name: View all field bundles', $config_params),
        'restrict access' => TRUE,
      ],
      "update own $config_id field_bundle" => [
        'title' => $this->t('%config_name: Update own field bundle', $config_params),
      ],
      "update any $config_id field_bundle" => [
        'title' => $this->t('%config_name: Update any field bundle', $config_params),
        'restrict access' => TRUE,
      ],
      "delete own $config_id field_bundle" => [
        'title' => $this->t('%config_name: Delete own field bundle', $config_params),
      ],
      "delete any $config_id field_bundle" => [
        'title' => $this->t('%config_name: Delete any field bundle', $config_params),
        'restrict access' => TRUE,
      ],
      "view $config_id field_bundle revisions" => [
        'title' => $this->t('%config_name: View field bundle revisions', $config_params),
      ],
      "revert $config_id field_bundle revisions" => [
        'title' => $this->t('%config_name: Revert field bundle revisions', $config_params),
        'restrict access' => TRUE,
      ],
      "delete $config_id field_bundle revisions" => [
        'title' => $this->t('%config_name: Delete field bundle revisions', $config_params),
        'restrict access' => TRUE,
      ],
    ];
  }

}
