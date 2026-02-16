<?php

namespace Drupal\access_records\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\access_records\AccessRecordInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for access record revision routes.
 */
class AccessRecordRevisionController extends ControllerBase implements ContainerInjectionInterface {

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
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a AccessRecordRevisionController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Generates an overview table of older revisions of a access record.
   *
   * @param \Drupal\access_records\AccessRecordInterface $access_record
   *   An access record.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(AccessRecordInterface $access_record) {
    $account = $this->currentUser();
    $langcode = $access_record->language()->getId();
    $langname = $access_record->language()->getName();
    $languages = $access_record->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $access_record_storage = $this->entityTypeManager()->getStorage('access_record');
    $type_id = $access_record->bundle();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $access_record->label()]) : $this->t('Revisions for %title', ['%title' => $access_record->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $type_id access_record revisions") || $account->hasPermission('revert access_record revisions') || $account->hasPermission('administer access_record')) && $access_record->access('update'));
    $delete_permission = (($account->hasPermission("delete $type_id access_record revisions") || $account->hasPermission('delete access_record revisions') || $account->hasPermission('administer access_record')) && $access_record->access('delete'));

    $rows = [];
    $default_revision = $access_record->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($access_record, $access_record_storage) as $vid) {
      /** @var \Drupal\access_records\AccessRecordInterface $revision */
      $revision = $access_record_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->ar_rev_timestamp->value, 'short');

        $link = Link::fromTextAndUrl($date, new Url('entity.access_record.revision', ['access_record' => $access_record->id(), 'access_record_revision' => $vid]))->toString();

        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if ($is_current_revision) {
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
              'message' => ['#markup' => $revision->ar_rev_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $vid < $access_record->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $has_translations ?
                Url::fromRoute('access_record.revision_revert_translation_confirm', ['access_record' => $access_record->id(), 'access_record_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('access_record.revision_revert_confirm', ['access_record' => $access_record->id(), 'access_record_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('access_record.revision_delete_confirm', ['access_record' => $access_record->id(), 'access_record_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['access_record_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'access-record-revision-table'],
    ];
    if ($this->moduleHandler()->moduleExists('node')) {
      $build['access_record_revisions_table']['#attached']['library'][] = 'node/drupal.node.admin';
    }

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Gets a list of access record revision IDs for a specific item.
   *
   * @param \Drupal\access_records\AccessRecordInterface $access_record
   *   The access record item.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $access_record_storage
   *   The access record storage handler.
   *
   * @return int[]
   *   Access record revision IDs (in descending order).
   */
  protected function getRevisionIds(AccessRecordInterface $access_record, ContentEntityStorageInterface $access_record_storage) {
    $result = $access_record_storage->getQuery()
      ->accessCheck(TRUE)
      ->allRevisions()
      ->condition($access_record->getEntityType()->getKey('id'), $access_record->id())
      ->sort($access_record->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
