<?php

/**
 * @file
 * Contains \Drupal\resource\Controller\ResourceController.
 */

namespace Drupal\resource\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\resource\ResourceTypeInterface;
use Drupal\resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Returns responses for Resource routes.
 */
class ResourceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ResourceController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for available content types.
   *
   * Redirects to resource/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the resource types that can be added; however,
   *   if there is only one resource type defined for the site, the function
   *   will return a RedirectResponse to the resource add page for that one resource
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'resource_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('resource_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use resource types the user has access to.
    foreach ($this->entityManager()->getStorage('resource_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('resource')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the resource/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('resource.add', array('resource_type' => $type->id()));
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the resource submission form.
   *
   * @param \Drupal\resource\ResourceTypeInterface $resource_type
   *   The resource type entity for the resource.
   *
   * @return array
   *   A resource submission form.
   */
  public function add(ResourceTypeInterface $resource_type) {
    $resource = $this->entityManager()->getStorage('resource')->create(array(
      'type' => $resource_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($resource);

    return $form;
  }

  /**
   * Displays a resource revision.
   *
   * @param int $resource_revision
   *   The resource revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($resource_revision) {
    $resource = $this->entityManager()->getStorage('resource')->loadRevision($resource_revision);
    $resource_view_controller = new ResourceViewController($this->entityManager, $this->renderer);
    $page = $resource_view_controller->view($resource);
    unset($page['resources'][$resource->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a resource revision.
   *
   * @param int $resource_revision
   *   The resource revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($resource_revision) {
    $resource = $this->entityManager()->getStorage('resource')->loadRevision($resource_revision);
    return $this->t('Revision of %title from %date', array('%title' => $resource->label(), '%date' => format_date($resource->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a resource.
   *
   * @param \Drupal\resource\ResourceInterface $resource
   *   A resource object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ResourceInterface $resource) {
    $account = $this->currentUser();
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $langname = $this->languageManager()->getLanguageName($langcode);
    $languages = $resource->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $resource_storage = $this->entityManager()->getStorage('resource');
    $type = $resource->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $resource->label()]) : $this->t('Revisions for %title', ['%title' => $resource->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer resources')) && $resource->access('update'));
    $delete_permission =  (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer resources')) && $resource->access('delete'));

    $rows = array();

    $vids = $resource_storage->revisionIds($resource);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\resource\ResourceInterface $revision */
      $revision = $resource_storage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionAuthor(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $resource->getRevisionId()) {
          $link = $this->l($date, new Url('entity.resource.revision', ['resource' => $resource->id(), 'resource_revision' => $vid]));
        }
        else {
          $link = $resource->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-resource">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->revision_resource->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/resource/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
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
                Url::fromRoute('resource.revision_revert_translation_confirm', ['resource' => $resource->id(), 'resource_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('resource.revision_revert_confirm', ['resource' => $resource->id(), 'resource_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('resource.revision_delete_confirm', ['resource' => $resource->id(), 'resource_revision' => $vid]),
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

    $build['resource_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('resource/drupal.resource.admin'),
      ),
    );

    return $build;
  }

  /**
   * The _title_callback for the resource.add route.
   *
   * @param \Drupal\resource\ResourceTypeInterface $resource_type
   *   The current resource.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(ResourceTypeInterface $resource_type) {
    return $this->t('Create @name', array('@name' => $resource_type->label()));
  }

}
