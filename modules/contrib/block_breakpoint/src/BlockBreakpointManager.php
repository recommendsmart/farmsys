<?php

namespace Drupal\block_breakpoint;

use Drupal\block\BlockInterface;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\layout_builder\Form\ConfigureBlockFormBase;

/**
 * Manages the Block Breakpoint handling.
 */
class BlockBreakpointManager {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * Constructs a new BlockBreakpointManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BreakpointManagerInterface $breakpoint_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * Alters the block configuration form and inject breakpoint_block settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form id.
   */
  public function blockFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\Core\Form\FormInterface $formObject */
    $formObject = $form_state->getFormObject();

    // Support Layout Builder components.
    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $object */
    if ($formObject instanceof ConfigureBlockFormBase) {
      $object = $form_state->getFormObject()->getCurrentComponent();
      array_unshift($form['#submit'], [$this, 'componentSubmit']);
    }
    else {
      /** @var \Drupal\Core\Entity\EntityInterface $object */
      $object = $form_state->getFormObject()->getEntity();

      // Add an entity builder to prepare and set/unset the third party settings.
      $form['#entity_builders'][] = [$this, 'entityBuilder'];
    }

    // The default fallback group will be the one from the default theme.
    $fallbackGroup = $this->configFactory->get('system.theme')->get('default');

    // Load in all available breakpoint groups and breakpoints.
    $defaultGroup = $object->getThirdPartySetting('block_breakpoint', 'breakpoint_group', $fallbackGroup);
    $groups = $this->breakpointManager->getGroups();
    $breakpointOptions = $this->loadBreakpointsAsOptions($defaultGroup);

