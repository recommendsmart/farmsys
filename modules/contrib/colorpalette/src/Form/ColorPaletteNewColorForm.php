<?php

namespace Drupal\colorpalette\Form;

use Drupal\colorpalette\ColorPaletteUtility;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to reuse or create a new color for the color palette.
 */
class ColorPaletteNewColorForm extends FormBase {

  /**
   * The color palette utility.
   *
   * @var \Drupal\colorpalette\ColorPaletteUtility
   */
  protected $colorPalette;

  /**
   * Constructs a new ColorPaletteNewColorForm.
   *
   * @param \Drupal\colorpalette\ColorPaletteUtility $colorpalette_utility
   *   The color palette utility.
   */
  public function __construct(ColorPaletteUtility $colorpalette_utility) {
    $this->colorPalette = $colorpalette_utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('colorpalette.utility')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colorpalette.new_color';
  }

  /**
   * ColorPaletteNewColorForm builder.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $field_selector
   *   The field selector to identify the field.
   * @param string $field_type
   *   The field type.
   * @param string $filter_tags
   *   Comma separated filter tags.
   * @param string $js
   *   Identifier if javascript is enabled in browser.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_selector = NULL, $field_type = NULL, $filter_tags = NULL, $js = NULL) {

    // Handle if browser does not support Javascript.
    if ($js == 'nojs') {
      $this->messenger()->addWarning($this->t('Javascript is disabled in your browser. For %name to work properly please enable it.', ['%name' => 'Color Palette']));
      return $form;
    }

    // Pass selector value to JS as well.
    $form['#attached']['drupalSettings']['colorpalette']['field_selector'] = $field_selector;

    // Sanitize filter tags to be used in DB query.
    $filter_tags = $filter_tags
      ? array_map('intval', explode(',', $filter_tags))
      : [];

    $form['filter_tags'] = [
      '#type' => 'value',
      '#value' => $filter_tags,
    ];

    $default_filter_tags = !empty($filter_tags)
      ? $this->colorPalette->loadColor($filter_tags)
      : NULL;

    $form['new_hexcode'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#required' => TRUE,
    ];
    $form['new_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['new_filter_tags'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Filter Tags'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['colorpalette_filter_tags']],
      '#default_value' => $default_filter_tags,
      '#description' => $this->t('Filter tags will be merged with what configured at field level intially. Also, if color value alreay exists then that color will be used and filter tags will be adjusted accordingly.'),
      '#tags' => TRUE,
    ];

    // Submit action items.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Apply Button.
    $form['actions']['create'] = [
      '#type' => 'button',
      '#ajax' => [
        'callback' => '::updateColorState',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#value' => $this->t('Submit & Apply'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Submit handler to update field color state.
   *
   * @param array $form
   *   The form components.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateColorState(array $form, FormStateInterface $form_state) {
    $submitted = $form_state->getValues();
    $build_info = $form_state->getBuildInfo();

    // Show form again in case last submission failed.
    if ($form_state->getErrors()) {
      // Note: Deleting error messages as every message is printing twice.
      $this->messenger()->deleteAll();

      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new OpenModalDialogCommand(
        $this->t('Add New Color'),
        $form,
        $this->colorPalette->getDataDialogOptions()
      ));

      return $ajax_response;
    }

    $new_hexcode = $submitted['new_hexcode'];
    $new_name = $submitted['new_name'];
    $new_filter_tags = $submitted['new_filter_tags'] ?? [];
    $filter_tags = $submitted['filter_tags'];

    // When color exists with given hexcode exists.
    if ($color_tid = $this->colorPalette->isColorExisting($new_hexcode)) {
      $update_color = FALSE;

      // Load the existing color.
      $target_color = $this->colorPalette->loadColor($color_tid);
      $new_name = $target_color->label();

      // Consider filter tags already applied to the existing color.
      $color_filter_tags = $target_color->field_colorpalette_filter_tags->getValue();

      $tag_list = [];
      // When filter tag inputs are passed through the form.
      if ($new_filter_tags && !empty($new_filter_tags)) {
        // Merge existing color filter tags with the input tags.
        $new_filter_tags = array_merge($color_filter_tags, $new_filter_tags);

        // Collect target ids from the merged $new_filter_tags.
        $tag_list = $this->colorPalette->extractTargetIds($new_filter_tags);

        // Lastly, merge field-configuration filter tags.
        $tag_list = array_unique(array_merge($filter_tags, $tag_list));
      }
      // When filter tag inputs are NOT passed.
      elseif ($filter_tags && $color_filter_tags && !empty($color_filter_tags)) {
        // Collect target ids from merged $new_filter_tags.
        $tag_list = $this->colorPalette->extractTargetIds($color_filter_tags);

        // Merge field-configuration filter tags.
        $tag_list = array_unique(array_merge($filter_tags, $tag_list));
      }
      else {
        $tag_list = $filter_tags;
      }

      // Final filter tags.
      $new_filter_tags = [];
      foreach ($tag_list as $tag) {
        $new_filter_tags[] = ['target_id' => $tag];
      }

      // Publish the color if unpublished.
      if (!$target_color->status->value) {
        $target_color->set('status', 1);
        $update_color = TRUE;
      }

      // Update the merged filter tags.
      if (!empty($new_filter_tags)) {
        $target_color->set('field_colorpalette_filter_tags', $new_filter_tags);
        $update_color = TRUE;
      }

      if ($update_color) {
        $target_color->save();
      }
    }
    else {
      $tag_list = [];

      // When filter tag inputs are passed through the form.
      if ($new_filter_tags && !empty($new_filter_tags)) {
        // Collect target ids from $new_filter_tags.
        $tag_list = $this->colorPalette->extractTargetIds($new_filter_tags);

        // Merge field-configuration filter tags.
        $tag_list = array_unique(array_merge($filter_tags, $tag_list));
      }
      else {
        $tag_list = $filter_tags;
      }

      // Final filter tags.
      $new_filter_tags = [];
      foreach ($tag_list as $tag) {
        $new_filter_tags[] = ['target_id' => $tag];
      }

      // Create a new color.
      $target_color = $this->colorPalette->createNewColor(
        $new_hexcode,
        $new_name,
        $new_filter_tags
      );
    }

    // Note: May be subjected to change,
    // directly using format "$label ($entity_id)" from getMatches()
    // of core/lib/Drupal/Core/Entity/EntityAutocompleteMatcher.php.
    $value = ($build_info['args'][1] == 'entity_reference')
      ? $new_name . ' (' . $target_color->id() . ')'
      : $new_hexcode;

    $data = [
      'value' => $value,
      'selector' => $build_info['args'][0],
      'background' => substr($new_hexcode, 1),
      'html' => '',
    ];

    return $this->colorPalette->generateAjaxResponse($data);
  }

}
