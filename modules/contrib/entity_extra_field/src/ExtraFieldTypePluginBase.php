<?php

namespace Drupal\entity_extra_field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define extra field type plugin base.
 */
abstract class ExtraFieldTypePluginBase extends PluginBase implements ExtraFieldTypePluginInterface {

  use PluginDependencyTrait;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Extra field type view constructor.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    Token $token,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $current_route_match,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
    $this->moduleHandler = $module_handler;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form['#prefix'] = '<div id="extra-field-plugin">';
    $form['#suffix'] = '</div>';

    $form['#parents'] = ['field_type_config'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) : void {
    // Intentionally left empty on base class.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $this->configuration = $form_state->cleanValues()->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return $this->dependencies;
  }

  /**
   * Get extra field plugin ajax.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return array
   *   An array of form elements.
   */
  public function extraFieldPluginAjaxCallback(
    array $form,
    FormStateInterface $form_state
  ): array {
    return $form['field_type_config'];
  }

  /**
   * Get extra field plugin ajax properties.
   *
   * @return array
   *   An array of common AJAX plugin properties.
   */
  protected function extraFieldPluginAjax(): array {
    return [
      'wrapper' => 'extra-field-plugin',
      'callback' => [$this, 'extraFieldPluginAjaxCallback'],
    ];
  }

  /**
   * Get target entity type identifier.
   *
   * @return string|null
   *   A target entity type identifier; otherwise NULL.
   */
  protected function getTargetEntityTypeId(): ?string {
    return $this->currentRouteMatch->getParameter('entity_type_id') ?: NULL;
  }

  /**
   * Get target entity type bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The target entity type bundle object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTargetEntityTypeBundle(): EntityInterface {
    $entity_type_id = $this->getTargetEntityTypeId();

    $bundle_entity_type = $bundle_entity_type = $this->entityTypeManager
      ->getDefinition($entity_type_id)
      ->getBundleEntityType();

    return $this->currentRouteMatch
      ->getParameter($bundle_entity_type);
  }

  /**
   * Get target entity type definition.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The target entity type definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTargetEntityTypeDefinition(): EntityTypeInterface {
    return $this->entityTypeManager->getDefinition(
      $this->getTargetEntityTypeId()
    );
  }

  /**
   * Process the entity token text.
   *
   * @param string $text
   *   The text that contains the token.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that's related to the text; references are based off this.
   *
   * @return string
   *   The process entity token.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processEntityToken(
    string $text,
    ContentEntityInterface $entity
  ): string {
    return $this->token->replace(
      $text,
      $this->getEntityTokenData($entity),
      ['clear' => TRUE]
    );
  }

  /**
   * Get entity token types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $entity_bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of the entity token types.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityTokenTypes(
    EntityTypeInterface $entity_type,
    string $entity_bundle
  ): array {
    $types = array_values($this->getEntityFieldReferenceTypes(
      $entity_type->id(), $entity_bundle
    ));
    $token_type = $entity_type->get('token_type') ?? $entity_type->id();

    if (!in_array($token_type, $types, TRUE)) {
      $types[] = $token_type;
    }

    return $types;
  }

  /**
   * Get entity token data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity instance.
   *
   * @return array
   *   An array of token data.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityTokenData(ContentEntityInterface $entity): array {
    $token_type = $entity->getEntityType()->get('token_type')
      ?? $entity->getEntityTypeId();

    $data[$token_type] = $entity;

    $field_references = $this->getEntityFieldReferenceTypes(
      $entity->getEntityTypeId(), $entity->bundle()
    );

    foreach ($field_references as $field_name => $target_type) {
      if (isset($data[$target_type]) || !$entity->hasField($field_name)) {
        continue;
      }
      $data[$target_type] = $entity->{$field_name}->entity;
    }

    return array_filter($data);
  }

  /**
   * Get entity field reference types.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param string $entity_bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of reference types.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityFieldReferenceTypes(
    string $entity_type_id,
    string $entity_bundle
  ): array {
    $types = [];

    $fields = $this->entityFieldManager->getFieldDefinitions(
      $entity_type_id,
      $entity_bundle
    );

    foreach ($fields as $field_name => $field) {
      if ($field->getType() !== 'entity_reference') {
        continue;
      }
      $definition = $field->getFieldStorageDefinition();
      $target_type = $definition->getSetting('target_type');

      if (!isset($target_type) || in_array($target_type, $types, TRUE)) {
        continue;
      }
      $type_definition = $this->entityTypeManager->getDefinition($target_type);

      if (!$type_definition instanceof ContentEntityTypeInterface) {
        continue;
      }

      $types[$field_name] = $type_definition->get('token_type') ?? $target_type;
    }

    return $types;
  }

  /**
   * Get plugin form state value.
   *
   * @param string|array $key
   *   The element key.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param mixed $default
   *   The default value if nothing is found.
   *
   * @return mixed
   *   The form value; otherwise FALSE if the value can't be found.
   */
  protected function getPluginFormStateValue(
    $key,
    FormStateInterface $form_state,
    $default = NULL
  ) {
    $key = !is_array($key) ? [$key] : $key;

    $inputs = [
      $form_state->cleanValues()->getValues(),
      $this->getConfiguration(),
    ];

    foreach ($inputs as $input) {
      $value = NestedArray::getValue($input, $key, $key_exists);

      if (!isset($value) && !$key_exists) {
        continue;
      }

      return $value;
    }

    return $default;
  }

}
