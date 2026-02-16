<?php

namespace Drupal\yasm_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\yasm\Services\GroupsStatisticsInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'YASM groups counts' Block.
 *
 * @Block(
 *   id = "yasm_block_group",
 *   admin_label = @Translation("YASM groups counts"),
 *   category = @Translation("YASM"),
 * )
 */
class GroupBlock extends YasmBlock implements ContainerFactoryPluginInterface {

  const GROUP_FROM_ROUTE = '_route_';

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entities statistics service.
   *
   * @var \Drupal\yasm\Services\GroupsStatisticsInterface
   */
  protected $groupsStatistics;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->moduleHandler->moduleExists('group')) {
      $config = $this->getConfiguration();

      if (empty($config['groups']) || GroupBlock::GROUP_FROM_ROUTE === array_key_first($config['groups'])) {
        $groups = [$this->getGroupFromRoute()];
        $routeGroup = TRUE;
      }
      else {
        $groups = $this->entityTypeManager->getStorage('group')->loadMultiple($config['groups']);
        $routeGroup = FALSE;
      }

      if (!empty($groups)) {
        $with_icons = isset($config['with_icons']) ? $config['with_icons'] : TRUE;

        if (isset($config['block_style']) && 'cards' === $config['block_style']) {
          $build = $this->buildBlockColumns($this->getGroupCards($groups, $with_icons));
        }
        elseif (isset($config['block_style']) && 'counters' === $config['block_style']) {
          $build = $this->buildBlockColumns($this->getGroupCards($groups, $with_icons));
          $build['#attributes']['class'][] = 'yasm-counters';
          $build['#attached']['library'][] = 'yasm_blocks/counters';
        }
        else {
          $build = [
            '#theme' => 'item_list',
            '#list_type' => 'ul',
            '#items' => $this->getGroupCards($groups, $with_icons, TRUE),
          ];
        }

        if (!empty($config['with_icons']) && !empty($config['attach_fontawesome'])) {
          $build['#attached']['library'][] = 'yasm/fontawesome';
        }

        return [
          '#theme' => 'yasm_wrapper',
          '#content' => $build,
          '#attributes' => [
            'class' => ['yasm-block', 'yasm-block-group'],
          ],
          '#cache' => [
            'contexts' => $routeGroup ? ['languages', 'route.group'] : ['languages'],
            'max-age'  => 3600,
          ],
        ];
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  private function getGroupCards(array $groups, $with_icons = TRUE, $list = FALSE) {
    $cards = [];

    // Group contents.
    $label = $this->t('Contents');
    $count = 0;
    foreach ($groups as $group) {
      $count += $this->groupsStatistics->countContents($group);
    }
    $picto = $with_icons ? 'far fa-file-alt' : '';

    $cards[] = $this->buildBlockItem($label, $count, $picto, $list);

    // Group members.
    $label = $this->t('Members');
    $count = 0;
    foreach ($groups as $group) {
      $count += $this->groupsStatistics->countMembers($group);
    }
    $picto = $with_icons ? 'fas fa-user' : '';

    $cards[] = $this->buildBlockItem($label, $count, $picto, $list);

    return $cards;
  }

  /**
   * Get group from current route.
   */
  private function getGroupFromRoute() {
    if ($group = $this->routeMatch->getParameters()->get('group')) {
      if ($group instanceof Group) {
        return $group;
      }

      if (is_numeric($group)) {
        if ($group = $this->entityTypeManager->getStorage('group')->load($group)) {
          return $group;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('group')) {
      $config = $this->getConfiguration();
      $groups = [GroupBlock::GROUP_FROM_ROUTE => '- ' . $this->t('Group from route') . ' -'];

      if ($userGroups = $this->groupMembershipLoader->loadByUser($this->currentUser)) {
        if (!empty($userGroups)) {
          foreach ($userGroups as $group) {
            if ($gr = $group->getGroup()) {
              $groups[$gr->id()] = $gr->label();
            }
          }
        }
      }

      $form['groups'] = [
        '#type'     => 'select',
        '#title'    => $this->t('Groups'),
        '#required' => TRUE,
        '#multiple' => TRUE,
        '#options'  => $groups,
        '#default_value' => !empty($config['groups']) ? $config['groups'] : GroupBlock::GROUP_FROM_ROUTE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    if ($this->moduleHandler->moduleExists('group')) {
      $values = $form_state->getValues();

      $this->configuration['groups'] = $values['groups'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $route_match,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $module_handler,
    YasmBuilderInterface $yasm_builder,
    GroupsStatisticsInterface $groups_statistics
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $yasm_builder);

    $this->moduleHandler     = $module_handler;
    $this->groupsStatistics  = $groups_statistics;
    $this->routeMatch        = $route_match;
    $this->currentUser       = $current_user;
    $this->entityTypeManager = $entityTypeManager;

    // Conditional dependency injection is not working. Remove this when works.
    if ($this->moduleHandler->moduleExists('group')) {
      $this->setGroupMembershipLoader(\Drupal::service('group.membership_loader'));
    }
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
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('yasm.builder'),
      $container->get('yasm.groups_statistics')
    );
  }

  /**
   * Set group membership service for conditional depdendency injection.
   */
  public function setGroupMembershipLoader(GroupMembershipLoaderInterface $group_membership_loader) {
    $this->groupMembershipLoader = $group_membership_loader;
  }

}
