<?php

namespace Drupal\flow\Plugin\flow\Derivative\Subject;

use Drupal\flow\Helpers\EntityFieldManagerTrait;
use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;
use Drupal\flow\Plugin\FlowSubjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subject plugin deriver for content referenced from other content.
 *
 * This deriver wraps other plugin definitions and thus makes use of the
 * subject plugin manager by itself. To prevent infinite recursion during
 * plugin definition discovery, an internal flag will be used. This deriver
 * might also add a lot more plugin definitions, making the list of available
 * subjects quite large. This could be improved in the future.
 *
 * @see \Drupal\flow\Plugin\flow\Subject\Reference
 */
class ReferenceDeriver extends ContentDeriverBase {

  use EntityFieldManagerTrait;

  /**
   * The Flow subject manager for retreiving other plugin definitions.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectManager
   */
  protected FlowSubjectManager $subjectManager;

  /**
   * A flag indicating whether we are calling the definitions from root level.
   *
   * This flag is used to prevent infinite recursion, as we want to use other
   * existing plugin definitions, and wrap them using existing reference fields.
   *
   * @var bool
   */
  protected bool $rootDefinitionCall = TRUE;

  /**
   * A statically cached list of reference derivatives.
   *
   * @var array|null
   */
  protected static ?array $referenceDerivatives;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\flow\Plugin\flow\Derivative\Subject\ReferenceDeriver $instance */
    $instance = parent::create($container, $base_plugin_id);
    $instance->setSubjectManager($container->get('plugin.manager.flow.subject'));
    $instance->setEntityFieldManager($container->get(self::$entityFieldManagerServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!$this->rootDefinitionCall) {
      // Coming from the root call, only return an empty array at this point.
      return [];
    }
    if (isset(self::$referenceDerivatives)) {
      $this->rootDefinitionCall = TRUE;
      return self::$referenceDerivatives;
    }

    // From here on, flag this one not to be the root level call.
    $this->rootDefinitionCall = FALSE;
    $other_definitions = $this->subjectManager->getDefinitions();

    $reference_derivatives = [];
    $content_derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($other_definitions as $other_plugin_id => $other_definition) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($other_definition['entity_type'], $other_definition['bundle']);
      $reference_targets = [];
      foreach ($field_definitions as $field_definition) {
        $field_storage_definition = $field_definition->getFieldStorageDefinition();
        if (!(strpos($field_storage_definition->getType(), 'entity_reference') === 0)) {
          continue;
        };
        $target_type = $field_storage_definition->getSetting('target_type');
        $handler_settings = $field_definition->getConfig($other_definition['bundle'])->getSetting('handler_settings');
        if (!isset($reference_targets[$target_type])) {
          $reference_targets[$target_type] = [];
        }
        if (!empty($handler_settings['target_bundles'])) {
          $reference_targets[$target_type] += $handler_settings['target_bundles'];
        }
        else {
          $reference_targets[$target_type] = [];
        }
      }
      foreach ($reference_targets as $target_type => $target_bundles) {
        foreach ($content_derivatives as $content_derivative) {
          $entity_type_id = $content_derivative['entity_type'];
          $bundle = $content_derivative['bundle'];
          if (($target_type !== $entity_type_id) || (!empty($target_bundles) && !in_array($bundle, $target_bundles))) {
            continue;
          }
          $derivative_id = $entity_type_id . '.' . $bundle . '::' . $other_plugin_id;
          $reference_derivatives[$derivative_id] = [
            'task_modes' => $other_definition['task_modes'],
            'targets' => $other_definition['targets'],
            'label' => $this->t('@reference referenced from @content', [
              '@reference' => $content_derivative['label'],
              '@content' => $other_definition['label'],
            ]),
          ] + $content_derivative;
        }
      }
    }

    // Reset the flag, in order to have the same behavior also on a cache reset.
    $this->rootDefinitionCall = TRUE;
    self::$referenceDerivatives = $reference_derivatives;
    return $reference_derivatives;
  }

  /**
   * Set the Flow subject plugin manager.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectManager $subject_manager
   *   The subject plugin manager.
   */
  public function setSubjectManager(FlowSubjectManager $subject_manager): void {
    $this->subjectManager = $subject_manager;
  }

}
