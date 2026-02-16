<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Component\Utility\NestedArray;

@trigger_error('\Drupal\pagerer\Plugin\pagerer\Scrollpane is deprecated in pagerer:8.x-2.3 and is removed in pagerer:3.0.0. Moved to the pagerer_jqueryui module. See https://www.drupal.org/project/pagerer/issues/3256042', E_USER_DEPRECATED);

/**
 * Pager style to display a scrollpane embedding a full pager.
 *
 * Page navigation is managed via a javascript.
 *
 * @PagererStyle(
 *   id = "scrollpane",
 *   title = @Translation("Scrollpane pager"),
 *   short_title = @Translation("Scrollpane"),
 *   help = @Translation("Pager style to display a scrollpane embedding a full pager."),
 *   style_type = "base"
 * )
 *
 * @deprecated in pagerer:8.x-2.3 and is removed in pagerer:3.0.0. Moved to the
 *   pagerer_jqueryui module.
 *
 * @see https://www.drupal.org/project/pagerer/issues/3256042
 */
class Scrollpane extends PagererStyleBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEmptyPager() {
    // The text will be embedded in the scrollpane.
    return $this->buildPagerItems();
  }

  /**
   * Return the pager render array.
   *
   * @return array
   *   render array.
   */
  protected function buildPagerItems() {
    // Prepares state.
    $state_settings = [
      'quantity' => $this->getOption('quantity'),
      'pageTag' => [
        'page_title' => $this->getTag($this->getOption('display') . '.page_title'),
        'first_title' => $this->getTag($this->getOption('display') . '.first_title'),
        'last_title' => $this->getTag($this->getOption('display') . '.last_title'),
      ],
    ];
    $pagerer_widget_id = $this->prepareJsState($state_settings);

    $items = [];

    // Left buttons.
    $vars = $this->getNavigationItem('first', FALSE);
    $items[] = [
      'widget' => [
        '#theme' => 'pagerer_scrollpane_button',
        '#scope' => 'first',
        '#text' => $vars['text'],
        '#title' => $vars['title'],
      ],
    ];
    $vars = $this->getNavigationItem('previous', FALSE);
    $items[] = [
      'widget' => [
        '#theme' => 'pagerer_scrollpane_button',
        '#scope' => 'previous',
        '#text' => $vars['text'],
        '#title' => $vars['title'],
      ],
    ];

    // Scrollpane.
    $embed_pager_config = NestedArray::mergeDeep(
      $this->configuration,
      [
        'prefix_display' => FALSE,
        'display_mode' => 'normal',
        'suffix_display' => FALSE,
        'first_link' => 'never',
        'previous_link' => 'never',
        'next_link' => 'never',
        'last_link' => 'never',
        'fl_breakers' => FALSE,
      ]
    );
    $items[] = [
      'widget' => [
        '#id' => $pagerer_widget_id,
        '#type' => 'pager',
        '#theme' => 'pagerer_base',
        '#style' => 'standard',
        '#element' => $this->pager->getElement(),
        '#parameters' => $this->parameters,
        '#route_name' => $this->pager->getRouteName(),
        '#route_parameters' => $this->pager->getRouteParameters(),
        '#config' => $embed_pager_config,
        '#embedded' => TRUE,
        '#state'  => $state_settings,
      ],
    ];

    // Right buttons.
    $vars = $this->getNavigationItem('next', FALSE);
    $items[] = [
      'widget' => [
        '#theme' => 'pagerer_scrollpane_button',
        '#scope' => 'next',
        '#text' => $vars['text'],
        '#title' => $vars['title'],
      ],
    ];
    $vars = $this->getNavigationItem('last', FALSE);
    $items[] = [
      'widget' => [
        '#theme' => 'pagerer_scrollpane_button',
        '#scope' => 'last',
        '#text' => $vars['text'],
        '#title' => $vars['title'],
      ],
    ];

    return $items;
  }

}
