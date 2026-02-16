<?php

namespace Drupal\yasm_blocks\Plugin\Block;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'YASM site counts' Block.
 *
 * @Block(
 *   id = "yasm_block_site",
 *   admin_label = @Translation("YASM site counts"),
 *   category = @Translation("YASM"),
 * )
 */
class SiteBlock extends YasmBlock implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entities statistics service.
   *
   * @var \Drupal\yasm\Services\EntitiesStatisticsInterface
   */
  protected $entitiesStatistics;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $with_icons = isset($config['with_icons']) ? $config['with_icons'] : TRUE;

    if (isset($config['block_style']) && 'cards' === $config['block_style']) {
      $build = $this->buildBlockColumns($this->getSiteCards($with_icons));
    }
    elseif (isset($config['block_style']) && 'counters' === $config['block_style']) {
      $build = $this->buildBlockColumns($this->getSiteCards($with_icons));
      $build['#attributes']['class'][] = 'yasm-counters';
      $build['#attached']['library'][] = 'yasm_blocks/counters';
    }
    else {
      $build = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->getSiteCards($with_icons, TRUE),
      ];
    }

    if (!empty($config['with_icons']) && !empty($config['attach_fontawesome'])) {
      $build['#attached']['library'][] = 'yasm/fontawesome';
    }

    return [
      '#theme' => 'yasm_wrapper',
      '#content' => $build,
      '#attributes' => [
        'class' => ['yasm-block', 'yasm-block-site'],
      ],
      '#cache' => [
        'contexts' => ['languages'],
        'max-age'  => 3600,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getSiteCards($with_icons = TRUE, $list = FALSE) {
    $cards = [];

    if ($this->moduleHandler->moduleExists('node')) {
      $label = $this->t('Contents');
      $count = $this->entitiesStatistics->count('node');
      $picto = $with_icons ? 'far fa-file-alt' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('comment')) {
      $label = $this->t('Comments');
      $count = $this->entitiesStatistics->count('comment');
      $picto = $with_icons ? 'fas fa-comment' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('user')) {
      $label = $this->t('Users');
      $count = $this->entitiesStatistics->count('user');
      $picto = $with_icons ? 'fas fa-user' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('group')) {
      $label = $this->t('Groups');
      $count = $this->entitiesStatistics->count('group');
      $picto = $with_icons ? 'fas fa-users' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('file')) {
      $label = $this->t('Files');
      $count = $this->entitiesStatistics->count('file');
      $picto = $with_icons ? 'far fa-file' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }

    return $cards;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    EntitiesStatisticsInterface $entities_statistics,
    YasmBuilderInterface $yasm_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $yasm_builder);

    $this->moduleHandler      = $module_handler;
    $this->entitiesStatistics = $entities_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('yasm.entities_statistics'),
      $container->get('yasm.builder')
    );
  }

}
