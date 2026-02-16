<?php

namespace Drupal\element\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\element\Entity\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElementController.
 *
 *  Returns responses for element routes.
 */
class ElementController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');

    return new static($dateFormatter, $renderer);
  }

  /**
   * ElementController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   */
  public function __construct(DateFormatterInterface $dateFormatter, RendererInterface $renderer) {
    $this->dateFormatter = $dateFormatter;
    $this->renderer = $renderer;
  }

  /**
   * Displays an element revision.
   *
   * @param int $element_revision
   *   The element revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function revisionShow($element_revision) {
    $entityTypeManager = $this->entityTypeManager();
    $element = $entityTypeManager->getStorage('element')->loadRevision($element_revision);
    $view_builder = $entityTypeManager->getViewBuilder('element');

    return $view_builder->view($element);
  }

  /**
   * Page title callback for a element revision.
   *
   * @param int $element_revision
   *   The element revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function revisionPageTitle($element_revision) {
    /** @var \Drupal\element\Entity\ElementInterface $element */
    $element = $this->entityTypeManager()->getStorage('element')->loadRevision($element_revision);
    return $this->t('Revision of %title from %date', ['%title' => $element->label(), '%date' => $this->dateFormatter->format($element->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of revisions of a element.
   *
   * @param \Drupal\element\Entity\ElementInterface $element
   *   A element object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown in there was a problem with the loaded entity.
   */
  public function revisionOverview(ElementInterface $element) {
    $account = $this->currentUser();
    $langcode = $element->language()->getId();
    $langname = $element->language()->getName();
    $languages = $element->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);
    /** @var \Drupal\element\ElementStorageInterface $elementItemStorage */
    $elementItemStorage = $this->entityTypeManager()->getStorage('element');

    $build['#title'] = $hasTranslations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $element->label()]) : $this->t('Revisions for %title', ['%title' => $element->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $mayRevert = (($account->hasPermission('revert all element revisions') || $account->hasPermission('administer element entities')));
    $mayDelete = (($account->hasPermission('delete all element revisions') || $account->hasPermission('administer element entities')));

    $rows = [];

    $revisionIds = $elementItemStorage->revisionIds($element);
    $currentRevision = $element->getRevisionId();

    // Let's start building the revision table.
    foreach (array_reverse($revisionIds) as $revisionId) {
      /** @var \Drupal\element\Entity\ElementInterface $revision */
      $revision = $elementItemStorage->loadRevision($revisionId);

      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected())) {
        continue;
      }

      // Array to keep the data for the current table row.
      $row = [];

      // Build the first table cell, containing author name, revision date and
      // revision log message.
      $row[] = $this->getRevisionInfoTableCell($revision, $element);

      // Build the second table cell. It will contain Revert and Delete links,
      // or just an indicator for the current revision.
      if ($revision->getRevisionId() != $currentRevision) {
        $row[] = $this->getRevisionOperationsTableCell($revision, $element, $mayRevert, $mayDelete, $hasTranslations);
      }
      else {
        // This is the current revision.
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];

        // Decorate all row cells with a class.
        foreach ($row as &$current) {
          $current['class'] = ['revision-current'];
        }
      }

      $rows[] = $row;
    }

    $build['element_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Build table data for the info column of the revision table.
   *
   * @param \Drupal\element\Entity\ElementInterface $revision
   *   The revision to produce the table cell for.
   * @param \Drupal\element\Entity\ElementInterface $element
   *   The element the revision belongs to.
   *
   * @return array
   *   Partial render array representing a table cell.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  private function getRevisionInfoTableCell(ElementInterface $revision, ElementInterface $element) {
    $username = [
      '#theme' => 'username',
      '#account' => $revision->getRevisionUser(),
    ];

    $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');

    // Use revision link to link to revisions that are not active.
    $vid = $revision->getRevisionId();
    if ($vid != $element->getRevisionId()) {
      $link = new Link($date, new Url('entity.element.revision', ['element' => $element->id(), 'element_revision' => $vid]));
    }
    else {
      $link = $element->toLink($date);
    }
    $renderableLink = $link->toRenderable();

    return [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
        '#context' => [
          'date' => $this->renderer->renderPlain($renderableLink),
          'username' => $this->renderer->renderPlain($username),
          'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
        ],
      ],
    ];
  }

  /**
   * Build table data for the operations column of the revision table.
   *
   * @param \Drupal\element\Entity\ElementInterface $revision
   *   The revision to produce the table cell for.
   * @param \Drupal\element\Entity\ElementInterface $element
   *   The element the revision belongs to.
   * @param bool $mayRevert
   *   Indicates whether the current user may revert revisions.
   * @param bool $mayDelete
   *   Indicates whether the current user may delete revisions.
   * @param bool $hasTranslations
   *   Indicates whether the element has translations.
   *
   * @return array
   *   Partial render array representing a table cell.
   */
  private function getRevisionOperationsTableCell(ElementInterface $revision, ElementInterface $element, $mayRevert, $mayDelete, $hasTranslations) {
    $langcode = $element->language()->getId();
    $vid = $revision->getRevisionId();

    $links = [];
    if ($mayRevert) {
      $urlWithTranslations = Url::fromRoute(
        'entity.element.translation_revert',
        [
          'element' => $element->id(),
          'element_revision' => $vid,
          'langcode' => $langcode,
        ]
      );
      $urlWithoutTranslations = Url::fromRoute(
        'entity.element.revision_revert',
        [
          'element' => $element->id(),
          'element_revision' => $vid,
        ]
      );
      $links['revert'] = [
        'title' => $this->t('Revert'),
        'url' => $hasTranslations ? $urlWithTranslations : $urlWithoutTranslations,
      ];
    }

    if ($mayDelete) {
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.element.revision_delete', ['element' => $element->id(), 'element_revision' => $vid]),
      ];
    }

    return [
      'data' => [
        '#type' => 'operations',
        '#links' => $links,
      ],
    ];
  }

}
