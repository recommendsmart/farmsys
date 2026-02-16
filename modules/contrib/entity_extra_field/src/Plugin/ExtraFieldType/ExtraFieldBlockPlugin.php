<?php

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;
use Drupal\entity_extra_field\EntityExtraFieldContextTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define extra field block type.
 *
 * @ExtraFieldType(
 *   id = "block",
 *   label = @Translation("Block")
 * )
 */
class ExtraFieldBlockPlugin extends ExtraFieldTypePluginBase {

  use EntityExtraFieldContextTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $blockManager;

  /**
   * Extra field block plugin constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    Token $token,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    RouteMatchInterface $current_route_match,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    BlockManagerInterface $block_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $module_handler,
      $current_route_match,
      $entity_type_manager,
      $entity_field_manager
    );
    $this->currentUser = $current_user;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.block'),
      $container->get('context.handler'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'block_type' => NULL,
      'block_config' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    $form_state->setTemporaryValue('gathered_contexts', [
      'entity_extra_field.target_entity' => $entity->getBaseEntityContext(),
    ] + $this->getContextRepository()->getAvailableContexts());

    $block_type = $this->getPluginFormStateValue('block_type', $form_state);

    $form['block_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Block Type'),
      '#required' => TRUE,
      '#options' => $this->getBlockTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
      ] + $this->extraFieldPluginAjax(),
      '#default_value' => $block_type,
    ];

    if (
      isset($block_type)
      && !empty($block_type)
      && $this->blockManager->hasDefinition($block_type)
    ) {
      $block_config = $this->getPluginFormStateValue('block_config', $form_state, []);
      $block_instance = $this->blockManager->createInstance($block_type, $block_config);

      if ($block_instance instanceof PluginFormInterface) {
        $form['block_config'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Block Configuration'),
          '#tree' => TRUE,
        ];
        $subform = [
          '#parents' => array_merge(
          $form['#parents'], ['block_config']
          ),
        ];

        $form['block_config'] += $block_instance->buildConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::validateConfigurationForm($form, $form_state);

    $block_instance = $this->getBlockTypeInstance();

    if ($block_instance instanceof PluginFormInterface) {
      $subform = [
        '#parents' => array_merge(
        $form['#parents'], ['block_config']
        ),
      ];

      $block_instance->validateConfigurationForm(
        $subform,
        SubformState::createForSubform($subform, $form, $form_state)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::submitConfigurationForm($form, $form_state);

    $block_instance = $this->getBlockTypeInstance();

    if ($block_instance instanceof PluginFormInterface) {
      $subform = [
        '#parents' => array_merge(
        $form['#parents'], ['block_config']
        ),
      ];

      $block_instance->submitConfigurationForm(
        $subform,
        SubformState::createForSubform($subform, $form, $form_state)
      );

      $form_state->setValue(
        ['block_config'],
        $block_instance->getConfiguration()
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(
    EntityInterface $entity,
    EntityDisplayInterface $display
  ): array {
    $block = $this->getBlockTypeInstance();

    if (!$block instanceof BlockPluginInterface) {
      return [];
    }
    $element = [];

    if ($block instanceof ContextAwarePluginInterface) {
      try {
        $this->applyPluginRuntimeContexts($block, [
          'display' => EntityContext::fromEntity($display),
          'view_mode' => new Context(ContextDefinition::create('string'), $display->getMode()),
          'entity_extra_field.target_entity' => EntityContext::fromEntity($entity)
        ]);
      }
      catch (\Exception $exception) {
        watchdog_exception('entity_extra_field', $exception);
      }
    }

    if (
      $block->access($this->currentUser)
      && ($build = $block->build())
    ) {
      $element = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block->getConfiguration(),
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
        '#id' => str_replace(':', '-', $block->getPluginId()),
        'content' => $build,
      ];

      CacheableMetadata::createFromRenderArray($element)
        ->merge(CacheableMetadata::createFromRenderArray($element['content']))
        ->addCacheableDependency($block)
        ->applyTo($element);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    if ($block_type_instance = $this->getBlockTypeInstance()) {
      $this->calculatePluginDependencies($block_type_instance);
    }

    return parent::calculateDependencies();
  }

  /**
   * Get block type instance.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block instance; otherwise FALSE if type is not defined.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getBlockTypeInstance(): BlockPluginInterface {
    $config = $this->getConfiguration();

    return $this->blockManager->createInstance(
      $config['block_type'],
      $config['block_config']
    );
  }

  /**
   * Get block type options.
   *
   * @param array $excluded_ids
   *   An array of block ids to exclude.
   *
   * @return array
   *   An array of block type options.
   */
  protected function getBlockTypeOptions(array $excluded_ids = []): array {
    $options = [];

    // There are a couple block ids that are excluded by default as either
    // they're not really needed, or they are causing problems when selected.
    $excluded_ids = [
      'broken',
      'system_branding_block',
    ] + $excluded_ids;

    foreach ($this->blockManager->getDefinitions() as $block_id => $definition) {
      if (
        !isset($definition['admin_label'])
        || in_array($block_id, $excluded_ids, TRUE)
      ) {
        continue;
      }
      $category = $definition['category'] ?? $this->t('Undefined');

      $options[(string) $category][$block_id] = $definition['admin_label'];
    }

    return $options;
  }

}
