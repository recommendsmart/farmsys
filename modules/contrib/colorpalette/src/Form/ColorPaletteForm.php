<?php

namespace Drupal\colorpalette\Form;

use Drupal\colorpalette\ColorPaletteUtility;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that builds the color palette.
 */
class ColorPaletteForm extends FormBase {

  /**
   * The color palette utility.
   *
   * @var \Drupal\colorpalette\ColorPaletteUtility
   */
  protected $colorPalette;

  /**
   * Constructs a new ColorPaletteForm.
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
    return 'colorpalette.colors';
  }

  /**
   * ColorPaletteForm builder.
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

    // Form theme.
    $form['#theme'] = 'colorpalette_colors';

    // Pass selector value to JS as well.
    $form['#attached']['drupalSettings']['colorpalette']['field_selector'] = $field_selector;

    // Sanitize filter tags to be used in DB query.
    $filter_tags = $filter_tags
      ? array_map('intval', explode(',', $filter_tags))
      : [];

    // Get all color published taxonomy-term ids.
    $colors = $this->colorPalette->getPaletteColors($filter_tags);

    $form['palette'] = [];
    if ($colors) {
      foreach ($colors as $id => $color) {
        $hexcode = $color->get('field_colorpalette_hexcode')->value;

        // Create the palette.
        $form['palette'][$id] = [
          'button' => [
            '#type' => 'button',
            '#ajax' => [
              'callback' => '::updateColorState',
              'event' => 'click',
              'progress' => [
                'type' => 'throbber',
                'message' => NULL,
              ],
            ],
            '#attributes' => ['title' => $hexcode],
            '#name' => $id,
          ],
          'hexcode' => [
            '#type' => 'value',
            '#value' => substr($hexcode, 1),
          ],
          'name' => [
            '#type' => 'value',
            '#value' => $color->label(),
          ],
        ];
      }
    }
    else {
      $form['empty_note'] = [
        '#markup' => $this->t('This palette is empty.'),
      ];
    }

    // Submit action items.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Clear button.
    $form['actions']['clear'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear'),
      '#ajax' => [
        'callback' => '::updateColorState',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#name' => 'clear-color',
      '#attributes' => ['class' => ['button', 'small']],
    ];

    // Show 'New Color' button to privileged users.
    if ($this->colorPalette->isAdministerPaletteUser()) {
      $url = Url::fromRoute(
        'colorpalette.new_color',
        [
          'field_selector' => $field_selector,
          'field_type' => $field_type,
          'filter_tags' => !empty($filter_tags) ? implode(',', $filter_tags) : 0,
          'js' => 'nojs',
        ],
        $this->colorPalette->getDialogLinkOptions(['title' => $this->t('Add New Color')])
      );

      $form['actions']['create'] = [
        '#type' => 'link',
        '#title' => $this->t('New Color'),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
            'button-action',
          ],
        ],
        '#url' => $url,
      ];
    }

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
    $build_info = $form_state->getBuildInfo();
    $triggered_btn = $form_state->getTriggeringElement();

    // Data values for 'Clear' color action.
    if ($triggered_btn['#name'] === 'clear-color') {
      $data = [
        'background' => '',
        'html' => '+',
        'value' => '',
      ];
    }
    // Data values for 'Apply' color action.
    else {
      $color = $form['palette'][$triggered_btn['#name']];

      // Note: May be subjected to change,
      // directly using format "$label ($entity_id)" from getMatches()
      // of core/lib/Drupal/Core/Entity/EntityAutocompleteMatcher.php.
      $value = ($build_info['args'][1] == 'entity_reference')
        ? $color['name']['#value'] . ' (' . $triggered_btn['#name'] . ')'
        : '#' . $color['hexcode']['#value'];

      $data = [
        'value' => $value,
        'background' => $color['hexcode']['#value'],
        'html' => '',
      ];
    }

    // Add field selector value.
    $data['selector'] = $build_info['args'][0];

    return $this->colorPalette->generateAjaxResponse($data);
  }

}
