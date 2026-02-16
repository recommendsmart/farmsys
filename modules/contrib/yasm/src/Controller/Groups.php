<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\GroupMembership;
use Drupal\group\Entity\Group;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\GroupsStatisticsInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * YASM Statistics site groups controller.
 */
class Groups extends ControllerBase {

  /**
   * The yasm builder service.
   *
   * @var \Drupal\yasm\Services\YasmBuilderInterface
   */
  protected $yasmBuilder;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
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
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The datatables service.
   *
   * @var \Drupal\yasm\Services\DatatablesInterface
   */
  protected $datatables;

  /**
   * The entities statistics service.
   *
   * @var \Drupal\yasm\Services\GroupsStatisticsInterface
   */
  protected $groupsStatistics;

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return ($this->moduleHandler->moduleExists('group')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Site groups page output.
   */
  public function siteContent() {
    // Get all groups.
    $site_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();

    // Build array content output.
    $build = [];
    $build[] = $this->yasmBuilder->title($this->t('Groups contents'), 'far file-alt');
    $build[] = $this->tableGroupsContents($this->getGroupsContents($site_groups));

    $build[] = $this->yasmBuilder->title($this->t('Groups members'), 'fas fa-users');
    $build[] = $this->tableGroupsMembers($this->getGroupsMembers($site_groups));

    return $this->buildContents($build);
  }

  /**
   * My groups page output.
   */
  public function myContent(Request $request) {
    $this->messenger->addMessage($this->t('Statistics filtered with your groups membership: @name.', [
      '@name' => $this->currentUser->getDisplayName(),
    ]));

    $gid = $request->query->get('gid');

    $build = [];
    $build['tabs'] = $this->buildGroupsTabs($gid);
    // Add user cache context because this can change for every user.
    $build['#cache']['contexts'] = ['user'];

    // My groups 'All' page.
    if (empty($gid)) {
      $cards = [];
      $count = $this->groupsStatistics->countGroupsByUser($this->currentUser);
      $cards[] = $this->yasmBuilder->card('fas fa-users', $this->t('My groups'), $count);

      $build['data'] = $this->yasmBuilder->columns($cards, [
        'yasm-groups',
        'yasm-highlight',
      ]);

      return $this->buildContents($build);
    }

    // One 'Group' page.
    $group = $this->entityTypeManager->getStorage('group')->load($gid);
    if ($group && $group->getMember($this->currentUser)) {
      $groupContents = $this->getGroupsContents([$group]);
      $groupContents = reset($groupContents);

      $groupMembers = $this->getGroupsMembers([$group]);
      $groupMembers = reset($groupMembers);

      // Build content output.
      $cards = [];

      $cards[] = $this->yasmBuilder->card('far fa-file-alt', $this->t('Contents'), $groupContents['contents']);
      $cards[] = $this->yasmBuilder->card('fas fa-user', $this->t('Members'), $groupMembers['members']);

      $cards[] = $this->tableGroupContents($groupContents);
      $cards[] = $this->tableGroupMembers($groupMembers);
      $cards[] = $this->tableGroupMembersByDomain($group, $groupMembers['members']);
      $cards[] = $this->tableGroupUsersByAccess($group, $groupMembers['members']);

      $build['data'] = [
        $this->yasmBuilder->columns($cards, ['yasm-groups'], 2),
        $this->yasmBuilder->columns([$this->tableGroupMonthlyCreated($group)], ['yasm-groups'], 1),
      ];

      return $this->buildContents($build);
    }

    return ['#markup' => $this->t('No groups membership found.')];
  }

  /**
   * Build output attaching libraris and cache settings.
   */
  private function buildContents($build) {
    return [
      '#theme' => 'yasm_wrapper',
      '#content' => $build,
      '#attached' => [
        'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
        'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
      ],
      '#cache' => ['max-age' => 3600],
    ];
  }

  /**
   * Build groups contents table.
   */
  private function tableGroupsContents($groupsContents) {
    // Get all content types.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    // Create groups content table.
    $rows = [];
    foreach ($groupsContents as $group) {
      $row = [
        'type'     => $group['type'],
        'name'     => $group['name'],
        'contents' => $group['contents'],
      ];
      // List all content types.
      foreach ($content_types as $key => $value) {
        $row[$key] = isset($group['by_type'][$key]) ? $group['by_type'][$key]['count'] : 0;
      }
      $rows[] = $row;
    }

    $labels = [
      $this->t('Type'),
      $this->t('Group'),
      $this->t('Contents'),
    ];
    // All content types labels.
    foreach ($content_types as $content_type) {
      $labels[] = $content_type->label();
    }

    return $this->yasmBuilder->table($labels, $rows, 'groups_contents');
  }

  /**
   * Build single group contents table.
   */
  private function tableGroupContents($groupContents) {
    if (!empty($groupContents['by_type'])) {
      $rows = [];
      $rows[] = [
        'data'  => [$this->t('Total contents'), $groupContents['contents']],
        'class' => ['total-row'],
      ];
      foreach ($groupContents['by_type'] as $value) {
        $rows[] = [$value['type'], $value['count']];
      }

      return [
        $this->yasmBuilder->title($this->t('Contents by type'), 'far fa-file-alt'),
        $this->yasmBuilder->table([$this->t('Type'), $this->t('Count')], $rows, 'my_groups_contents'),
      ];
    }

    return [];
  }

  /**
   * Build groups members table.
   */
  private function tableGroupsMembers($groupsMembers) {
    $groupsRoles = $this->groupsStatistics->getGroupRoles();
    $rows = [];
    foreach ($groupsMembers as $group) {
      $row = [
        'type'    => $group['type'],
        'name'    => $group['name'],
        'members' => $group['members'],
      ];
      // List all group roles.
      foreach ($groupsRoles as $key => $value) {
        $row[] = isset($group['by_role'][$key]) ? $group['by_role'][$key]['count'] : 0;
      }
      $rows[] = $row;
    }
    $labels = [
      $this->t('Type'),
      $this->t('Group'),
      $this->t('Members'),
    ];
    // All group roles labels.
    foreach ($groupsRoles as $role) {
      $labels[] = $role;
    }

    return $this->yasmBuilder->table($labels, $rows, 'groups_members');
  }

  /**
   * Build single group members table.
   */
  private function tableGroupMembers($groupMembers) {
    if (!empty($groupMembers['by_role'])) {
      $rows = [];
      $rows[] = [
        'data'  => [$this->t('Members'), $groupMembers['members']],
        'class' => ['total-row'],
      ];
      foreach ($groupMembers['by_role'] as $value) {
        $rows[] = [$value['role'], $value['count']];
      }

      return [
        $this->yasmBuilder->title($this->t('Members by group role'), 'fas fa-user'),
        $this->yasmBuilder->table([$this->t('Role'), $this->t('Count')], $rows, 'my_groups_roles'),
      ];
    }

    return [];
  }

  /**
   * Build single group members table.
   */
  private function tableGroupMembersByDomain($group, int $userCount) {
    if ($userCount > 0) {
      $count_by_domain = $this->groupsStatistics->countUsersByEmailDomain($group);

      $rows = [];
      foreach ($count_by_domain as $value => $count) {
        $rows[] = [
          $value,
          $count,
          round($count * 100 / $userCount, 2) . '%',
        ];
      }

      return [
        $this->yasmBuilder->title($this->t('Members by mail domain'), 'fas fa-home'),
        $this->yasmBuilder->table([
          $this->t('Domain'),
          $this->t('Count'),
          $this->t('Percentage'),
        ], $rows, 'my_groups_users_domain'),
      ];
    }

    return [];
  }

  /**
   * Build users by access table.
   */
  private function tableGroupUsersByAccess($group, int $userCount) {
    $count_by_access = $this->groupsStatistics->countMembersByAccess($group);

    $access = [
      'never' => $this->t('Never'),
      '1d'    => $this->t('Today'),
      '1w'    => $this->t('Last week'),
      '1m'    => $this->t('Last month'),
      '1y'    => $this->t('Last year'),
      '3y'    => $this->t('Last three years'),
    ];

    $rows = [];
    foreach ($access as $key => $name) {
      if (isset($count_by_access[$key])) {
        $rows[] = [
          $name,
          $count_by_access[$key],
          round($count_by_access[$key] * 100 / $userCount, 2) . '%',
        ];
      }
    }

    return [
      $this->yasmBuilder->title($this->t('Members by last access'), 'fas fa-door-open'),
      $this->yasmBuilder->table([
        $this->t('Access'),
        $this->t('Count'),
        $this->t('Percentage'),
      ], $rows, 'my_groups_users_access'),
    ];
  }

  /**
   * Build users by access table.
   */
  private function tableGroupMonthlyCreated($group) {
    $lastMonths = $this->yasmBuilder->getLastMonths();

    $rows = $labels = $types = [];
    foreach ($lastMonths as $date) {
      $labels[] = $date['label'];
      $bundles[$date['label']] = $this->groupsStatistics->countContentsByBundle($group, $date['min'], $date['max']);

      // Get a list with all content types with content.
      if (!empty($bundles[$date['label']])) {
        foreach ($bundles[$date['label']] as $key => $bundle) {
          if (isset($bundle['type'])) {
            $types[$key] = $bundle['type'];
          }
        }
      }
    }

    // Show a table with only content types with data.
    if (!empty($types)) {
      $rows = [];
      foreach ($types as $key => $label) {
        $row = [];

        $row[] = $label;
        foreach ($lastMonths as $date) {
          $row[] = isset($bundles[$date['label']][$key]['count']) ? $bundles[$date['label']][$key]['count'] : 0;
        }
        $rows[] = $row;
      }
    }

    $title = $this->yasmBuilder->title($this->t('Content monthly created'), 'far fa-file-alt');

    if (!empty($types)) {
      return [
        $title,
        $this->yasmBuilder->table(array_merge([$this->t('Created')], $labels), $rows, 'my_groups_monthly_created'),
      ];
    }

    return [
      $title,
      ['#markup' => $this->t('No data found.')],
    ];
  }

  /**
   * Get groups content statistics.
   */
  private function getGroupsContents($groups) {
    $groups_stats = [];
    foreach ($groups as $group) {
      $g = ($group instanceof GroupMembership) ? $group->getGroup() : $group;
      if ($g instanceof Group) {
        $groups_stats[$g->id()] = [
          'name'     => $g->label(),
          'type'     => $g->getGroupType()->label(),
          'type_id'  => $g->getGroupType()->id(),
          'contents' => $this->groupsStatistics->countContents($g),
          'by_type'  => $this->groupsStatistics->countContentsByBundle($g),
        ];
      }
    }

    return $groups_stats;
  }

  /**
   * Get groups members statistics.
   */
  private function getGroupsMembers($groups) {
    $groups_stats = [];
    foreach ($groups as $group) {
      $g = ($group instanceof GroupMembership) ? $group->getGroup() : $group;
      if ($g instanceof Group) {
        $groups_stats[$g->id()] = [
          'name'    => $g->label(),
          'type'    => $g->getGroupType()->label(),
          'type_id' => $g->getGroupType()->id(),
          'members' => $this->groupsStatistics->countMembers($g),
          'by_role' => $this->groupsStatistics->countMembersByRole($g),
        ];
      }
    }

    return $groups_stats;
  }

  /**
   * Build page tabs: one tab by user membership group.
   */
  private function buildGroupsTabs($activeGroup = '') {
    $userGroups = $this->groupMembershipLoader->loadByUser($this->currentUser);
    if ($userGroups) {
      $prefix = '?gid=';

      $items = [];
      $items['all'] = [
        'link'  => $prefix,
        'label' => $this->t('All'),
      ];

      foreach ($userGroups as $groupMembership) {
        if ($group = $groupMembership->getGroup()) {
          $items[$group->id()] = [
            'link'  => $prefix . $group->id(),
            'label' => $group->label(),
          ];
        }
      }

      return [
        '#theme' => 'yasm_tabs',
        '#items' => $items,
        '#active_link' => $prefix . $activeGroup,
      ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    YasmBuilderInterface $yasm_builder,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
    ModuleHandlerInterface $module_handler,
    DatatablesInterface $datatables,
    GroupsStatisticsInterface $groups_statistics
  ) {
    $this->yasmBuilder = $yasm_builder;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->datatables = $datatables;
    $this->groupsStatistics = $groups_statistics;

    // Conditional dependency injection is not working. Remove this when works.
    if ($this->moduleHandler->moduleExists('group')) {
      $this->setGroupMembershipLoader(\Drupal::service('group.membership_loader'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yasm.builder'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
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
