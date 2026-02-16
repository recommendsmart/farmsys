<?php

namespace Drupal\designs_view\Plugin\designs\source;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source providing views pager sources.
 *
 * @DesignSource(
 *   id = "views_pager",
 *   label = @Translation("Views pager"),
 *   defaultSources = {
 *     "first",
 *     "previous",
 *     "previous_ellipsis",
 *     "items",
 *     "next_ellipsis",
 *     "next",
 *     "last"
 *   }
 * )
 */
class ViewsPagerSource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * ViewsPagerSource constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PagerManagerInterface $pagerManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pagerManager = $pagerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pager.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    return [
      'first' => $this->t('First'),
      'previous' => $this->t('Previous'),
      'previous_ellipsis' => $this->t('Previous Ellipsis'),
      'items' => $this->t('Items'),
      'next_ellipsis' => $this->t('Next Ellipsis'),
      'next' => $this->t('Next'),
      'last' => $this->t('Last'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    return $this->process($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [
      'view' => $element['#view'],
    ];
  }

  /**
   * Processes the pager element to generate the pager sources.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The pager sources.
   */
  protected function process(array $element) {
    $items = [
      'first' => [],
      'previous' => [],
      'previous_ellipsis' => [],
      'next_ellipsis' => [],
      'next' => [],
      'last' => [],
    ];

    $index = $element['#element'];
    $parameters = $element['#parameters'];
    $quantity = empty($element['#quantity']) ? 1 : $element['#quantity'];
    $route_name = $element['#route_name'];
    $route_parameters = $element['#route_parameters'] ?? [];

    $pager = $this->pagerManager->getPager($index);

    // Nothing to do if there is no pager.
    if (!isset($pager)) {
      return [];
    }

    $pager_max = $pager->getTotalPages();
    if ($pager_max <= 1) {
      return [];
    }

    $tags = $element['#tags'];

    // Calculate various markers within this pager piece:
    // Middle is used to "center" pages around the current page.
    $pager_middle = ceil($quantity / 2);
    $current_page = $pager->getCurrentPage();
    // The current pager is the page we are currently paged to.
    $pager_current = $current_page + 1;
    // The first pager is the first page listed by this pager piece.
    $pager_first = $pager_current - $pager_middle + 1;
    // The last is the last page listed by this pager piece (re quantity).
    $pager_last = $pager_current + $quantity - $pager_middle;

    // Generate the loop limitations.
    $i = $pager_first;
    if ($pager_last > $pager_max) {
      // Adjust "center" if at end of query.
      $i = $i + ($pager_max - $pager_last);
      $pager_last = $pager_max;
    }
    if ($i <= 0) {
      // Adjust "center" if at start of query.
      $pager_last = $pager_last + (1 - $i);
      $i = 1;
    }

    // Create the "first" and "previous" links if we are not on the first page.
    if ($current_page > 0) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters(
          $parameters,
          $index,
          0
        ),
      ];

      // @todo Need some configuration for pager text.
      $items['first'] = [
        '#type' => 'link',
        '#title' => $tags[0] ?? $this->t('First'),
        '#url' => Url::fromRoute($route_name, $route_parameters, $options),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attributes' => [
          'class' => ['pager-first'],
        ],
      ];

      $options = [
        'query' => $this->pagerManager->getUpdatedParameters(
          $parameters,
          $index,
          $current_page - 1
        ),
      ];
      $items['previous'] = [
        '#type' => 'link',
        '#title' => $tags[1] ?? $this->t('Previous'),
        '#url' => Url::fromRoute($route_name, $route_parameters, $options),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attributes' => [
          'class' => ['pager-previous'],
        ],
      ];
    }

    // Add an ellipsis if there are further previous pages.
    if ($i > 1) {
      $items['previous_ellipsis'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => '...',
        '#attributes' => [
          'class' => ['pager-ellipsis', 'pager-ellipsis-previous'],
        ],
      ];
    }

    // Now generate the actual pager piece.
    for (; $i <= $pager_last && $i <= $pager_max; $i++) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters(
          $parameters,
          $index,
          $i - 1
        ),
      ];
      $items['items'][$i] = [
        '#type' => 'link',
        '#title' => $i,
        '#url' => Url::fromRoute($route_name, $route_parameters, $options),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attributes' => [
          'class' => ['pager-page'],
        ],
      ];
      if ($i == $pager_current) {
        $items['items'][$i]['#attributes']['class'][] = 'pager-current';
      }
    }

    // Add an ellipsis if there are further next pages.
    if ($i < $pager_max + 1) {
      $items['next_ellipsis'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => '...',
        '#attributes' => [
          'class' => ['pager-ellipsis', 'pager-ellipsis-next'],
        ],
      ];
    }

    // Create the "next" and "last" links if we are not on the last page.
    if ($current_page < ($pager_max - 1)) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $index, $current_page + 1),
      ];
      $items['next'] = [
        '#type' => 'link',
        '#title' => $tags[3] ?? $this->t('Next'),
        '#url' => Url::fromRoute($route_name, $route_parameters, $options),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attributes' => [
          'class' => ['pager-next'],
        ],
      ];

      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $index, $pager_max - 1),
      ];
      $items['last'] = [
        '#type' => 'link',
        '#title' => $tags[4] ?? $this->t('Last'),
        '#url' => Url::fromRoute($route_name, $route_parameters, $options),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attributes' => [
          'class' => ['pager-last'],
        ],
      ];
    }

    return $items;
  }

}
