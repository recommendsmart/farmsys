<?php

namespace Drupal\flow\Helpers;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\EntitySerializationTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\FormBuilderTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\TokenTrait;

/**
 * Trait for Flow-related components that configure a content entity.
 */
trait EntityContentConfigurationTrait {

  use EntityFromStackTrait;
  use EntitySerializationTrait;
  use EntityTypeManagerTrait;
  use FormBuilderTrait;
  use ModuleHandlerTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * The configured content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $configuredContentEntity;

  /**
   * The form object used for configuring the content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityFormInterface|null
   */
  protected ?ContentEntityFormInterface $entityForm;

  /**
   * The entity form display to use for configuring the content entity.
   *
   * @var string
   */
  protected string $entityFormDisplay = 'flow';

  /**
   * The data to use for Token replacement.
   *
   * Can be either the subject item ("subject") or the Flow-related entity
   * ("flow"). Default is set to "subject".
   *
   * @var string
   */
  protected string $tokenTarget = 'subject';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    if ($definition = $this->getPluginDefinition()) {
      $values = [];
      $entity_type = $this->getEntityTypeManager()->getDefinition($definition['entity_type']);
      if ($entity_type->hasKey('bundle')) {
        $values[$entity_type->getKey('bundle')] = $definition['bundle'];
      }
      return ['settings' => ['values' => $values]];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token_info'] = [
      '#type' => 'container',
      'allowed_text' => [
        '#markup' => $this->t('Tokens are allowed.') . '&nbsp;',
        '#weight' => 10,
      ],
      '#weight' => -100,
    ];
    $entity_type_id = $this->tokenTarget === 'subject' ? $this->getPluginDefinition()['entity_type'] : ($this->configuration['entity_type_id'] ?? NULL);
    if (isset($entity_type_id) && $this->moduleHandler->moduleExists('token')) {
      $form['token_info']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->getTokenTypeForEntityType($entity_type_id)],
        '#dialog' => TRUE,
        '#weight' => 10,
      ];
    }
    else {
      $form['token_info']['no_browser'] = [
        '#markup' => $this->t('To get a list of available tokens, install the <a target="_blank" rel="noreferrer noopener" href=":drupal-token" target="blank">contrib Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']),
        '#weight' => 10,
      ];
    }
    $form['entity_form_info'] = [
      '#type' => 'container',
      'display_mode' => [
        '#markup' => $this->t('This configuration is using the "@mode" form display mode. <a href=":url" target="_blank">Manage form display modes</a>.', [
          '@mode' => $this->entityFormDisplay,
          ':url' => '/admin/structure/display-modes/form',
        ]),
        '#weight' => 10,
      ],
      '#weight' => -50,
    ];
    // We need to use a process callback for embedding the entity fields,
    // because the fields to embed need to know their "#parents".
    $wrapper_id = Html::getUniqueId('entity-content-fields');
    $entity = $this->getConfiguredContentEntity();
    $form['values'] = [
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#wrapper_id' => $wrapper_id,
      '#flow__entity' => $entity,
      '#flow__form_display' => $this->entityFormDisplay,
      '#process' => [[static::class, 'processContentConfigurationForm']],
    ];
    return $form;
  }

  /**
   * Process callback to insert the content entity form.
   *
   * @param array $element
   *   The containing element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The containing element, with the content entity form inserted.
   */
  public static function processContentConfigurationForm(array $element, FormStateInterface $form_state) {
    $entity = $element['#flow__entity'];
    $form_display_mode = $element['#flow__form_display'];
    $wrapper_id = $element['#wrapper_id'];
    $form_display = EntityFormDisplay::collectRenderDisplay($entity, $form_display_mode);

    // A very special treatment for the moderation state field that is coming
    // from core's content_moderation module. We need to wrap the according
    // widget with a decorator that prevents that widget from manipulating
    // entity values. This happens when the configured moderation state differs
    // from the initial state.
    if ($form_display->getComponent('moderation_state')) {
      $closure = \Closure::fromCallable(function () {
        /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $this */
        $this->getRenderer('moderation_state');
        $workaround_class = 'Drupal\flow\Workaround\ModerationStateWidgetWorkaround';
        $this->plugins['moderation_state'] = new $workaround_class($this->plugins['moderation_state']);
      });
      $closure->call($form_display);
    }

    $content_config_entities = $form_state->get('flow__content_configuration') ?? [];
    $content_config_entities[$wrapper_id] = [$entity, $form_display];
    $form_state->set('flow__content_configuration', $content_config_entities);
    $form_display->buildForm($entity, $element, $form_state);
    if ($entity->getEntityTypeId() === 'user') {
      UserAccount::processUserAccountForm($element, $form_state);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue(['values']) || !isset($form['values']['#wrapper_id'])) {
      return;
    }
    $wrapper_id = $form['values']['#wrapper_id'];
    $content_config_entities = $form_state->get('flow__content_configuration') ?? [];
    if (!isset($content_config_entities[$wrapper_id])) {
      return;
    }

    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;
    [$entity, $form_display] = $content_config_entities[$wrapper_id];
    $extracted = $form_display->extractFormValues($entity, $form['values'], $complete_form_state);
    // Extract the values of fields that are not rendered through widgets, by
    // simply copying from top-level form values. This leaves the fields that
    // are not being edited within this form untouched.
    // @see \Drupal\Tests\field\Functional\NestedFormTest::testNestedEntityFormEntityLevelValidation()
    foreach ($form_state->getValue(['values']) as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }
    $form_display->validateFormValues($entity, $form['values'], $complete_form_state);
  }

  /**
   * Disables an element and all of its child elements.
   *
   * @param array &$element
   *   The render element to disable.
   */
  protected function disableAccessAllElements(array &$element): void {
    $element['#access'] = FALSE;
    foreach (Element::children($element) as $key) {
      $this->disableAccessAllElements($element[$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue(['values']) || !isset($form['values']['#wrapper_id'])) {
      return;
    }
    $wrapper_id = $form['values']['#wrapper_id'];
    $content_config_entities = $form_state->get('flow__content_configuration') ?? [];
    if (!isset($content_config_entities[$wrapper_id])) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    [$entity] = $content_config_entities[$wrapper_id];

    $form_display = EntityFormDisplay::collectRenderDisplay($entity, $this->entityFormDisplay, TRUE);
    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;
    $extracted = $form_display->extractFormValues($entity, $form['values'], $complete_form_state);
    foreach ($form_state->getValue(['values']) as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }

    // Filter field values that are not available on the form display mode.
    $entity_type = $entity->getEntityType();
    $entity_keys = $entity_type->getKeys();
    $components = $form_display->getComponents();
    foreach (array_keys($values) as $k_1) {
      if (!isset($components[$k_1]) && !in_array($k_1, $entity_keys)) {
        unset($values[$k_1]);
      }
    }

    if ($entity->getEntityTypeId() === 'user') {
      UserAccount::submitUserAccountForm($form['values'], SubformState::createForSubform($form['values'], $form, $form_state));
    }

    $this->setConfiguredContentEntity($entity);
    $values = $this->toConfigArray($entity);

    $this->settings['values'] = $values;
  }

  /**
   * Instantiates the configured content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $subject_item
   *   (optional) The current subject item to operate on.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The initialized entity.
   */
  public function initConfiguredContentEntity(?ContentEntityInterface $subject_item = NULL): ContentEntityInterface {
    $flow_is_active = Flow::isActive();
    Flow::setActive(FALSE);
    try {
      $entity_type_id = $this->getPluginDefinition()['entity_type'];
      $values = $this->settings['values'];
      // Apply Token replacement when operating on a subject item.
      if ($subject_item) {
        $token_data = [$this->getTokenTypeForEntityType($subject_item->getEntityTypeId()) => $subject_item];
        if ($this->entityFromStack && ($this->entityFromStack->getEntityTypeId() !== $subject_item->getEntityTypeId())) {
          $token_data[$this->getTokenTypeForEntityType($this->entityFromStack->getEntityTypeId())] = $this->entityFromStack;
        }
        array_walk_recursive($values, function (&$value) use (&$token_data) {
          if (is_string($value) && !empty($value)) {
            $value = $this->tokenReplace($value, $token_data);
          }
        });
      }
      $this->setConfiguredContentEntity($this->fromConfigArray($values, $this->getEntityTypeManager()->getDefinition($entity_type_id)->getClass()));
    }
    finally {
      Flow::setActive($flow_is_active);
    }
    return $this->configuredContentEntity;
  }

  /**
   * Get the configured content entity object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The initialized entity.
   */
  public function getConfiguredContentEntity(): ContentEntityInterface {
    if (!isset($this->configuredContentEntity)) {
      $this->initConfiguredContentEntity();
    }
    return $this->configuredContentEntity;
  }

  /**
   * Set the configured content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to set as configured content entity.
   */
  public function setConfiguredContentEntity(ContentEntityInterface $entity): void {
    $this->configuredContentEntity = $entity;
  }

}
