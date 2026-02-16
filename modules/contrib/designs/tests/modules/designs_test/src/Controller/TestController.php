<?php

namespace Drupal\designs_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\designs\DesignSourceManagerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for testing designs.
 */
class TestController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The design source manager.
   *
   * @var \Drupal\designs\DesignSourceManagerInterface
   */
  protected $sourceManager;

  /**
   * TestController constructor.
   *
   * @param \Drupal\designs\DesignSourceManagerInterface $sourceManager
   *   The source manager.
   */
  public function __construct(DesignSourceManagerInterface $sourceManager) {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.design_source'),
    );
  }

  /**
   * Renders a design source.
   *
   * @param string $design_source
   *   The design source.
   *
   * @return array[]
   *   The render array.
   */
  public function render($design_source) {
    return [
      '#prefix' => '<p>Start of the design</p>',
      'design' => [
        '#type' => 'design',
        '#design' => 'library',
        '#configuration' => [
          'settings' => [
            'setting' => [
              'content' => 'token',
              'value' => 'chicken [node:title]',
            ],
          ],
        ],
        'chicken' => [
          '#markup' => 'Chicken Dinner',
        ],
        'sausage' => [
          '#markup' => 'Devilled Sausage',
        ],
        'hamburger' => [
          '#markup' => 'Hamburger',
        ],
        '#context' => [
          'node' => Node::load(1),
        ],
      ],
      '#suffix' => '<p>End of the design</p>',
    ];
  }

}
