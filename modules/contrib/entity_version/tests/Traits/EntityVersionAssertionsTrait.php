<?php

namespace Drupal\Tests\entity_version\Traits;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Testing trait for Entity Version.
 */
trait EntityVersionAssertionsTrait {

  /**
   * Assert the entity version field value.
   *
   * The trait assumes the field name is "field_version".
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param int $major
   *   The major version number.
   * @param int $minor
   *   The minor version number.
   * @param int $patch
   *   The patch version number.
   * @param string $message
   *   An option error message.
   */
  protected function assertEntityVersion(ContentEntityInterface $entity, int $major, int $minor, int $patch, $message = ''): void {
    $this->assertEquals($major, $entity->get('field_version')->major, $message);
    $this->assertEquals($minor, $entity->get('field_version')->minor, $message);
    $this->assertEquals($patch, $entity->get('field_version')->patch, $message);
  }

}
