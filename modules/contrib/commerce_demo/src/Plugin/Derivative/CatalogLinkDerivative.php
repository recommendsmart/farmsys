<?php

namespace Drupal\commerce_demo\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CatalogLinkDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new CatalogLinkDerivative object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
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
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $terms = $this->termStorage->loadTree('product_categories', 0, NULL, TRUE);
    foreach ($terms as $term) {
      assert($term instanceof TermInterface);
      if (!$term->isPublished()) {
        continue;
      }
      $parents = $this->termStorage->loadParents($term->id());
      $parent = reset($parents);
      if ($parent && !$parent->isPublished()) {
        continue;
      }

      $menu_link_parent = NULL;
      if ($parent) {
        $menu_link_parent = 'catalog:' . $parent->uuid();
      }

      $this->derivatives[$term->uuid()] = [
          'route_name' => 'entity.taxonomy_term.canonical',
          'route_parameters' => [
            'taxonomy_term' => $term->id(),
          ],
          'title' => $term->label(),
          'expanded' => 1,
          'menu_name' => 'main',
          'parent' => $menu_link_parent,
        ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
