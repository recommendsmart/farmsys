<?php

namespace Drupal\flow_action\Plugin\flow\Derivative\Task;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;
use Drupal\flow_action\Helpers\ActionManagerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task plugin deriver for Action plugins.
 *
 * @see \Drupal\flow_action\Plugin\flow\Task\Action
 */
class ActionDeriver extends ContentDeriverBase {

  use ActionManagerTrait;
  use EntityTypeManagerTrait;
  use StringTranslationTrait;

  /**
   * An array of action plugins to exclude.
   *
   * @var array
   */
  public static array $excludes = [
    'action_goto_action',
    'entity_delete_action',
    'entity_print_download_action',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\flow_action\Plugin\flow\Derivative\Task\ActionDeriver $instance */
    $instance = parent::create($container, $base_plugin_id);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setActionManager($container->get(self::$actionManagerServiceName));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $content_derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    $action_definitions = $this->actionManager->getDefinitions();
    $action_derivatives = [];
    foreach ($content_derivatives as $content_id => &$content_derivative) {
      foreach ($action_definitions as $action_plugin_id => $action_plugin_definition) {
        if (isset($action_plugin_definition['provider'])) {
          $provider = $action_plugin_definition['provider'];
          if ($provider === 'flow') {
            // Exclude any action plugin coming from the Flow module itself.
            continue;
          }
          if (substr($provider, 0, 5) === 'views') {
            // Exclude any Views Bulk Operations (VBO) as they only make sense
            // to be executed via Views.
            continue;
          }
          // Exclude all actions for the ECA module, as they will be used within
          // ECA configurations and mostly don't make sense to be used within
          // Flow. One exception is the
          // "eca_trigger_content_entity_custom_event" action - this one allows
          // to start any ECA-configured process from Flow.
          if ((substr($provider, 0, 3) === 'eca' || substr($action_plugin_id, 0, 3) === 'eca') && $action_plugin_id !== "eca_trigger_content_entity_custom_event") {
            continue;
          }
        }
        if (!empty($action_plugin_definition['confirm_form_route_name'])) {
          // Cannot execute actions that have a confirmation form.
          continue;
        }
        if (!empty($action_plugin_definition['type']) && $this->entityTypeManager->hasDefinition($action_plugin_definition['type']) && $action_plugin_definition['type'] !== $content_derivative['entity_type']) {
          // When the type annotation maps to an entity type definition,
          // only map it to the according entity type.
          continue;
        }
        if (substr($action_plugin_id, 0, 7) === 'entity:') {
          [, $entity_action, $action_entity_type] = explode(':', $action_plugin_id);
          // Map entity actions to their according entity type.
          if ($action_entity_type !== $content_derivative['entity_type']) {
            continue;
          }
          // Saving an entity explicitly is not supported.
          if (substr($entity_action, 0, 4) === 'save') {
            continue;
          }
        }
        if ((substr($action_plugin_id, 0, 4) === 'user') && $content_derivative['entity_type'] !== 'user') {
          continue;
        }
        if ((substr($action_plugin_id, 0, 4) === 'node') && $content_derivative['entity_type'] !== 'node') {
          continue;
        }
        if ((substr($action_plugin_id, 0, 7) === 'comment') && $content_derivative['entity_type'] !== 'comment') {
          continue;
        }
        foreach (self::$excludes as $exclude) {
          if ($action_plugin_id === $exclude || $action_plugin_definition['id'] === $exclude) {
            continue 2;
          }
        }
        $derivative_id = $content_id . '::' . $action_plugin_id;
        $action_derivatives[$derivative_id] = [
          'label' => $this->t('Execute @action on @content', [
            '@action' => $action_plugin_definition['label'],
            '@content' => $content_derivative['label'],
          ]),
        ] + $content_derivative;
      }
    }
    return $action_derivatives;
  }

}
