<?php

namespace Drupal\flow\Plugin\flow\Derivative;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Flow-related plugins derived by content entity types.
 */
abstract class ContentDeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * A statically cached list of derivative definitions.
   *
   * @var array
   */
  protected static ?array $derivatives;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Creates a new ContentDerivativeBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset(self::$derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    return isset(self::$derivatives[$derivative_id]) ? self::$derivatives[$derivative_id] + $base_plugin_definition : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset(self::$derivatives)) {
      self::$derivatives = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
        if (!($entity_type->entityClassImplements(ContentEntityInterface::class)) || !$entity_type->hasKey('uuid')) {
          continue;
        }
        foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle => $info) {
          $bundle_label = $info['label'] instanceof TranslatableMarkup ? $info['label'] : new TranslatableMarkup($info['label']);
          $plugin_label = $entity_type->getBundleEntityType() ? $this->t('@bundle item (@type)', [
            '@bundle' => $bundle_label,
            '@type' => $entity_type->getLabel(),
          ]) : $this->t('@type item', ['@type' => $bundle_label]);
          self::$derivatives[$entity_type_id . '.' . $bundle] = [
            'label' => $plugin_label,
            'entity_type' => $entity_type_id,
            'bundle' => $bundle,
          ];
        }
      }
    }

    $derivatives = self::$derivatives;
    foreach ($derivatives as &$item) {
      $item += $base_plugin_definition;
    }

    return $derivatives;
  }

}
