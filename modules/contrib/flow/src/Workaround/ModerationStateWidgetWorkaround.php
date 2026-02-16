<?php

namespace Drupal\flow\Workaround;

use Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Prevents content moderation from manipulating the entity.
 *
 * @internal
 */
final class ModerationStateWidgetWorkaround extends ModerationStateWidget {

  /**
   * The decorated widget.
   *
   * @var \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget
   */
  protected ModerationStateWidget $widget;

  /**
   * Constructs a new ModerationStateWidgetWorkaround object.
   *
   * @param \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget $widget
   *   The widget to be decorated.
   */
  public function __construct(ModerationStateWidget $widget) {
    $this->widget = $widget;
    $this->moderationInformation = $this->widget->moderationInformation;
    $this->entityTypeManager = $this->widget->entityTypeManager;
    $this->currentUser = $this->widget->currentUser;
    $this->validator = $this->widget->validator;
    OptionsSelectWidget::__construct($this->widget->getPluginId(), $this->widget->getPluginDefinition(), $this->widget->fieldDefinition, $this->widget->settings, $this->widget->thirdPartySettings);
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // This override solely exist to note here, that this callback needs to call
    // the method ::formElement() from this decorator instance.
    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   *
   * This is the place where the entity is being manipulated by the moderation
   * state widget. All this workaround class solely exists because of this.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $original_entity = $items->getEntity();

    $default = $this->moderationInformation->getOriginalState($entity);

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = $this->validator->getValidTransitions($original_entity, $this->currentUser);

    $transition_labels = [];
    $default_value = $items->value;
    foreach ($transitions as $transition) {
      $transition_to_state = $transition->to();
      $transition_labels[$transition_to_state->id()] = $transition_to_state->label();
    }

    $element += [
      '#type' => 'container',
      'current' => [
        '#type' => 'item',
        '#title' => $this->t('Current state'),
        '#markup' => $default->label(),
        '#access' => !$entity->isNew(),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
      'state' => [
        '#type' => 'select',
        '#title' => $entity->isNew() ? $this->t('Save as') : $this->t('Change to'),
        '#key_column' => $this->column,
        '#options' => $transition_labels,
        '#default_value' => $default_value,
        '#access' => !empty($transition_labels),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
    ];
    $element['#element_validate'][] = [ModerationStateWidget::class, 'validateElement'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    ModerationStateWidget::validateElement($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    ModerationStateWidget::isApplicable($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->widget->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $this->widget->extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $this->widget->flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function getWidgetState(array $parents, $field_name, FormStateInterface $form_state) {
    return ModerationStateWidget::getWidgetState($parents, $field_name, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function setWidgetState(array $parents, $field_name, FormStateInterface $form_state, array $field_state) {
    ModerationStateWidget::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $this->widget->settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->widget->settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $this->widget->massageFormValues($values, $form, $form_state);
  }

}
