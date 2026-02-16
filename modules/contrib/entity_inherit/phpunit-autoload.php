<?php

/**
 * @file
 * PHPUnit class autoloader.
 *
 * PHPUnit knows nothing about Drupal, so provide PHPUnit with the bare
 * minimum it needs to know in order to find classes by namespace.
 *
 * Used by the PHPUnit test runner and referenced in ./phpunit.xml.
 *
 * See also https://blog.dcycle.com/unit
 */

spl_autoload_register(function ($class) {
  $custom_code = [
    'src' => ['entity_inherit'],
    'modules/entity_inherit_details/src' => ['entity_inherit_details'],
    'tests/src/Unit' => [
      'Tests',
      'entity_inherit',
      'Unit',
    ],
  ];

  require_once 'phpunit-bootstrap.php';

  foreach ($custom_code as $dir => $namespace) {
    if (substr($class, 0, strlen('Drupal\\' . implode('\\', $namespace) . '\\')) == 'Drupal\\' . implode('\\', $namespace) . '\\') {
      $class2 = preg_replace('/^Drupal\\\\' . implode('\\\\', $namespace) . '\\\\/', '', $class);
      $path = $dir . '/' . str_replace('\\', '/', $class2) . '.php';
      require_once $path;
    }
  }
});
