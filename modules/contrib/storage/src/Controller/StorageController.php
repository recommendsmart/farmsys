<?php

namespace Drupal\storage\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\storage\Entity\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class StorageController.
 *
 *  Returns responses for Storage routes.
 */
class StorageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Provides a canonical page to render a single Storage entity.
   *
   * This wraps the default entity view controller in the way that a check is
   * performed, whether the according type is configured to have a canonical
   * URL. It passes the rendering to the default view controller if enabled
   * to do so, and otherwise throws an exception to show a 404 page instead,
   * or redirects a privileged user to the edit form.
   *
   * @param \Drupal\storage\Entity\StorageInterface $storage
   *   The Storage entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render() or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When the Storage type is not configured to have canonical URLs and the
   *   current user has no access to edit the entity.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When the user has no access to view the Storage entity.
   */
  public function viewCanonical(StorageInterface $storage, $view_mode = 'full') {
    /** @var \Drupal\storage\Entity\StorageTypeInterface $storage_type */
    $storage_type = $this->entityTypeManager->getStorage('storage_type')->load($storage->bundle());
    if (!$storage_type->hasCanonical()) {
      if ($storage->access('update')) {
        return $this->redirect('entity.storage.edit_form', ['storage' => $storage->id()], [], 302);
      }
      throw new NotFoundHttpException();
    }
    if (!$storage->access('view')) {
      throw new AccessDeniedHttpException();
    }
    return (new EntityViewController($this->entityTypeManager, $this->renderer))->view($storage, $view_mode);
  }

  /**
   * Custom access callback for ::viewCanonical().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\storage\Entity\StorageInterface $storage
   *   The requested Storage entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function viewCanonicalAccess(AccountInterface $account, StorageInterface $storage) {
    // Local menu links are being built up using a "fake" route match. Therefore
    // we catch the current route match from the global container instead.
    $current_route_match = \Drupal::routeMatch();
    $route = $current_route_match->getRouteObject();

    if ($route && ($route->getDefault('_controller') === 'Drupal\storage\Controller\StorageController::viewCanonical')) {
      // Let ::viewCanonical finally decide whether access is allowed.
      return AccessResult::allowed()
        ->addCacheContexts(['url.path', 'url.query_args'])
        ->addCacheableDependency($storage);
    }

    /** @var \Drupal\storage\Entity\StorageTypeInterface $storage_type */
    $storage_type = $this->entityTypeManager->getStorage('storage_type')->load($storage->bundle());
    if (!$storage_type->hasCanonical()) {
      return AccessResult::forbidden()
        ->addCacheContexts(['route.name'])
        ->addCacheTags(['config:storage.storage_type.' . $storage_type->id()])
        ->addCacheableDependency($storage);
    }
    return $storage->access('view', $account, TRUE)
    ->addCacheTags(['config:storage.storage_type.' . $storage_type->id()])
      ->addCacheableDependency($storage);
  }

  /**
   * Displays a Storage revision.
   *
   * @param int $storage_revision
   *   The Storage revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($storage_revision) {
    $storage = $this->entityTypeManager()->getStorage('storage')
      ->loadRevision($storage_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('storage');

    return $view_builder->view($storage);
  }

  /**
   * Page title callback for a Storage revision.
   *
   * @param int $storage_revision
   *   The Storage revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($storage_revision) {
    $storage = $this->entityTypeManager()->getStorage('storage')
      ->loadRevision($storage_revision);
    return $this->t('Revision of %name from %date', [
      '%name' => $storage->label(),
      '%date' => $this->dateFormatter->format($storage->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Storage.
   *
   * @param \Drupal\storage\Entity\StorageInterface $storage
   *   A Storage object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(StorageInterface $storage) {
    $account = $this->currentUser();
    $storage_storage = $this->entityTypeManager()->getStorage('storage');

    $langcode = $storage->language()->getId();
    $langname = $storage->language()->getName();
    $languages = $storage->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %name', ['@langname' => $langname, '%name' => $storage->label()]) : $this->t('Revisions for %name', ['%name' => $storage->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all storage revisions") || $account->hasPermission('administer storage entities')));
    $delete_permission = (($account->hasPermission("delete all storage revisions") || $account->hasPermission('administer storage entities')));

    $rows = [];

    $vids = $storage_storage->revisionIds($storage);

    $latest_revision = TRUE;
    $default_revision = $storage->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\storage\StorageInterface $revision */
      $revision = $storage_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.storage.revision', ['storage' => $storage->id(), 'storage_revision' => $vid]))->toString();
        } else {
          $link = $storage->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.storage.translation_revert', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.storage.revision_revert', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.storage.revision_delete', [
                'storage' => $storage->id(),
                'storage_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['storage_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
