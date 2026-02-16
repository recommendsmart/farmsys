<?php

namespace Drupal\entity_inherit\EntityInheritPlugin;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface;

/**
 * Abstraction around a collection of plugins.
 */
class EntityInheritPluginCollection implements EntityInheritPluginInterface, \Countable {

  use StringTranslationTrait;

  /**
   * The global app.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructs a new WWatchdogPluginCollection object.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The injected global app.
   */
  public function __construct(EntityInherit $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFields(array &$field_names, EntityInherit $app) {
    $this->callOnPlugins('alterFields', [
      &$field_names,
      $app,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function filterFields(array &$field_names, array $original, string $category, EntityInherit $app) {
    $this->callOnPlugins('filterFields', [
      &$field_names,
      $original,
      $category,
      $app,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function presave(EntityInheritEntitySingleInterface $entity, EntityInherit $app) {
    $this->callOnPlugins('presave', [$entity, $app]);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->plugins());
  }

  /**
   * Call a method on all plugins, display errors if exceptions occur.
   *
   * @param string $method
   *   The method to call.
   * @param array $arguments
   *   Arguments to pass, for example [&$info].
   */
  protected function callOnPlugins(string $method, array $arguments = []) {
    foreach ($this->plugins() as $plugin) {
      try {
        call_user_func_array([$plugin, $method], $arguments);
      }
      catch (\Throwable $t) {
        $this->displayErrorToUser($t);
      }
    }
  }

  /**
   * Display a \Throwable to the user.
   *
   * @param \Throwable $throwable
   *   A \Throwable.
   */
  public function displayErrorToUser(\Throwable $throwable) {
    $this->app->getMessenger()->addError($this->t('%type: @message in %function (line %line of %file).', Error::decodeException($throwable)));
  }

  /**
   * Get the injected global app.
   *
   * @return \Drupal\entity_inherit\EntityInherit
   *   The global app.
   */
  public function getApp() : EntityInherit {
    return $this->app;
  }

  /**
   * Get plugin objects.
   *
   * @param bool $reset
   *   Whether to re-fetch plugins; otherwise we use the static variable.
   *   This can be useful during testing.
   *
   * @return array
   *   Array of plugin objects.
   *
   * @throws \Exception
   */
  public function plugins(bool $reset = FALSE) : array {
    static $return = NULL;

    if ($return === NULL || $reset) {
      $return = [];
      foreach (array_keys($this->pluginDefinitions()) as $plugin_id) {
        $return[$plugin_id] = $this->getApp()->getPluginManager()->createInstance($plugin_id, ['of' => 'configuration values']);
      }
    }

    return $return;
  }

  /**
   * Get plugin definitions based on their annotations.
   *
   * @return array
   *   Array of plugin definitions.
   *
   * @throws \Exception
   */
  public function pluginDefinitions() : array {
    $return = $this->getApp()->getPluginManager()->getDefinitions();

    uasort($return, function (array $a, array $b) : int {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    return $return;
  }

}
