<?php

declare(strict_types=1);

namespace Drupal\entity_extra_field_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define menu links task derivative.
 */
class MenuLinksTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Menu links task constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $links = parent::getDerivativeDefinitions($base_plugin_definition);

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      $base_route_name = $definition->get('field_ui_base_route');

      if (!isset($base_route_name)) {
        continue;
      }
      $links["{$entity_type_id}.extra_fields"] = [
        'title' => $this->t('Manage extra fields'),
        'route_name' => "entity.{$entity_type_id}.extra_fields",
        'weight' => 2,
        'base_route' => $base_route_name,
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
