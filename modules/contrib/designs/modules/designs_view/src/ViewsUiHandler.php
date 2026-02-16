<?php

namespace Drupal\designs_view;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\designs\DesignManagerInterface;
use Drupal\views_ui\ViewUI;

/**
 * Provides handling of the views UI.
 */
class ViewsUiHandler {

  use StringTranslationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * ViewsUiHandler constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   */
  public function __construct(DesignManagerInterface $designManager) {
    $this->designManager = $designManager;
  }

  /**
   * Modify the display tab details.
   *
   * @param array $build
   *   The render array for the display content.
   * @param \Drupal\views_ui\ViewUI $view
   *   The view.
   * @param string $display_id
   *   The display identifier.
   */
  public function displayTabAlter(array &$build, ViewUI $view, $display_id) {
    // Modify the second column, as this contains all the additional areas.
    $column = &$build['details']['columns']['second'];

    foreach (['header', 'footer', 'empty', 'pager'] as $type) {
      if (!isset($column[$type]['#actions'])) {
        $column['pager']['#actions'] = [
          '#type' => 'dropbutton',
          '#links' => [],
          '#attributes' => [
            'class' => ['views-ui-settings-bucket-operations'],
          ],
          '#attached' => [
            'library' => [
              'designs_view/views-admin',
            ],
          ],
        ];
      }

      $column[$type]['#actions']['#links']['design'] = [
        'title' => $this->t('Design'),
        'url' => Url::fromRoute('views_ui.form_design', [
          'js' => 'nojs',
          'view' => $view->id(),
          'display_id' => $display_id,
          'type' => $type,
        ]),
        'attributes' => [
          'class' => ['icon compact design', 'views-ajax-link'],
          'id' => 'views-design-' . $type,
        ],
      ];
    }
  }

}
