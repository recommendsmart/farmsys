<?php

namespace Drupal\flow\Helpers;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flow\Entity\EntityFallbackRepository;
use Drupal\flow\Flow;

/**
 * Trait for Flow subject plugins making use of a fallback mechanic.
 */
trait FallbackSubjectTrait {

  use EntityTypeManagerTrait;
  use ModuleHandlerTrait;

  /**
   * Adds the form elements for fallback settings.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildFallbackForm(array &$form, FormStateInterface $form_state): void {
    $weight = 1000;
    $form['fallback'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('When the item could not be loaded'),
      '#weight' => $weight++,
    ];
    $fallback_method_options = [];
    foreach ($this->getFallbackMethods() as $method => $info) {
      $fallback_method_options[$method] = $info['label'];
    }
    $form['fallback']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#title_display' => 'invisible',
      '#options' => $fallback_method_options,
      '#default_value' => $this->settings['fallback']['method'] ?? 'nothing',
      '#required' => TRUE,
    ];
  }

  /**
   * Validation callback for fallback settings.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFallbackForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * Submit callback for fallback settings.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitFallbackForm(array &$form, FormStateInterface $form_state): void {
    $this->settings['fallback']['method'] = $form_state->getValue(
      ['fallback', 'method'], 'nothing');
  }

  /**
   * Loads fallback subject items using the specified method.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An iterable data type that allows for traversing on all identified items.
   *   This may be a simple array that holds one or multiple items, but it may
   *   also be a generator that allows traversing on a large amount of items.
   */
  public function getFallbackItems(): iterable {
    $fallback_method = $this->settings['fallback']['method'] ?? 'nothing';
    $available_fallback_methods = $this->getFallbackMethods();
    $fallback_callback = $available_fallback_methods[$fallback_method]['callback'] ?? NULL;
    return isset($fallback_callback) && is_callable($fallback_callback) ? call_user_func($fallback_callback, $fallback_method, $this) : [];
  }

  /**
   * Default callback to load fallback subject items using the specified method.
   *
   * @param string $method
   *   The fallback method, e.g. "create" for creating a new item or "nothing"
   *   for doing nothing.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An iterable data type that allows for traversing on all identified items.
   *   This may be a simple array that holds one or multiple items, but it may
   *   also be a generator that allows traversing on a large amount of items.
   */
  public function doGetFallbackItems(string $method): iterable {
    if ($method !== 'create') {
      // Currently, this method only covers a "create" method that instantiates
      // a new entity and marks it to be saved. This is also called for doing
      // "nothing", and returning an empty list for that case is fine.
      return [];
    }

    $definition = $this->getPluginDefinition();
    $entity_type_id = $definition['entity_type'];
    $values = [];
    $entity_type = $this->getEntityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->hasKey('bundle')) {
      $values[$entity_type->getKey('bundle')] = $definition['bundle'];
    }
    if (!empty($this->settings['entity_uuid']) && Uuid::isValid($this->settings['entity_uuid'])) {
      // When a UUID is specified, use it as identifier.
      $uuid = $this->settings['entity_uuid'];
      if (!isset(EntityFallbackRepository::$items[$uuid])) {
        EntityFallbackRepository::$items[$uuid] = $this->getEntityRepository()->loadEntityByUuid($entity_type_id, $uuid);
      }
      if (!isset(EntityFallbackRepository::$items[$uuid])) {
        $uuid_key = $entity_type->hasKey('uuid') ? $entity_type->getKey('uuid') : 'uuid';
        $values[$uuid_key] = $uuid;
        $item = $this->getEntityTypeManager()->getStorage($entity_type_id)->create($values);
        Flow::needsSave($item, $this);
        EntityFallbackRepository::$items[$uuid] = $item;
      }
      return [EntityFallbackRepository::$items[$uuid]];
    }
    // When nothing else is specified, generate a hash value of the given
    // settings, so that subject plugins that use the same settings will use the
    // same entity within the same process.
    $settings_hash = hash('md4', $entity_type_id . ':' . $definition['bundle'] . ':' . serialize($this->settings));
    if (!isset(EntityFallbackRepository::$items[$settings_hash])) {
      $item = $this->getEntityTypeManager()->getStorage($entity_type_id)->create($values);
      Flow::needsSave($item, $this);
      EntityFallbackRepository::$items[$settings_hash] = $item;
    }
    return [EntityFallbackRepository::$items[$settings_hash]];
  }

  /**
   * Get available fallback methods for this plugin.
   *
   * @return array
   *   An associative array of available methods, keyed by method machine name.
   *   Each value is an associative array containing "label" as translatable
   *   markup for the human-readable label, and "callback" that is a callable
   *   for executing the method.
   */
  public function getFallbackMethods(): array {
    $fallback_methods = [
      'nothing' => [
        'label' => $this->t('Do nothing'),
        'callback' => [$this, 'doGetFallbackItems'],
      ],
      'create' => [
        'label' => $this->t('Create a new item'),
        'callback' => [$this, 'doGetFallbackItems'],
      ],
    ];
    $this->getModuleHandler()->alter('flow_fallback_methods', $fallback_methods, $this);
    return $fallback_methods;
  }

}