    // Add enabled checkbox.
    $form['block_breakpoint']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Block Breakpoint'),
      '#description' => t('Conditionally show the block only on the selected breakpoint.'),
      '#default_value' => $object->getThirdPartySetting('block_breakpoint', 'enabled'),
    ];

    // Add the breakpoint group select.
    $form['block_breakpoint']['breakpoint_group'] = [
      '#type' => 'select',
      '#title' => t('Breakpoint group'),
      '#default_value' => $defaultGroup,
      '#options' => $groups,
      '#ajax' => [
        'callback' => [$this, 'updateBreakpointOptions'],
        'wrapper' => 'edit-breakpoint-options',
        'method' => 'replace',
        'event' => 'change',
      ],
      '#states' => [
        'required' => [
          ':input[name="block_breakpoint[enabled]"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="block_breakpoint[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add the breakpoint select.
    $breakpointsValue = [];
    foreach ($object->getThirdPartySetting('block_breakpoint', 'breakpoints', []) as $breakpoint) {
      $breakpointsValue[$breakpoint['breakpoint_id']] = $breakpoint['breakpoint_id'];
    };
    $form['block_breakpoint']['breakpoints'] = [
      '#type' => 'select',
      '#title' => t('Breakpoints'),
      '#default_value' => $breakpointsValue,
      '#options' => $breakpointOptions,
      '#multiple' => TRUE,
      '#prefix' => '<div id="edit-breakpoint-options">',
      '#suffix' => '</div>',
      '#states' => [
        'required' => [
          ':input[name="block_breakpoint[enabled]"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="block_breakpoint[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Update the available breakpoints based on the selected breakpoing group.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The changed breakpoints form element.
   */
  public function updateBreakpointOptions($form, FormStateInterface $form_state) {
    $group = $form_state->getValue('block_breakpoint')['breakpoint_group'];
    $breakpointOptions = $this->loadBreakpointsAsOptions($group);
    $form['block_breakpoint']['breakpoints']['#options'] = $breakpointOptions;
    return $form['block_breakpoint']['breakpoints'];
  }

  /**
   * Load the available breakpoints for a breakpoint group as an options array.
   *
   * @param string $group
   *   The name of the breakpoint group.
   *
   * @return array|string[]
   *   The available breakpoints as an array (breakpoint_id|label).
   */
  protected function loadBreakpointsAsOptions($group) {
    $availableBreakpoints = $this->breakpointManager->getBreakpointsByGroup($group);
    return array_map(function ($breakpoint) {
      return $breakpoint->getLabel();
    }, $availableBreakpoints);
  }

  /**
   * Stores block_breakpoint settings for a component.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function componentSubmit(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\layout_builder\SectionComponent $section_component */
    $section_component = $form_state->getFormObject()->getCurrentComponent();
    $this->storeThirdPartySettings($section_component, $form_state);
  }

  /**
   * Entity builder for blocks to set or unset block_breakpoint settings.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The block.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state
   */
  public function entityBuilder($entity_type, EntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $this->storeThirdPartySettings($entity, $form_state);
  }

  /**
   * Store the third party settings for block_component.
   *
   * @param \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $object
   *   The object to store the third party settings in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function storeThirdPartySettings(ThirdPartySettingsInterface $object, FormStateInterface $form_state) {
    $values = $form_state->getValue('block_breakpoint');
    if (empty($values['enabled'])) {
      $object->unsetThirdPartySetting('block_breakpoint', 'enabled');
      $object->unsetThirdPartySetting('block_breakpoint', 'breakpoint_group');
      $object->unsetThirdPartySetting('block_breakpoint', 'breakpoint');
    }
    else {
      $object->setThirdPartySetting('block_breakpoint', 'enabled', TRUE);
      $object->setThirdPartySetting('block_breakpoint', 'breakpoint_group', $values['breakpoint_group']);
      // Prepare the nested breakpoint structure, that we need to support
      // multiple breakpoints and avoid problems, due breakpoint keys are labeled
      // with dots inside, which messes with the configuration system.
      $breakpoints = [];
      foreach ($values['breakpoints'] as $breakpoint_id) {
        $breakpoints[] = ['breakpoint_id' => $breakpoint_id];
      }
      $object->setThirdPartySetting('block_breakpoint', 'breakpoints', $breakpoints);
    }
  }

  /**
   * Preprocesses the block and prepares the block breakpoint properties.
   *
   * @param array $variables
   *   The preprocess variables.
   */
  public function preprocessBlock(&$variables) {
    // Blocks coming from page manager widget do not have an id.
    if (!empty($variables['elements']['#id'])) {
      $id = $variables['elements']['#id'];
      $block = $this->entityTypeManager->getStorage('block')->load($id);

      // Check if the block_breakpoint feature is enabled.
      if ($block && $block->getThirdPartySetting('block_breakpoint', 'enabled')) {
        $variables['attributes']['class'][] = 'block-breakpoint';
        $variables['#attached']['library'][] = 'block_breakpoint/block_breakpoint';
        $group = $block->getThirdPartySetting('block_breakpoint', 'breakpoint_group');
        $breakpoints = $block->getThirdPartySetting('block_breakpoint', 'breakpoints');
        $variables['attributes']['data-block-breakpoint-media-query'] = $this->buildMediaQueryFromBreakpoints($breakpoints, $group);
        // Add inline script to remove blocks while they are processed in the
        // browser.
        $variables['content'] = [$variables['content']];
        $variables['content'][] = [
          '#theme' => 'block_breakpoint_inline_match',
          '#weight' => -50,
        ];
      }
    }
  }

  /**
   * Preprocesses the layout builder component.
   *
   * @param array $component
   *   The component render array.
   */
  public function preprocessComponent(&$component) {
    if (is_array($component) && !empty($component['#block_breakpoint'])) {
      $component['#attributes']['class'][] = 'block-breakpoint';
      $component['#attached']['library'][] = 'block_breakpoint/block_breakpoint';

      $group = $component['#block_breakpoint']['breakpoint_group'];
      $breakpoints = $component['#block_breakpoint']['breakpoints'];
      $component['#attributes']['data-block-breakpoint-media-query'] = $this->buildMediaQueryFromBreakpoints($breakpoints, $group);
      // Add inline script to remove blocks while they are processed in the
      // browser.
      $component['content'] = [$component['content']];
      $component['content'][] = [
        '#theme' => 'block_breakpoint_inline_match',
        '#weight' => -50,
      ];
    }
  }

  /**
   * Build media query from given breakpoints.
   *
   * @param array $breakpoints
   *   The given breakpoints.
   * @param string $group
   *   The breakpoint group.
   *
   * @return string
   *   THe assembled media query.
   */
  protected function buildMediaQueryFromBreakpoints(array $breakpoints, $group) {
    $available_breakpoints = $this->breakpointManager->getBreakpointsByGroup($group);

    // Loop through the selected breakpoint and build up the media queries.
    $media_queries = [];
    foreach ($breakpoints as $breakpoint) {
      if (array_key_exists($breakpoint['breakpoint_id'], $available_breakpoints)) {
        $media_queries[] = $available_breakpoints[$breakpoint['breakpoint_id']]->getMediaQuery();
      }
    }

    // Build the final media query to check for.
    return implode(', ', $media_queries);
  }

}
