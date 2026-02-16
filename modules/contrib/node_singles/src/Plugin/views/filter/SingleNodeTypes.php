<?php

namespace Drupal\node_singles\Plugin\views\filter;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A views filter for excluding single node types.
 *
 * @ViewsFilter("node_singles")
 */
class SingleNodeTypes extends LimitBundle {

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  protected $singles;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->singles = $container->get('node_singles');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return array_keys($this->singles->getAllSingles());
  }

}
