<?php

namespace Drupal\designs_field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;

/**
 * Provides functionality for wrapper field formatters in a design.
 */
class DesignsFieldWrapper implements TrustedCallbackInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * The design setting manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingManager;

  /**
   * The design content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * DesignsFieldWrapper constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design settings manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   */
  public function __construct(
    DesignManagerInterface $designManager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager
  ) {
    $this->designManager = $designManager;
    $this->settingManager = $settingManager;
    $this->contentManager = $contentManager;
  }

  /**
   * Build the form for the field wrapper.
   *
   * @param \Drupal\Core\Field\WidgetInterface|\Drupal\Core\Field\FormatterInterface $plugin
   *   The field formatter/widget plugin.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration render array.
   */
  public function buildConfigurationForm($plugin, FieldDefinitionInterface $field_definition, array &$form, FormStateInterface $form_state) {
    return [
      '#entity_type' => $field_definition->getTargetEntityTypeId(),
      '#entity_bundle' => $field_definition->getTargetBundle(),
      '#field_name' => $field_definition->getName(),
      '#plugin' => $plugin,
      '#process' => [[$this, 'processConfigurationForm']],
    ];
  }

  /**
   * Defines the wrappers used by the field wrapper behaviour.
   *
   * @return array[]
   *   The wrapper details.
   */
  protected static function getWrappers() {
    return [
      'item' => [
        'label' => t('Item Design'),
        'description' => t('Enables using a design to wrap each field item.'),
        'source' => 'field_item_wrapper',
      ],
      'field' => [
        'label' => t('Field Design'),
        'description' => t('Enables using a design to wrap all field items.'),
        'source' => 'field_wrapper',
      ],
    ];
  }

  /**
   * Process the configuration form to use the plugin form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function processConfigurationForm(array $form, FormStateInterface $form_state) {
    $plugin = $form['#plugin'];
    $form['#design_submit'] = [[$this, 'submitConfigurationForm']];

    $settings = $form_state->getValue($form['#parents']);
    if (empty($settings)) {
      $settings = $plugin->getThirdPartySettings('designs_field') ?: [];
    }

    $parents = $form['#parents'];
    $array_parents = $form['#array_parents'];
    foreach (self::getWrappers() as $key => $wrapper) {
      $design_form = new PluginForm(
        $this->designManager,
        $this->settingManager,
        $this->contentManager,
        $settings[$key]['design'] ?? '',
        $settings[$key] ?? [],
        $wrapper['source'],
        [
          'type' => $form['#entity_type'],
          'bundle' => $form['#entity_bundle'],
          'field' => $form['#field_name'],
        ],
      );

      $form["{$key}_enabled"] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use @label', ['@label' => $wrapper['label']]),
        '#description' => $wrapper['description'],
        '#default_value' => $settings["{$key}_enabled"] ?? FALSE,
      ];

      $name = reset($parents) . '[' . implode('][', array_slice($parents, 1)) . ']';
      $form[$key] = [
        '#parents' => array_merge($parents, [$key]),
        '#array_parents' => array_merge($array_parents, [$key]),
        '#states' => [
          'visible' => [
            ":input[name=\"{$name}[{$key}_enabled]\"]" => ['checked' => TRUE],
          ],
        ],
      ];
      $form[$key] = $design_form->buildForm($form[$key], $form_state);
      $form[$key]['#title'] = $wrapper['label'];
    }

    $form['#element_validate'][] = [$this, 'validateConfigurationForm'];
    return $form;
  }

  /**
   * Validation for ::buildConfigurationForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state) {
    foreach (self::getWrappers() as $key => $wrapper) {
      if ($form_state->getValue($form["{$key}_enabled"]['#parents'])) {
        $form[$key]['#form_handler']->validateForm($form[$key], $form_state);
      }
      else {
        $form_state->unsetValue($form["{$key}_enabled"]['#parents']);
        $form_state->unsetValue($form["{$key}"]['#parents']);
      }
    }
  }

  /**
   * Build the summary for the field wrapper extras.
   *
   * @param \Drupal\Core\Field\WidgetInterface|\Drupal\Core\Field\FormatterInterface $plugin
   *   The field formatter/widget plugin.
   * @param array $summary
   *   The list of summary items.
   *
   * @return array
   *   The updated summary.
   */
  public function buildSummary($plugin, array &$summary) {
    $settings = $plugin->getThirdPartySettings('designs_field');
    foreach (self::getWrappers() as $key => $wrapper) {
      if (!empty($settings[$key]['design'])) {
        if ($this->designManager->hasDefinition($settings[$key]['design'])) {
          $design = $this->designManager->getDefinition($settings[$key]['design']);
          $summary[] = $this->t(
            '@wrapper: @label',
            [
              '@wrapper' => $wrapper['label'],
              '@label' => $design->getLabel(),
            ]
          );
        }
        else {
          $summary[] = $this->t(
            '@wrapper: @label',
            [
              '@wrapper' => $wrapper['label'],
              '@label' => $this->t('Missing design'),
            ]
          );
        }
      }
    }
    return $summary;
  }

  /**
   * Modifies the built entity render array.
   *
   * @param array $build
   *   The build render array.
   * @param array $context
   *   The display build context.
   */
  public function entityDisplayBuildAlter(array &$build, array $context) {
    $this->entityViewAlter($build, $context['entity']);
  }

  /**
   * Modifies the built entity render array.
   *
   * @param array $build
   *   The build render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function entityViewAlter(array &$build, EntityInterface $entity) {
    if (!empty($build['#designs_field'])) {
      return;
    }
    $build['#designs_field'] = TRUE;
    // Process each field for the third party settings.
    foreach (Element::children($build) as $field_name) {
      // For some reason this can be called multiple times on the same
      // system.
      if (!empty($build[$field_name]['#designs_field'])) {
        continue;
      }
      $build[$field_name]['#designs_field'] = TRUE;

      $settings = $build[$field_name]['#third_party_settings']['designs_field'] ?? [];
      // Perform the item level changes.
      if (!empty($settings['item_enabled']) && !empty($settings['item']['design'])) {
        // Only preform the change just before rendering.
        $build[$field_name]['#pre_render'][] = [
          static::class,
          'preRenderItemFormatter',
        ];
      }

      // Perform the field level changes.
      if (!empty($settings['field_enabled']) && !empty($settings['field']['design'])) {
        // Only preform the change just before rendering.
        $build[$field_name]['#pre_render'][] = [
          static::class,
          'preRenderFormatter',
        ];
      }
    }
  }

  /**
   * Modifies the built entity form render array.
   *
   * @param array $form
   *   The build render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return;
    }

    // Get the entity form display.
    $entity = $form_object->getEntity();
    $entity_display = EntityFormDisplay::collectRenderDisplay($entity, $form_object->getOperation());

    // Process each of the entity items.
    foreach (Element::children($form) as $field_name) {
      $field = $entity->{$field_name};
      if (!$field) {
        continue;
      }

      // Get the field display for the field name.
      $field_display = $entity_display->getComponent($field_name);
      $settings = $field_display['third_party_settings']['designs_field'] ?? [];
      if (!empty($settings['item_enabled']) || !empty($settings['field_enabled'])) {
        $form[$field_name] += [
          '#object' => $entity,
          '#entity_type' => $entity->getEntityTypeId(),
          '#bundle' => $entity->bundle(),
          '#field_name' => $field_name,
          '#third_party_settings' => $field_display['third_party_settings'],
        ];

        if (!empty($settings['item']['design'])) {
          $form[$field_name]['#pre_render'][] = [
            static::class,
            'preRenderItemWidget',
          ];
        }

        if (!empty($settings['field']['design'])) {
          $form[$field_name]['#pre_render'][] = [
            static::class,
            'preRenderWidget',
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderFormatter',
      'preRenderItemFormatter',
      'preRenderWidget',
      'preRenderItemWidget',
    ];
  }

  /**
   * Hide the title when rendering form elements.
   *
   * @param array $element
   *   The render element.
   */
  protected static function hideTitle(array &$element) {
    if (isset($element['#title_display'])) {
      $element['#title_display'] = 'hidden';
    }
    foreach (Element::children($element) as $child) {
      self::hideTitle($element[$child]);
    }
  }

  /**
   * Render API: Convert field widget into design.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The design render element.
   */
  public static function preRenderWidget(array $element) {
    $settings = $element['#third_party_settings']['designs_field'];
    /** @var \Drupal\designs\DesignInterface $design */
    $design = \Drupal::service('plugin.manager.design')->createSourcedInstance(
      $settings['field']['design'],
      $settings['field'],
      'field_wrapper',
      [
        'type' => $element['#entity_type'],
        'bundle' => $element['#bundle'],
        'field' => $element['#field_name'],
      ]
    );

    if ($design) {
      // Disable the title and description for widgets processed by designs.
      self::hideTitle($element['widget']);

      // Replace widget with design.
      $element['widget'] += array_intersect_key(
        $element,
        array_flip(['#object', '#entity_type', '#bundle', '#field_name'])
      );
      $element['widget'] = $design->build($element['widget']);
    }

    return $element;
  }

  /**
   * Render API: Convert field widget into design.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The design render element.
   */
  public static function preRenderItemWidget(array $element) {
    $extras = array_intersect_key(
      $element,
      array_flip(['#object', '#entity_type', '#bundle', '#field_name'])
    );

    $settings = $element['#third_party_settings']['designs_field'];
    foreach (Element::children($element) as $delta) {
      /** @var \Drupal\designs\DesignInterface $design */
      $design = \Drupal::service('plugin.manager.design')
        ->createSourcedInstance(
          $settings['item']['design'],
          $settings['item'],
          'field_item_wrapper',
          [
            'type' => $element['#entity_type'],
            'bundle' => $element['#bundle'],
            'field' => $element['#field_name'],
            'delta' => $delta,
          ]
        );
      if ($design) {
        // Disable the title and description for widget items processed by
        // designs.
        self::hideTitle($element[$delta]);

        $element[$delta] += $extras;
        $element[$delta] = ['design' => $design->build($element[$delta])];
      }
    }

    return $element;
  }

  /**
   * Render API: Convert field wrapper into design.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The design render element.
   */
  public static function preRenderFormatter(array $element) {
    $settings = $element['#third_party_settings']['designs_field'];
    /** @var \Drupal\designs\DesignInterface $design */
    $design = \Drupal::service('plugin.manager.design')->createSourcedInstance(
      $settings['field']['design'],
      $settings['field'],
      'field_wrapper',
      [
        'type' => $element['#entity_type'],
        'bundle' => $element['#bundle'],
        'field' => $element['#field_name'],
      ]
    );
    if ($design) {
      return ['design' => $design->build($element)];
    }
    return $element;
  }

  /**
   * Render API: Convert field items into designs.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The design render element.
   */
  public static function preRenderItemFormatter(array $element) {
    $extras = array_intersect_key(
      $element,
      array_flip(['#object', '#entity_type', '#bundle', '#field_name'])
    );

    $settings = $element['#third_party_settings']['designs_field'];
    foreach (Element::children($element) as $delta) {
      /** @var \Drupal\designs\DesignInterface $design */
      $design = \Drupal::service('plugin.manager.design')
        ->createSourcedInstance(
          $settings['item']['design'],
          $settings['item'],
          'field_item_wrapper',
          [
            'type' => $element['#entity_type'],
            'bundle' => $element['#bundle'],
            'field' => $element['#field_name'],
            'delta' => $delta,
          ]
        );
      if ($design) {
        $element[$delta] += $extras + [
          '#item' => $element['#items']->get($delta),
        ];
        $element[$delta] = ['design' => $design->build($element[$delta])];
      }
    }
    return $element;
  }

}
