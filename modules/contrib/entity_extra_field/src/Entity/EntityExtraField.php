<?php

namespace Drupal\entity_extra_field\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_extra_field\EntityExtraFieldContextTrait;
use Drupal\entity_extra_field\ExtraFieldTypePluginInterface;

/**
 * Define entity extra field.
 *
 * @ConfigEntityType(
 *   id = "entity_extra_field",
 *   label = @Translation("Extra Field"),
 *   admin_permission = "administer entity extra field",
 *   config_prefix = "extra_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "display_label",
 *     "name",
 *     "description",
 *     "base_entity_type_id",
 *     "base_bundle_type_id",
 *     "field_type_id",
 *     "field_type_config",
 *     "field_type_condition",
 *     "field_conditions_all_pass",
 *     "display"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\entity_extra_field\Form\EntityExtraFieldForm",
 *       "edit" = "\Drupal\entity_extra_field\Form\EntityExtraFieldForm",
 *       "delete" = "\Drupal\entity_extra_field\Form\EntityExtraFieldFormDelete"
 *     },
 *     "list_builder" = "\Drupal\entity_extra_field\Controller\EntityExtraFieldListBuilder"
 *   }
 * )
 */
class EntityExtraField extends ConfigEntityBase implements EntityExtraFieldInterface {

  use StringTranslationTrait;
  use EntityExtraFieldContextTrait;

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $description;

  /**
   * @var array
   */
  public $display = [];

  /**
   * @var string
   */
  public $field_type_id;

  /**
   * @var bool
   */
  public $display_label = FALSE;

  /**
   * @var array
   */
  public $field_type_config = [];

  /**
   * @var array
   */
  public $field_type_condition = [];

  /**
   * @var bool
   */
  public $field_conditions_all_pass = FALSE;

  /**
   * @var string
   */
  public $base_entity_type_id;

  /**
   * @var string
   */
  public $base_bundle_type_id;

  /**
   * @var array
   */
  protected $build_attachments = [];

  /**
   * {@inheritdoc}
   */
  public function id(): ?string {
    if (empty($this->name)
      || empty($this->base_entity_type_id)
      || empty($this->base_bundle_type_id)) {
      return NULL;
    }

    return "{$this->base_entity_type_id}.{$this->base_bundle_type_id}.{$this->name}";
  }

