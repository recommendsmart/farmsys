<?php

namespace Drupal\content_as_config\Commands;

use Drupal\content_as_config\Controller\EntityControllerBase;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush command file for importing/exporting content as configuration.
 */
class ContentAsConfigCommands extends DrushCommands {

  /**
   * The DI container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * An array mapping entity-types to their import/export controller class.
   *
   * @var array
   */
  protected $controllerInfo;

  /**
   * Constructor for this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI container.
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct();
    $this->container = $container;
    $this->controllerInfo = $container->get('module_handler')->invokeAll('content_as_config_controllers');
  }

  /**
   * Exports content to configuration.
   *
   * @param string $entity_type
   *   The type of entity to be exported: 'block_content', 'menu_link_content',
   *   'taxonomy_term', or 'feeds_feed'.
   *
   * @command content_as_config:export
   * @aliases cac-export cacx
   *
   * @usage drush content_as_config:export block_content
   */
  public function export(string $entity_type) {
    $controller = $this->getController($entity_type);
    $this->output()->writeln(dt('Exporting @et entities...', ['@et' => $entity_type]));
    $count = $controller->export(['drush' => TRUE]);
    $this->output()->writeln(dt(
      'Successfully exported @count @et entities...',
      ['@et' => $entity_type, '@count' => $count]
    ));
  }

  /**
   * Imports content from configuration.
   *
   * @param string $entity_type
   *   The type of entity to be imported: 'block_content', 'menu_link_content',
   *   'taxonomy_term', or 'feeds_feed'.
   * @param array $options
   *   An associative array of options.
   *
   * @command content_as_config:import
   * @aliases cac-import caci
   *
   * @option style
   *   Which style of import to be used: safe, full, or force.
   * @usage drush content_as_config:import block --style=full
   */
  public function import(string $entity_type, array $options = ['style' => 'safe']) {
    $controller = $this->getController($entity_type);
    $this->output()->writeln(dt('Importing @et entities...', ['@et' => $entity_type]));
    $count = $controller->import(['drush' => TRUE, 'style' => $options['style']]);
    $this->output()->writeln(dt(
      'Successfully imported @count @et entities...',
      ['@et' => $entity_type, '@count' => $count]
    ));
  }

  /**
   * Exports all eligible content to configuration.
   *
   * @command content_as_config:export-all
   * @aliases cac-export-all cacxa
   * @usage drush content_as_config:export-all
   */
  public function exportAll() {
    foreach (array_keys($this->controllerInfo) as $entity_type) {
      $this->export($entity_type);
    }
  }

  /**
   * Imports all eligible content from configuration.
   *
   * @param array $options
   *   An associative array of options.
   *
   * @command content_as_config:import-all
   * @aliases cac-import-all cacia
   * @usage drush content_as_config:import-all --style=full
   */
  public function importAll(array $options = ['style' => 'safe']) {
    foreach (array_keys($this->controllerInfo) as $entity_type) {
      $this->import($entity_type, $options);
    }
  }

  /**
   * Fetches the appropriate controller for the given entity type.
   *
   * @param string $entity_type
   *   The type of entity whose controller is to be fetched.
   *
   * @return \Drupal\content_as_config\Controller\EntityControllerBase
   *   The controller appropriate to the entity.
   *
   * @throws \RuntimeException
   *   Thrown if there is no controller available for the given entity type.
   */
  protected function getController(string $entity_type): EntityControllerBase {
    if (isset($this->controllerInfo[$entity_type])) {
      $class = $this->controllerInfo[$entity_type];
      if ($class instanceof EntityControllerBase) {
        return $class::create($this->container);
      }
    }
    throw new \RuntimeException('No registered import/export controller for entity type "' . $entity_type . '".');
  }

}
