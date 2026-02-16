<?php

namespace Drupal\designs_entity;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\designs\DesignManagerInterface;

/**
 * Manages the entity display view.
 */
class DesignsEntityDisplayHandler {

  use DependencySerializationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * DesignDisplayHandler constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $manager
   *   The design manager.
   */
  public function __construct(DesignManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Implements hook_entity_view_alter().
   *
   * @param array $build
   *   The build render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display.
   */
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    if ($display instanceof ThirdPartySettingsInterface) {
      // Layout builder has been enabled so ignore the design.
      if ($display->getThirdPartySetting('layout_builder', 'enabled')) {
        return;
      }

      // Get the design definitions.
      $configuration = $display->getThirdPartySettings('designs_entity');
      if (empty($configuration['design'])) {
        return;
      }

      $source = [
        'type' => $display->getTargetEntityTypeId(),
        'bundle' => $display->getTargetBundle(),
        'form' => FALSE,
      ];

      $design = $this->manager->createSourcedInstance(
        $configuration['design'],
        $configuration,
        'entity',
        $source
      );

      if ($design) {
        $element = $design->build($build);
        foreach (Element::children($build) as $child) {
          unset($build[$child]);
        }
        $build['design'] = $element;

        // Remove normal wrapper.
        unset($build['#theme']);
        unset($build['#pre_render']);
      }
    }
  }

  /**
   * Implements hook_entity_prepare_form().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function entityPrepareForm(EntityInterface $entity, $operation, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (!empty($storage['form_display']) && $storage['form_display'] instanceof ThirdPartySettingsInterface) {
      /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display */
      $display = $storage['form_display'];
      $configuration = $display->getThirdPartySettings('designs_entity');
      if (empty($configuration['design'])) {
        return;
      }

      $configuration['source'] = [
        'type' => $display->getTargetEntityTypeId(),
        'bundle' => $display->getTargetBundle(),
        'form' => TRUE,
      ];

      // Store the design and configuration.
      $form_state->set('entity', $entity);
      $form_state->set('entity_design', $configuration);
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    // We set the design for the form alteration.
    if ($form_state->has('entity_design')) {
      $form['#process'][] = [$this, 'entityProcess'];
    }
  }

  /**
   * Performs alterations of the render array based on the design.
   *
   * @param array $element
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form.
   */
  public function entityProcess(array $element, FormStateInterface $form_state) {
    $configuration = $form_state->get('entity_design');

    // Remove all targets of groups and the meta.
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#group'])) {
        unset($element[$element[$key]['#group']]);
        unset($element[$key]['#group']);
      }
    }
    unset($element['meta']);

    // Add the element for context.
    $entity = $form_state->get('entity');
    $element['#' . $entity->getEntityTypeId()] = $entity;

    $design = $this->manager->createSourcedInstance(
      $configuration['design'],
      $configuration,
      'entity',
      $configuration['source'],
    );
    if (!$design) {
      return $element;
    }
    $build = $design->build($element);

    // Copy all other settings of the element.
    $entity_form = [];
    foreach ($element as $key => $value) {
      if (substr($key, 0, 1) === '#') {
        $entity_form[$key] = $value;
      }
    }

    // Convert the entity form theme to design.
    $entity_form['#theme'] = 'design';

    // Ensure the hidden elements and token, as they are never selected as part
    // of the design.
    return $entity_form + $build + $this->getHiddenElements($element);
  }

  /**
   * Get the hidden elements for a form.
   *
   * Hidden elements are not chosen via the field ui, so these should always
   * be provided as part of the form actions.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   The hidden elements.
   */
  protected function getHiddenElements(array $form) {
    $hidden_types = ['hidden', 'token'];
    $hidden = [];
    foreach ($form as $key => $element) {
      if (is_array($element) && !empty($element['#type']) && in_array($element['#type'], $hidden_types)) {
        $hidden[$key] = $element;
      }
    }
    return $hidden;
  }

}
