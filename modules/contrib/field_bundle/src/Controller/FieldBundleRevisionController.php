<?php

namespace Drupal\field_bundle\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\field_bundle\FieldBundleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for field bundle revision routes.
 */
class FieldBundleRevisionController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a FieldBundleRevisionController object.
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
   * Generates an overview table of older revisions of a field bundle.
   *
   * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
   *   A field bundle item.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(FieldBundleInterface $field_bundle) {
    $account = $this->currentUser();
    $langcode = $field_bundle->language()->getId();
    $langname = $field_bundle->language()->getName();
    $languages = $field_bundle->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $field_bundle_storage = $this->entityTypeManager()->getStorage('field_bundle');
    $config_id = $field_bundle->bundle();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $field_bundle->label()]) : $this->t('Revisions for %title', ['%title' => $field_bundle->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $config_id field_bundle revisions") || $account->hasPermission('revert field_bundle revisions') || $account->hasPermission('administer field_bundle')) && $field_bundle->access('update'));
    $delete_permission = (($account->hasPermission("delete $config_id field_bundle revisions") || $account->hasPermission('delete field_bundle revisions') || $account->hasPermission('administer field_bundle')) && $field_bundle->access('delete'));

    $rows = [];
    $default_revision = $field_bundle->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($field_bundle, $field_bundle_storage) as $vid) {
      /** @var \Drupal\field_bundle\FieldBundleInterface $revision */
      $revision = $field_bundle_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');

        $link = Link::fromTextAndUrl($date, new Url('entity.field_bundle.revision', ['field_bundle' => $field_bundle->id(), 'field_bundle_revision' => $vid]))->toString();

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
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
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
              'title' => $vid < $field_bundle->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $has_translations ?
                Url::fromRoute('field_bundle.revision_revert_translation_confirm', ['field_bundle' => $field_bundle->id(), 'field_bundle_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('field_bundle.revision_revert_confirm', ['field_bundle' => $field_bundle->id(), 'field_bundle_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('field_bundle.revision_delete_confirm', ['field_bundle' => $field_bundle->id(), 'field_bundle_revision' => $vid]),
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

    $build['field_bundle_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'field-bundle-revision-table'],
    ];
    if ($this->moduleHandler()->moduleExists('node')) {
      $build['field_bundle_revisions_table']['#attached']['library'][] = 'node/drupal.node.admin';
    }

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Gets a list of field bundle revision IDs for a specific item.
   *
   * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
   *   The field bundle item.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $field_bundle_storage
   *   The field bundle storage handler.
   *
   * @return int[]
   *   Field bundle revision IDs (in descending order).
   */
  protected function getRevisionIds(FieldBundleInterface $field_bundle, ContentEntityStorageInterface $field_bundle_storage) {
    $result = $field_bundle_storage->getQuery()
      ->accessCheck(TRUE)
      ->allRevisions()
      ->condition($field_bundle->getEntityType()->getKey('id'), $field_bundle->id())
      ->sort($field_bundle->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
