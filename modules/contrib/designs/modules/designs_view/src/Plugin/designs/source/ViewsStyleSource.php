<?php

namespace Drupal\designs_view\Plugin\designs\source;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignSourceBase;

/**
 * The source providing views style sources.
 *
 * @DesignSource(
 *   id = "views_style",
 *   label = @Translation("Views style"),
 *   defaultSources = {
 *     "attachment_before",
 *     "header",
 *     "exposed",
 *     "empty",
 *     "rows",
 *     "pager",
 *     "footer",
 *     "more",
 *     "feed_icons",
 *     "attachment_after"
 *   }
 * )
 */
class ViewsStyleSource extends DesignSourceBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    return [
      'rows' => $this->t('Rows'),
      'header' => $this->t('Header'),
      'footer' => $this->t('Footer'),
      'empty' => $this->t('Empty'),
      'exposed' => $this->t('Exposed Filters'),
      'more' => $this->t('More'),
      'feed_icons' => $this->t('Feed Icons'),
      'pager' => $this->t('Pager'),
      'attachment_before' => $this->t('Attachment Before'),
      'attachment_after' => $this->t('Attachment After'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $results = [];
    foreach ($this->getSources() as $key => $label) {
      $results[$key] = $element["#{$key}"] ?? [];
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [
      'view' => $element['#view'],
    ];
  }

}
