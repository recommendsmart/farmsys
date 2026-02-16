<?php

namespace Drupal\designs\Discovery;

use Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Provides discovery using directories.
 */
class YamlDirectoryDiscoveryDecorator extends YamlDirectoryDiscovery {

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * The base extension directories.
   *
   * @var string[]
   */
  protected $extensions;

  /**
   * Constructs a YamlDiscoveryDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The discovery object that is being decorated.
   * @param string $name
   *   The file name suffix to use for discovery; for instance, 'test' will
   *   become 'MODULE.test.yml'.
   * @param array $directories
   *   An array of directories to scan.
   * @param array $extensions
   *   The base directories for each extension.
   */
  public function __construct(DiscoveryInterface $decorated, $name, array $directories, array $extensions) {
    parent::__construct($directories, $name);
    $this->decorated = $decorated;
    $this->extensions = $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as &$definition) {
      $path = $definition['path'] ?? '';
      $path = $path ? "{$path}/" : '';
      $file_path = dirname($definition['_discovered_file_path']);
      $ext_path = $this->extensions[$definition['provider']];
      $definition['path'] = $path . substr($file_path, strlen($ext_path) + 1);
    }
    return $definitions + $this->decorated->getDefinitions();
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->decorated, $method], $args);
  }

}
