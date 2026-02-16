<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_singles\Service\NodeSinglesInterface;
use Drupal\node_singles\Service\NodeSinglesSettingsInterface;

/**
 * Alters the node edit form.
 */
class NodeFormEventSubscriber {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  protected $singles;

  /**
   * The settings service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesSettingsInterface
   */
  protected $settings;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The route match.
   * @param \Drupal\node_singles\Service\NodeSinglesInterface $singles
   *   The node singles service.
   * @param \Drupal\node_singles\Service\NodeSinglesSettingsInterface $settings
   *   The settings service.
   */
  public function __construct(
    RouteMatchInterface $currentRouteMatch,
    NodeSinglesInterface $singles,
    NodeSinglesSettingsInterface $settings
  ) {
    $this->currentRouteMatch = $currentRouteMatch;
    $this->singles = $singles;
    $this->settings = $settings;
  }

  /**
   * Overrides the page title & hides irrelevant information.
   */
  public function formAlter(array &$form): void {
    if ($this->currentRouteMatch->getRouteName() !== 'entity.node.edit_form') {
      return;
    }

    $node = $this->currentRouteMatch->getParameter('node');
    if (empty($node) || !$this->singles->getSingleByBundle($node->bundle())) {
      return;
    }

    // Remove duplicate name from the page title.
    $form['#title'] = $this->t('Edit %type @singularLabel', [
      '%type' => node_get_type_label($node),
      '@singularLabel' => $this->settings->getSingularLabel(),
    ], ['context' => 'Single node edit form title']);

    // Hide authoring information since it's irrelevant.
    if (isset($form['meta']['author'])) {
      $form['meta']['author']['#access'] = FALSE;
    }

    // Hide publishing information since it's irrelevant.
    if (isset($form['meta']['published'])) {
      $form['meta']['author']['#access'] = FALSE;
    }
  }

}
