<?php

namespace Drupal\properties_field\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for label related routes.
 */
class LabelController extends ControllerBase {

  /**
   * Provides the label autocomplete suggestions.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The autocomplete suggestions.
   */
  public function autocomplete($entity_type_id, $bundle, $field_name, Request $request) {
    $string = $request->query->get('q', '');

    if (!is_string($string) || $string === '') {
      return new JsonResponse([]);
    }

    $entity_type_manager = $this->entityTypeManager();
    $entity_storage = $entity_type_manager->getStorage($entity_type_id);

    $query = $entity_storage->getQuery()
      ->condition($field_name . '.label', $string, 'STARTS_WITH')
      ->range(0, 10);

    $bundle_key = $entity_type_manager->getDefinition($entity_type_id)->getKey('bundle');

    if ($bundle_key !== FALSE) {
      $query->condition($bundle_key, $bundle);
    }

    if (!$entity_ids = $query->execute()) {
      return new JsonResponse([]);
    }

    $results = [];
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    foreach ($entity_storage->loadMultiple($entity_ids) as $entity) {
      foreach ($entity->get($field_name) as $item) {
        if (stripos($item->label, $string) !== 0) {
          continue;
        }

        $results[] = [
          'value' => $item->machine_name,
          'label' => $item->label,
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Check access for the label autocomplete route.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field name.
   * @param string $entity_id
   *   The entity ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function autocompleteAccess($entity_type_id, $bundle, $field_name, $entity_id, AccountInterface $account) {
    $entity_type_manager = $this->entityTypeManager();

    // Ensure the entity type exists.
    if (!$entity_type_manager->hasDefinition($entity_type_id)) {
      return AccessResult::forbidden();
    }

    if ($entity_id !== '0') {
      // Load the specified entity.
      $entity = $entity_type_manager
        ->getStorage($entity_type_id)
        ->load($entity_id);

      if (!$entity) {
        return AccessResult::forbidden();
      }

      if ($entity->bundle() !== $bundle) {
        return AccessResult::forbidden();
      }
    }
    else {
      // Create a dummy entity.
      $bundle_key = $entity_type_manager->getDefinition($entity_type_id)->getKey('bundle');

      $values = [];
      if ($bundle_key !== FALSE) {
        $values[$bundle_key] = $bundle;
      }

      $entity = $entity_type_manager->getStorage($entity_type_id)->create($values);
    }

    // Check the field specifications.
    if (!$entity instanceof FieldableEntityInterface) {
      return AccessResult::forbidden();
    }

    if (!$entity->hasField($field_name)) {
      return AccessResult::forbidden();
    }

    if ($entity->getFieldDefinition($field_name)->getType() !== 'properties') {
      return AccessResult::forbidden();
    }

    // Check if $account may create or update the entity.
    if ($entity_type_manager->hasHandler($entity_type_id, 'access')) {
      $access_handler = $entity_type_manager->getAccessControlHandler($entity_type_id);

      if ($entity->isNew()) {
        return $access_handler->createAccess($bundle, $account, [], TRUE);
      }

      return $access_handler->access($entity, 'update', $account, TRUE);
    }

    return AccessResult::neutral();
  }

}
