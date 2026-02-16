<?php

namespace Drupal\yasm_blocks\Plugin\Block;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'YASM current user counts' Block.
 *
 * @Block(
 *   id = "yasm_block_user",
 *   admin_label = @Translation("YASM current user counts"),
 *   category = @Translation("YASM"),
 * )
 */
class UserBlock extends YasmBlock implements ContainerFactoryPluginInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
      $build = $this->buildBlockColumns($this->getUserCards($with_icons));
    }
    elseif (isset($config['block_style']) && 'counters' === $config['block_style']) {
      $build = $this->buildBlockColumns($this->getUserCards($with_icons));
      $build['#attributes']['class'][] = 'yasm-counters';
      $build['#attached']['library'][] = 'yasm_blocks/counters';
    }
    else {
      $build = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->getUserCards($with_icons, TRUE),
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
        'contexts' => ['languages', 'user'],
        'max-age'  => 3600,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getUserCards($with_icons = TRUE, $list = FALSE) {
    $cards = [];
    $conditions = ['uid' => $this->currentUser->id()];

    if ($this->moduleHandler->moduleExists('node')) {
      $label = $this->t('My contents');
      $count = $this->entitiesStatistics->count('node', $conditions);
      $picto = $with_icons ? 'far fa-file-alt' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('comment')) {
      $label = $this->t('My comments');
      $count = $this->entitiesStatistics->count('comment', $conditions);
      $picto = $with_icons ? 'fas fa-comment' : '';

      $cards[] = $this->buildBlockItem($label, $count, $picto, $list);
    }
    if ($this->moduleHandler->moduleExists('file')) {
      $label = $this->t('My files');
      $count = $this->entitiesStatistics->count('file', $conditions);
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
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    EntitiesStatisticsInterface $entities_statistics,
    YasmBuilderInterface $yasm_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $yasm_builder);

    $this->currentUser        = $current_user;
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
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('yasm.entities_statistics'),
      $container->get('yasm.builder')
    );
  }

}
