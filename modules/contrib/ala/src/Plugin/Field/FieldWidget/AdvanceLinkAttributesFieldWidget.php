<?php

namespace Drupal\ala\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ala_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "ala_field_widget",
 *   label = @Translation("Advance Link Attributes"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class AdvanceLinkAttributesFieldWidget extends LinkWidget implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'ala_link_class_settings' => '',
      'ala_link_class' => '',
      'ala_link_icon' => '',
      'ala_link_color' => '',
      'ala_link_target' => 1,
      'ala_link_roles' => 'all',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $item = $this->getLinkItem($items, $delta);
    $options = $item->get('options')->getValue();

    $targets_available = [
      '_self' => 'Current window (_self)',
      '_blank' => 'New window (_blank)',
      '_parent' => 'Parent window (_parent)',
      '_top' => 'Topmost window (_top)',
    ];
    if (($this->getSetting('ala_link_target'))) {
      $default_value = !empty($options['attributes']['target']) ? $options['attributes']['target'] : '';
      $element['options']['attributes']['target'] = [
        '#type' => 'select',
        '#title' => $this->t('Select a target'),
        '#options' => ['' => $this->t('- None -')] + $targets_available,
        '#default_value' => $default_value,
        '#description' => $this->t('Select a link behavior. <em>_self</em> will open the link in the current window. <em>_blank</em> will open the link in a new window or tab. <em>_parent</em> and <em>_top</em> will generally open in the same window or tab, but in some cases will open in a different window.'),
      ];
    }
    if (($this->getSetting('ala_link_icon'))) {
      $icon = !empty($options['icon']) ? $options['icon'] : '';
      $element['options']['icon'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Icon Class'),
        '#default_value' => $icon,
        '#description' => $this->t('Icon Class, fal fa-icon'),
      ];
    }

    if (($this->getSetting('ala_link_color'))) {
      $form['#attached']['library'][] = 'ala/color';
      $color = !empty($options['color']) ? $options['color'] : '';
      $element['options']['color'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Text Color'),
        '#attributes' => ['class' => ['alaColorField']],
        '#default_value' => $color,
      ];
      $bgColor = !empty($options['bgcolor']) ? $options['bgcolor'] : '';
      $element['options']['bgcolor'] = [
        '#type' => 'textfield',
        '#title' => $this->t('BG Color'),
        '#attributes' => ['class' => ['alaColorField']],
        '#default_value' => $bgColor,
      ];
    }

    if ($this->getSetting('ala_link_roles')) {
      $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
      $system_roles = array_map(
        function (RoleInterface $a) {
          return $a->label();
        }, $roles);

      $default_value = !empty($options['roles']) ? $options['roles'] : '';
      $element['options']['roles'] = [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => $this->t('Visible for'),
        '#options' => [
          'all' => $this->t('- Everyone -'),
          'authenticated' => $this->t('- Logged -'),
        ] + $system_roles,
        '#default_value' => $default_value,
      ];
    }

    $class_settings = $this->getSetting('ala_link_class_settings');
    if (!empty($class_settings)) {

      switch ($class_settings) {
        case 'global':
          $config = Drupal::config('ala.settings');
          $classes_available = $this->getSelectOptions($config->get('ala_default_classes'));
          break;

        case 'custom':
          $classes_available = $this->getSelectOptions($this->getSetting('ala_link_class'));
          break;

        default:
          $classes_available = [];
          break;
      }

      $default_value = !empty($options['class']) ? $options['class'] : '';
      $element['options']['class'] = [
        '#type' => 'select',
        '#title' => $this->t('Select a style'),
        '#options' => ['' => $this->t('- None -')] + $classes_available,
        '#default_value' => $default_value,
      ];
    }

    return $element;
  }

  /**
   * Getting link items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Returning of field items.
   * @param string $delta
   *   Returning field delta with item.
   *
   * @return \Drupal\link\LinkItemInterface
   *   Returning link items inteface.
   */
  private function getLinkItem(FieldItemListInterface $items, $delta) {
    return $items[$delta];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['ala_link_class_settings'] = [
      '#type' => 'select',
      '#title' => $this->t('Class Settings'),
      '#default_value' => $this->getSetting('ala_link_class_settings'),
      '#options' => [
        '' => $this->t('Disabled'),
        'global' => $this->t('Global List'),
        'custom' => $this->t('Custom List'),
      ],
    ];

    $element['ala_link_class'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Define possibles classes'),
      '#default_value' => $this->getSetting('ala_link_style'),
      '#description' => $this->selectClassDescription(),
      '#attributes' => [
        'placeholder' => 'btn btn-default|Default button' . PHP_EOL . 'btn btn-primary|Primary button',
      ],
      '#size' => '30',
      '#states' => [
        'visible' => [
          [
            [':input[name="fields[field_link][settings_edit_form][settings][ala_link_class_settings]"]' => ['value' => 'custom']],
          ],
        ],
      ],
    ];

    $element['ala_link_icon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Icon'),
      '#default_value' => $this->getSetting('ala_link_icon'),
    ];
    $element['ala_link_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable User Roles'),
      '#default_value' => $this->getSetting('ala_link_roles'),
    ];
    $element['ala_link_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Custom BG & Color'),
      '#default_value' => $this->getSetting('ala_link_color'),
    ];
    $element['ala_link_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Target'),
      '#default_value' => $this->getSetting('ala_link_target'),
    ];

    return $element;
  }

  /**
   * Return the description for the class select mode.
   */
  protected function selectClassDescription() {
    return $this->t('<p>The possible classes this link can have. Enter one value per line, in the format key|label.
    <br/>The key is the string which will be used as a class on a link. The label will be used on edit forms.
    <br/>If the key contains several classes, each class must be separated by a <strong>space</strong>.
    <br/>The label is optional: if a line contains a single string, it will be used as key and label.</p>');
  }

  /**
   * Convert textarea lines into an array.
   *
   * @param string $string
   *   The textarea lines to explode.
   * @param bool $summary
   *   A flag to return a formatted list of classes available.
   *
   * @return array
   *   An array keyed by the classes.
   */
  protected function getSelectOptions($string, $summary = FALSE) {
    $options = [];
    $lines = preg_split("/\\r\\n|\\r|\\n/", trim($string));
    $lines = array_filter($lines);

    foreach ($lines as $line) {
      list($class, $label) = explode('|', trim($line));
      $label = $label ?: $class;
      $options[$class] = $label;
    }

    if ($summary) {
      return implode(', ', array_keys($options));
    }
    return $options;
  }

}