  /**
   * {@inheritdoc}
   */
  public function name(): ?string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function description(): ?string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function displayLabel(): bool {
    return $this->display_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplay(): array {
    return $this->display;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayType(): ?string {
    return $this->getDisplay()['type'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeLabel(): string {
    return $this->getFieldTypePlugin()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypePluginId(): string {
    return $this->field_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypePluginConfig(): array {
    return $this->field_type_config;
  }

  /**
   * {@inheritDoc}
   */
  public function getFieldTypeCondition(): array {
    return $this->field_type_condition;
  }

  /**
   * {@inheritDoc}
   */
  public function getFieldTypeConditionsAllPass(): bool {
    return $this->field_conditions_all_pass;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityTypeId(): string {
    return $this->base_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseBundleTypeId(): ?string {
    return $this->base_bundle_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityType(): EntityTypeInterface {
    return $this->entityTypeManager()->getDefinition(
      $this->getBaseEntityTypeId()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityTypeBundle(): EntityTypeInterface {
    $entity_type = $this->getBaseEntityType();

    return $this->entityTypeManager()->getDefinition(
      $entity_type->getBundleEntityType()
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getBaseEntityContext(): EntityContext {
    $definition = $this->getBaseEntityType();

    $label = $this->t('@entity being viewed', [
      '@entity' => $definition->getLabel(),
    ]);
    $entity_context = EntityContext::fromEntityType($definition, $label);

    $context_definition = $entity_context->getContextDefinition();
    $context_definition->addConstraint('Bundle', [$this->getBaseBundleTypeId()]);

    return $entity_context;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheDiscoveryId(): string {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return "entity_bundle_extra_fields:{$this->getBaseEntityTypeId()}:{$this->getBaseBundleTypeId()}:{$langcode}";
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheRenderTag(): string {
    return "entity_extra_field:{$this->getDisplayType()}.{$this->getBaseEntityTypeId()}.{$this->getBaseBundleTypeId()}";
  }

  /**
   * {@inheritDoc}
   */
  public function getBuildAttachments(): array {
    return $this->build_attachments;
  }

  /**
   * {@inheritDoc}
   */
  public function getActiveFieldTypeConditions(): array {
    return array_filter($this->getFieldTypeCondition(), function ($value) {
      unset($value['id'], $value['negate'], $value['context_mapping']);
      return !$this->isArrayEmpty($value);
    });
  }

  /**
   * {@inheritDoc}
   */
  public function setBuildAttachment($type, array $attachment): self {
    if (!isset($this->build_attachments[$type])) {
      $this->build_attachments[$type] = [];
    }

    $this->build_attachments[$type] = array_replace_recursive(
      $this->build_attachments[$type], $attachment
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build(
    EntityInterface $entity,
    EntityDisplayInterface $display
  ): array {
    $field_type_plugin = $this->getFieldTypePlugin();

    if (!$field_type_plugin instanceof ExtraFieldTypePluginInterface) {
      return [];
    }

    return [
      '#field' => $this,
      '#view_mode' => $display->getMode(),
      '#theme' => 'entity_extra_field',
      'label' => [
        '#plain_text' => $this->displayLabel()
        ? $this->label()
        : NULL,
      ],
      'content' => $field_type_plugin->build($entity, $display),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function hasDisplayComponent(EntityDisplayInterface $display): bool {
    return $display->getComponent($this->name()) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();

    foreach ($this->getActiveFieldTypeConditions() as $plugin_id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
      $condition = $this->conditionPluginManager()
        ->createInstance($plugin_id, $configuration);
      $contexts = Cache::mergeContexts($contexts, $condition->getCacheContexts());
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate(): array {
    $tags = parent::getCacheTagsToInvalidate();

    foreach ($this->getActiveFieldTypeConditions() as $plugin_id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
      $condition = $this->conditionPluginManager()
        ->createInstance($plugin_id, $configuration);
      $tags = Cache::mergeTags($tags, $condition->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritDoc}
   */
  public function hasConditionsBeenMet(
    array $contexts,
    bool $all_must_pass = FALSE
  ): bool {
    $conditions = $this->getActiveFieldTypeConditions();

    if (empty($conditions)) {
      return TRUE;
    }
    $verdicts = [];

    foreach ($this->getActiveFieldTypeConditions() as $plugin_id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
      $condition = $this->conditionPluginManager()
        ->createInstance($plugin_id, $configuration);

      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $this->applyPluginRuntimeContexts($condition, $contexts);
        }
        catch (\Exception $exception) {
          watchdog_exception('entity_extra_field', $exception);
        }
      }
      $verdict = $condition->evaluate();

      if ($verdict && !$all_must_pass) {
        return TRUE;
      }
      $verdicts[] = $verdict;
    }
    $verdicts = array_unique($verdicts);

    return count($verdicts) === 1 && current($verdicts) === TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return (bool) $this->getQuery()
      ->condition('id', "{$this->getBaseEntityTypeId()}.{$this->getBaseBundleTypeId()}.{$name}")
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'edit-form', array $options = []): Url {
    $base_route_name = $this->getBaseRouteName();
    $route_parameters = $this->urlRouteParameters($rel);

    switch ($rel) {
      case 'collection':
        return URL::fromRoute($base_route_name, $route_parameters, $options);

      case 'add-form':
        return Url::fromRoute("{$base_route_name}.add", $route_parameters, $options);

      case 'edit-form':
        return Url::fromRoute("{$base_route_name}.edit", $route_parameters, $options);

      case 'delete-form':
        return Url::fromRoute("{$base_route_name}.delete", $route_parameters, $options);
    }

    throw new \RuntimeException(
      sprintf('Unable to find %s to built a URL.', $rel)
    );
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies(): self {
    parent::calculateDependencies();

    if ($field_type_plugin = $this->getFieldTypePlugin()) {
      $this->calculatePluginDependencies($field_type_plugin);
    }

    return $this;
  }

  /**
   * Determine if the array is completely empty.
   *
   * @param array $array
   *   A single or multidimensional array.
   *
   * @return bool
   *   Return TRUE if empty, otherwise FALSE.
   */
  protected function isArrayEmpty(array $array): bool {
    foreach (NestedArray::filter($array) as $value) {
      if (!empty($value)) {
        return FALSE;
      }

      if (is_array($value)) {
        $this->isArrayEmpty($value);
      }
    }

    return TRUE;
  }

  /**
   * Get field type plugin instance.
   *
   * @return \Drupal\entity_extra_field\ExtraFieldTypePluginInterface
   *   The extra field type plugin.
   */
  protected function getFieldTypePlugin(): ExtraFieldTypePluginInterface {
    return \Drupal::service('plugin.manager.extra_field_type')
      ->createInstance($this->getFieldTypePluginId(), $this->getFieldTypePluginConfig());
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel): array {
    $base_bundle_type_id = $this->getBaseEntityTypeBundle()->id();

    $uri_route_parameters = [];
    $uri_route_parameters[$base_bundle_type_id] = $this->getBaseBundleTypeId();

    switch ($rel) {
      case 'edit-form':
      case 'delete-form':
        $uri_route_parameters[$this->getEntityTypeId()] = $this->id();
        break;
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates(): array {
    $templates = [];
    $ui_base_path = $this->getBaseEntityBundleUiPath();

    $entity_type = $this->getEntityType();
    $entity_handlers = $entity_type->getHandlerClasses();

    if (isset($entity_handlers['form'])) {
      foreach (array_keys($entity_handlers['form']) as $rel) {
        $template_path = "{$ui_base_path}/extra-fields";

        switch ($rel) {
          case 'add':
            $template_path = "{$template_path}/{$rel}";
            break;

          case 'edit':
          case 'delete':
            $template_path = "{$template_path}/{" . $entity_type->id() . "}/{$rel}";
            break;
        }
        $templates[$rel . '-form'] = $template_path;
      }
    }
    $templates['collection'] = "{$ui_base_path}/extra-fields";

    return $templates;
  }

  /**
   * Get base entity bundle UI path.
   *
   * @return string|null
   *   The base entity bundle UI path.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getBaseEntityBundleUiPath(): ?string {
    $base_route = $this
      ->getBaseEntityType()
      ->get('field_ui_base_route');

    if (!isset($base_route)) {
      return NULL;
    }

    $base_route_rel = strtr(
      substr($base_route, strrpos($base_route, '.') + 1),
      ['_' => '-']
    );
    $base_entity_bundle = $this->getBaseEntityTypeBundle();

    if (!$base_entity_bundle->hasLinkTemplate($base_route_rel)) {
      return NULL;
    }

    return $base_entity_bundle->getLinkTemplate($base_route_rel);
  }

  /**
   * Get base entity route name.
   *
   * @return string
   *   The base entity route.
   */
  protected function getBaseRouteName(): string {
    return "entity.{$this->getBaseEntityTypeId()}.extra_fields";
  }

  /**
   * Get entity storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity storage query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getQuery(): QueryInterface {
    return $this->getStorage()->getQuery();
  }

  /**
   * Get entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage interface.
   */
  protected function getStorage(): EntityStorageInterface {
    return $this->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }

  /**
   * Condition plugin manager service.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The condition plugin manager service.
   */
  protected function conditionPluginManager(): PluginManagerInterface {
    return \Drupal::service('plugin.manager.condition');
  }

}
