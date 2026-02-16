<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Views' condition.
 *
 * @Condition(
 *   id = "view_inclusion",
 *   label = @Translation("View inclusion")
 * )
 */
class ViewInclusion extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * View constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    $options = [];
    foreach ($views as $key => $view) {
      foreach ($view->get('display') as $display) {
        if ($display['display_plugin'] === 'page') {
          $viewRoute = 'view-' . $key . '-' . $display['id'];
          $options[$viewRoute] = $view->label() . ' - ' . $display['display_title'];
        }
      }
    }

    $configuration = $this->getConfiguration();

    $form['views_pages'] = [
      '#title' => $this->t('Views pages'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => isset($configuration['view_inclusion']) && !empty($configuration['view_inclusion']) ? array_keys($configuration['view_inclusion']) : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['view_inclusion'] = array_filter($form_state->getValue('views_pages'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return t('Select views pages');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $route = str_replace('.', '-', $this->currentRouteMatch->getRouteName());
    $configuration = $this->getConfiguration();

    if (array_key_exists('view_inclusion', $configuration) && !empty($configuration['view_inclusion'])) {
      return in_array($route, $configuration['view_inclusion']);
    }
    else {
      return TRUE;
    }
  }

}
