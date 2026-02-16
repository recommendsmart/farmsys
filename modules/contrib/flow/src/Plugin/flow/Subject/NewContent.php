<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntitySerializationTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\FormBuilderTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\TokenTrait;
use Drupal\flow\Plugin\FlowSubjectBase;
use Drupal\flow\Helpers\EntityContentConfigurationTrait;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\SingleTaskOperationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subject for creating new content.
 *
 * @FlowSubject(
 *   id = "new",
 *   label = @Translation("New content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\NewContentDeriver"
 * )
 */
class NewContent extends FlowSubjectBase implements PluginFormInterface {

  use EntityContentConfigurationTrait;
  use EntityFromStackTrait;
  use EntitySerializationTrait;
  use EntityTypeManagerTrait;
  use FormBuilderTrait;
  use ModuleHandlerTrait;
  use SingleTaskOperationTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Task\Merge $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->tokenTarget = 'flow';
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setFormBuilder($container->get(self::$formBuilderServiceName));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    $instance->setSerializer($container->get(self::$serializerServiceName));
    $instance->setToken($container->get(self::$tokenServiceName));
    if (empty($instance->settings['values'])) {
      $default_config = $instance->defaultConfiguration();
      $instance->settings += $default_config['settings'];
    }
    $instance->initEntityFromStack();
    $instance->initConfiguredContentEntity();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    $entity = $this->initConfiguredContentEntity($this->getEntityFromStack());
    $current_time = \Drupal::time()->getCurrentTime();
    if (method_exists($entity, 'setCreatedTime')) {
      $entity->setCreatedTime($current_time);
    }
    if ($entity instanceof EntityChangedInterface) {
      $entity->setChangedTime($current_time);
    }
    Flow::needsSave($entity, $this);
    return [$entity];
  }

}
