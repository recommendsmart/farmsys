<?php

namespace Drupal\commerceg_profile\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides per profile type definitions of the group content enabler plugin.
 */
class ProfileDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The profile type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $profileTypeStorage;

  /**
   * Constructs a new ProfileDeriver object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $profile_type_storage
   *   The profile type storage.
   */
  public function __construct(
    ConfigEntityStorageInterface $profile_type_storage
  ) {
    $this->profileTypeStorage = $profile_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $base_plugin_id
  ) {
    $profile_type_storage = $container
      ->get('entity_type.manager')
      ->getStorage('profile_type');

    return new static($profile_type_storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $profile_types = $this->profileTypeStorage->loadMultiple();

    foreach ($profile_types as $name => $profile_type) {
      $label = $profile_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group profile (@type)', ['@type' => $label]),
        'description' => t(
          'Adds %type profiles to groups.',
          ['%type' => $label]
        ),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
