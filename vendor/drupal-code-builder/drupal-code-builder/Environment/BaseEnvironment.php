<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Base class for environments.
 *
 * The environment system provides an abstraction layer between Drupal Code
 * Builder and its current environment, e.g., whether we are running as a Drush
 * plugin, a Drupal module, or being loaded as a library, and what major version
 * of Drupal core we are running on. The environment handler takes care of
 * things such as:
 *  - how to output debug data
 *  - how to get the Drupal core version
 *  - how to load an include file with a version suffix
 *  - how to find the hooks data directory.
 * The classes for the execution environment (Drush, Drupal, library) are
 * supported by helper classes for Drupal core version, thus allowing the
 * execution environment to be orthogonal to the major version. All methods on
 * the helper core version object should be access via a wrapper on the main
 * environment class.
 */
abstract class BaseEnvironment implements EnvironmentInterface {

  /**
   * Whether to skip the sanity tests.
   *
   * @see skipSanityCheck()
   */
  protected $skipSanity = FALSE;

  /**
   * Whether to output log data.
   *
   *  - 0 outputs nothing.
   *  - 1 outputs logging data using debug().
   */
  protected $loggingLevel = 0;

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'Serialized';

  /**
   * The storage helper.
   */
  protected $storage;

  /**
   * The path to the hooks directory.
   *
   * Depending on our environment this is either relative to Drupal root or
   * absolute, but in either case it is in a format that other environment
   * methods can use.
   *
   * Initially this only represents a user setting, and is not verified
   * as an existing, writable directory unless the Task's sanity level has
   * requested it.
   */
  protected $hooks_directory;

  /**
   * A helper object for version-specific code.
   *
   * This allows code specific to different major version of Drupal to be
   * orthogonal to the environment, without external systems having to deal
   * with it.
   */
  protected $version_helper;

  /**
   * {@inheritdoc}
   */
  public function setCoreVersionNumber($drupal_core_version) {
    // Get the major version from the core version number.
    list($major_version) = explode('.', $drupal_core_version);

    $helper_class_name = '\DrupalCodeBuilder\Environment\VersionHelper' . $major_version;

    $this->version_helper = new $helper_class_name();

    return $this;
  }

  /**
   * Set the version helper object.
   *
   * Internal. This is used by tests which use their own dummy version helper
   * class rather than set a core version number.
   *
   * @param $version_helper
   *  The version helper object.
   *
   * @return $this
   *  The environment object.
   */
  public function setCoreVersionHelper($version_helper) {
    $this->version_helper = $version_helper;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageLocalClass($storage_class) {
    if ($storage_class) {
      $this->storageType = $storage_class;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDirectory() {
    if (empty($this->hooks_directory)) {
      $this->getHooksDirectorySetting();
    }

    return $this->hooks_directory;
  }

  /**
   * {@inheritdoc}
   */
  public function getHooksDirectory() {
    return $this->getDataDirectory();
  }

  /**
   * Get the hooks directory setting from the environment and set it locally.
   */
  protected function getHooksDirectorySetting() {
    // Set the module folder based on variable.
    $directory = $this->getSetting('data_directory', 'hooks');

    // Run it through version-specific stuff.
    // This basically prepends 'public://' or 'sites/default/files/'.
    $this->version_helper->directoryPath($directory);

    $this->hooks_directory = $directory;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyEnvironment($sanity_level) {
    // Allow the environment to request skipping the sanity checks.
    if ($this->skipSanity) {
      return;
    }

    // Sanity level 'none': nothing to do.
    if ($sanity_level == 'none') {
      return;
    }

    // Read the hooks directory from settings.
    $this->getHooksDirectorySetting();

    // Sanity level 'data_directory_exists':
    if (!file_exists($this->hooks_directory)) {
      try {
        // Try to create the directory if it doesn't exist.
        $this->version_helper->prepareDirectory($this->hooks_directory);
      }
      catch (\Exception $e) {
        // Re-throw a sanity exception.
        throw new \DrupalCodeBuilder\Exception\SanityException('data_directory_exists');
      }
    }

    // This is as far as we need to go for the hooks_directory level.
    if ($sanity_level == 'data_directory_exists') {
      return;
    }

    // Sanity level 'component_data_processed':
    $hooks_processed = $this->hooks_directory . "/hooks_processed.php";
    if (!file_exists($hooks_processed)) {
      throw new \DrupalCodeBuilder\Exception\SanityException('component_data_processed');
    }

    // This is as far as we need to go for the hook_data level.
    if ($sanity_level == 'component_data_processed') {
      return;
    }

    // There are no further sanity levels!
  }

  /**
   * {@inheritdoc}
   */
  public function skipSanityCheck($setting) {
    $this->skipSanity = $setting;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreMajorVersion() {
    return $this->version_helper->getCoreMajorVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    if (!isset($this->storage)) {
      $storage_class_name = '\DrupalCodeBuilder\Storage\\' . $this->storageType;
      $this->storage = new $storage_class_name($this);
    }

    return $this->storage;
  }

  /**
   * Output debug data.
   */
  abstract public function debug($data, $message = '');

  /**
   * Output verbose log data.
   */
  public function log($data, $message = '') {
    if ($this->loggingLevel) {
      $this->debug($data, $message);
    }
  }

  /**
   * Set the logging level.
   *
   * @param $loggingLevel
   *  Either TRUE, to output log data, or FALSE to not output it.
   */
  public function setLoggingLevel($loggingLevel) {
    $this->loggingLevel = $loggingLevel;
  }

  /**
   * {@inheritdoc}
   */
  public function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return $this->version_helper->systemListing($mask, $directory, $key, $min_depth);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeInfoHook() {
    return $this->version_helper->invokeInfoHook();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    return $this->version_helper->getSetting($name, $default);
  }

  /**
   * Get a path to one of our files or folders.
   */
  function getPath($subpath) {
    $base_path = dirname(__FILE__) . '/..';

    $path = $base_path . '/' . $subpath;

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  function getExtensionPath($type, $name) {
    return $this->version_helper->getExtensionPath($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getContainer() {
    return \Drupal::getContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    // TODO: this should be moved to the version helper so that non-D8 versions
    // can use this, but YAGNI.
    return \Drupal::root();
  }

}
