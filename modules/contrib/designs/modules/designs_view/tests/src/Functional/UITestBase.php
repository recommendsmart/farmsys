<?php

namespace Drupal\Tests\designs_view\Functional;

use Drupal\Tests\designs\Traits\DesignsStandardTrait;
use Drupal\Tests\designs\Traits\DesignsTestTrait;
use Drupal\Tests\views_ui\Functional\UITestBase as ViewsUITestBase;

/**
 * Provides wrapper around default views_ui tests.
 */
abstract class UITestBase extends ViewsUITestBase {

  use DesignsTestTrait;
  use DesignsStandardTrait;

  /**
   * The modules.
   *
   * @var string[]
   */
  protected static $modules = [
    'node',
    'views_ui',
    'block',
    'taxonomy',
    'designs_view',
    'designs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
