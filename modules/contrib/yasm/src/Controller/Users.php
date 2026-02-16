<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Services\UsersStatisticsInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * YASM Statistics site users controller.
 */
class Users extends ControllerBase {

  /**
   * The yasm builder service.
   *
   * @var \Drupal\yasm\Services\YasmBuilderInterface
   */
  protected $yasmBuilder;

  /**
   * The Date Formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @var \Drupal\yasm\Services\EntitiesStatisticsInterface
   */
  protected $entitiesStatistics;

  /**
   * The users statistics service.
   *
   * @var \Drupal\yasm\Services\UsersStatisticsInterface
   */
  protected $usersStatistics;

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
    return ($this->moduleHandler->moduleExists('user')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Site users page output.
   */
  public function siteContent(Request $request) {
    $filters = [];

    $year = $request->query->get('year', 'all');
    if (is_numeric($year)) {
      $filters = $this->yasmBuilder->getYearFilter('created', $year);
      $this->messenger->addMessage($this->t('"Users by access" and "Lasts users access" tables are not filtered by year.'));
    }

    $first_content_date = $this->entitiesStatistics->getFirstDateContent('user');

    $build['tabs'] = $this->yasmBuilder->getYearLinks(date('Y', $first_content_date), $year);
    $build['data'] = $this->buildContent($year, $filters);

    return $build;
  }

  /**
   * Build users page html.
   */
  private function buildContent($year, $filters) {
    // Exclude uid = 0 (anonymous).
    $filter_non_anonymous = [
      [
        'key'      => 'uid',
        'value'    => 0,
        'operator' => '!=',
      ],
    ];
    $filters = array_merge($filters, $filter_non_anonymous);

    $userCount = $this->entitiesStatistics->count('user', $filters);

    if ($userCount > 0) {
      $users_by_status       = $this->tableUsersByStatus($userCount, $filters);
      $users_by_role         = $this->tableUsersByRole($userCount, $filters);
      $users_by_access       = $this->tableUsersByAccess($userCount);
      $users_by_domain       = $this->tableUsersByDomain($userCount);
      $users_lasts_access    = $this->tableUsersLastAccess();
      $users_created_monthly = $this->tableUsersCreatedMonthly($year);

      // Build content output.
      $build = [];
      $build[] = $this->yasmBuilder->markup($this->t('There are currently @count users.', ['@count' => $userCount]));

      $cards[] = [
        $this->yasmBuilder->title($this->t('Users created by status'), 'far fa-user'),
        $users_by_status,
      ];
      $cards[] = [
        $this->yasmBuilder->title($this->t('Users created by role'), 'far fa-user'),
        $users_by_role,
      ];
      $cards[] = [
        $this->yasmBuilder->title($this->t('Users by access'), 'fas fa-door-open'),
        $users_by_access,
      ];
      $cards[] = [
        $this->yasmBuilder->title($this->t('Users by mail domain'), 'fas fa-home'),
        $users_by_domain,
      ];

      // First region in two cols.
      $build[] = $this->yasmBuilder->columns($cards, ['yasm-users'], 2);

      $cards = [];
      $cards[] = [
        $this->yasmBuilder->title($this->t('New users monthly'), 'far fa-user'),
        $users_created_monthly,
      ];
      $cards[] = [
        $this->yasmBuilder->title($this->t('Recent users who have accessed'), 'far fa-user'),
        $users_lasts_access,
      ];

      // Second region in one col.
      $build[] = $this->yasmBuilder->columns($cards, ['yasm-users'], 1);

      return [
        '#theme' => 'yasm_wrapper',
        '#content' => $build,
        '#attached' => [
          'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
          'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
        ],
        '#cache' => [
          'tags' => ['user_list'],
          'max-age' => 3600,
        ],
      ];
    }

    return ['#markup' => $this->t('No data found.')];
  }

  /**
   * Build users by status table.
   */
  private function tableUsersByStatus(int $userCount, array $filters = []) {
    $users_status = $this->entitiesStatistics->aggregate('user', ['uid' => 'COUNT'], 'status', $filters);

    $status_label = [
      1 => $this->t('Active'),
      0 => $this->t('Blocked'),
    ];

    $rows = [];
    if (!empty($users_status)) {
      foreach ($users_status as $users) {
        $rows[] = [
          $status_label[$users['status']],
          $users['uid_count'],
          round($users['uid_count'] * 100 / $userCount, 2) . '%',
        ];
      }
    }

    return $this->yasmBuilder->table([
      $this->t('Status'),
      $this->t('Count'),
      $this->t('Percentage'),
    ], $rows, 'users_status');
  }

  /**
   * Build users by role table.
   */
  private function tableUsersByRole(int $userCount, array $filters = []) {
    $users_roles = $this->entitiesStatistics->aggregate('user', ['uid' => 'COUNT'], 'roles', $filters);

    $rows = [];
    if (!empty($users_roles)) {
      foreach ($users_roles as $users) {
        if (!empty($users['roles_target_id'])) {
          $role = $this->entityTypeManager->getStorage('user_role')->load($users['roles_target_id'])->label();
        }
        else {
          $role = $this->t('None');
        }

        $rows[] = [
          $role,
          $users['uid_count'],
          round($users['uid_count'] * 100 / $userCount, 2) . '%',
        ];
      }
    }

    return $this->yasmBuilder->table([
      $this->t('Role'),
      $this->t('Count'),
      $this->t('Percentage'),
    ], $rows, 'users_role');
  }

  /**
   * Build users by access table.
   */
  private function tableUsersByAccess(int $userCount) {
    $rows = [];

    // Users have never accesed.
    $never = $this->entitiesStatistics->count('user', ['access' => 0]);
    $rows[] = [
      $this->t('Never'),
      $never,
      round($never * 100 / $userCount, 2) . '%',
    ];

    // Group users by last access table.
    $access = [
      strtotime('-1 day')   => $this->t('Today'),
      strtotime('-1 week')  => $this->t('Last week'),
      strtotime('-1 month') => $this->t('Last month'),
      strtotime('-1 year')  => $this->t('Last year'),
      strtotime('-3 years') => $this->t('Last 3 years'),
    ];
    foreach ($access as $key => $value) {
      $count = $this->entitiesStatistics->count('user', [
        [
          'key'      => 'access',
          'value'    => $key,
          'operator' => '>',
        ],
      ]);
      $rows[] = [
        $value,
        $count,
        round($count * 100 / $userCount, 2) . '%',
      ];
    }

    return $this->yasmBuilder->table([
      $this->t('Access'),
      $this->t('Count'),
      $this->t('Percentage'),
    ], $rows, 'users_access');
  }

  /**
   * Build users by email domain table.
   */
  private function tableUsersByDomain(int $userCount) {
    $count_by_domain = $this->usersStatistics->countUsersByEmailDomain();

    $rows = [];
    foreach ($count_by_domain as $value => $count) {
      $rows[] = [
        $value,
        $count,
        round($count * 100 / $userCount, 2) . '%',
      ];
    }

    return $this->yasmBuilder->table([
      $this->t('Domain'),
      $this->t('Count'),
      $this->t('Percentage'),
    ], $rows, 'users_domain');
  }

  /**
   * Build users list by last acces table.
   */
  private function tableUsersLastAccess(int $limit = 30) {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    $query->condition('uid', 0, '!=')
      ->sort('access', 'DESC')
      ->range(0, $limit);
    $uids = $query->execute();

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    $rows = [];
    foreach ($users as $user) {
      $rows[] = [
        'name'   => $user->getDisplayName() . ' (' . $user->getAccountName() . ')',
        'access' => $this->dateFormatter->formatTimeDiffSince($user->getLastAccessedTime()),
      ];
    }

    return $this->yasmBuilder->table([
      $this->t('Access'),
      $this->t('User'),
    ], $rows);
  }

  /**
   * Build users by role table.
   */
  private function tableUsersCreatedMonthly($year) {
    // Build new users monthly table.
    $dates = $this->yasmBuilder->getLastMonths($year);

    $rows = $labels = [];
    foreach ($dates as $date) {
      $labels[] = $date['label'];
      $filter = $this->yasmBuilder->getIntervalFilter('created', $date['max'], $date['min']);

      $rows['data'][] = $this->entitiesStatistics->count('user', $filter);
    }

    return $this->yasmBuilder->table($labels, $rows, 'users_monthly');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    YasmBuilderInterface $yasm_builder,
    DateFormatterInterface $date_formatter,
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
    ModuleHandlerInterface $module_handler,
    DatatablesInterface $datatables,
    EntitiesStatisticsInterface $entities_statistics,
    UsersStatisticsInterface $users_statistics
  ) {
    $this->yasmBuilder = $yasm_builder;
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->datatables = $datatables;
    $this->entitiesStatistics = $entities_statistics;
    $this->usersStatistics = $users_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yasm.builder'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics'),
      $container->get('yasm.users_statistics')
    );
  }

}
