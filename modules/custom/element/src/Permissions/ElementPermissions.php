<?php

namespace Drupal\element\Permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\element\Entity\ElementType;

/**
 * Provides dynamic permissions for element of different types.
 */
class ElementPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of element type permissions.
   *
   * @return array
   *   The element type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function elementTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (ElementType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of element permissions for a given element type.
   *
   * @param \Drupal\element\Entity\ElementType $type
   *   The element type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ElementType $type) {
    $type_id = $type->id();
    $permissions = [];
    $replacements = ['%type' => $type->label()];

    $permissions[$this->buildPermissionId($type_id, 'create')] = [
      'title' => $this->t('Create %type element', $replacements),
      'description' => $this->t(
        'Allows users to create element of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'view')] = [
      'title' => $this->t('View %type element', $replacements),
      'description' => $this->t(
        'Allows users to view element of type %type. This does not affect whether a user is able to view individual element on their own page.',
        $replacements
      ),
    ];
    $permissions[$this->buildPermissionId($type_id, 'update')] = [
      'title' => $this->t('Edit any %type element', $replacements),
      'description' => $this->t(
        'Allows users to edit any element of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'update own')] = [
      'title' => $this->t('Edit own %type element', $replacements),
      'description' => $this->t(
        'Allows users to edit the element of type %type they created.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'delete')] = [
      'title' => $this->t('Delete any %type element', $replacements),
      'description' => $this->t(
        'Allows users to delete any element of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'delete own')] = [
      'title' => $this->t('Delete own %type element', $replacements),
      'description' => $this->t(
        'Allows users to delete element of type %type they created.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];

    return $permissions;
  }

  /**
   * Produce the permission machine name for an operation and a element type.
   *
   * @param string $type
   *   The machine name of a element type. There is no validity checking on
   *   this.
   * @param string $op
   *   One of 'create', 'view', 'update', 'update own', 'delete' or
   *   'delete own'. (This is not actually checked).
   *
   * @return string
   *   A string used to identify the corresponding permission.
   */
  public static function buildPermissionId($type, $op) {
    return $op . ' ' . $type . ' element';
  }

}
