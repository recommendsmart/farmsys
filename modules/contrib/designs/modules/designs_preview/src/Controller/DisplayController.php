<?php

namespace Drupal\designs_preview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Link;
use Drupal\designs\DesignManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Displays the design for a theme.
 */
class DisplayController extends ControllerBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The pattern plugins.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * Constructs a new DisplayController instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, DesignManagerInterface $designManager) {
    $this->themeHandler = $theme_handler;
    $this->designManager = $designManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('plugin.manager.design')
    );
  }

  /**
   * Display the design for the current theme.
   *
   * @return array
   *   The pattern markup.
   */
  public function current() {
    return $this->theme($this->themeHandler->getDefault());
  }

  /**
   * Display the design for a theme.
   *
   * @param string $theme
   *   The theme.
   *
   * @return array
   *   The pattern markup.
   */
  public function theme($theme) {
    if (!$this->themeHandler->hasUi($theme)) {
      throw new NotFoundHttpException();
    }

    $output = [];

    /** @var \Drupal\designs\DesignDefinition $definition */
    foreach ($this->designManager->getSortedDefinitions() as $definition) {
      $category = (string) $definition->getCategory();
      if (!isset($output[$category])) {
        $output[$category] = [
          '#type' => 'details',
          '#title' => $category,
          '#open' => TRUE,
          'items' => [
            '#theme' => 'item_list',
          ],
        ];
      }

      // Generate a link for the design.
      $output[$category]['items']['#items'][] = Link::createFromRoute(
        $definition->getLabel(),
        'designs_preview.design_display',
        [
          'theme' => $theme,
          'design' => $definition->id(),
        ]
      );
    }

    return $output;
  }

  /**
   * Get the previews available for the design.
   *
   * @param string $theme
   *   The theme key.
   * @param string $design
   *   The design plugin identifier.
   *
   * @return array
   *   The render array of design examples.
   */
  public function design($theme, $design) {
    if (!$this->themeHandler->hasUi($theme)) {
      throw new NotFoundHttpException();
    }
    if (!$this->designManager->hasDefinition($design)) {
      throw new NotFoundHttpException();
    }
    $definition = $this->designManager->getDefinition($design);
    return $definition->getPreviews();
  }

}
