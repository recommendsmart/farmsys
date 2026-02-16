<?php

namespace Drupal\basket\Plugin\Basket\DeliverySettings;

use Drupal\basket\Plugins\DeliverySettings\BasketDeliverySettingsInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delivery options field settings plan.
 *
 * @BasketDeliverySettings(
 *  id              = "basket_options_field_settings",
 *  name            = "DeliverySettings options",
 *  parent_field    = "basket_options_field"
 * )
 */
class DeliveryOptionsFieldSettings implements BasketDeliverySettingsInterface {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormAlter(&$form, $form_state) {
    $tid = $form['tid']['#value'];

    $storage = $form_state->getStorage();
    if (empty($storage['max'])) {
      $storage['max'] = !empty($options = $this->basket->getSettings('delivery_settings', $tid . '.options')) ? count($options) : 1;
      $form_state->setStorage($storage);
    }
    $form['type'] = [
      '#type'         => 'hidden',
      '#value'        => 'delivery',
    ];
    $form['settings_required'] = [
      '#parents'      => ['basket_options_field_settings', 'required'],
      '#type'         => 'checkbox',
      '#title'        => $this->trans->t('Required field'),
      '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.required'),
    ];
    $form['settings_title'] = [
      '#parents'      => ['basket_options_field_settings', 'title'],
      '#type'         => 'textfield',
      '#title'        => implode(' ', [$this->trans->t('Title'), 'EN:']),
      '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.title'),
    ];
    $form['settings_title_display'] = [
      '#parents'      => ['basket_options_field_settings', 'title_display'],
      '#type'         => 'checkbox',
      '#title'        => $this->trans->t('Display title'),
      '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.title_display'),
    ];
    $form['settings'] = [
      '#type'         => 'table',
      '#title'        => $this->trans->t('Options'),
      '#parents'      => ['basket_options_field_settings', 'options'],
      '#prefix'       => '<div id="basket_options_field_settings_ajax_wrap">',
      '#suffix'       => '</div>',
      '#tabledrag'    => [[
        'action'        => 'order',
        'relationship'  => 'sibling',
        'group'         => 'group-order-weight',
      ],
      ],
    ];
    foreach (range(1, $storage['max']) as $key) {
      if (!empty($storage['deleteItems'][$key])) {
        continue;
      }
      $form['settings'][$key] = [
        '#attributes'       => [
          'class'             => ['draggable'],
        ],
        '#weight'           => $this->basket->getSettings('delivery_settings', $tid . '.options.' . $key . '.weight'),
        'handle'            => [
          '#wrapper_attributes' => [
            'class'     => ['tabledrag-handle-td'],
          ],
        ],
        'name'            => [
          '#type'          => 'textfield',
          '#attributes'    => [
            'placeholder'    => implode(' ', [$this->trans->t('Title'), 'EN']),
          ],
          '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.options.' . $key . '.name'),
        ],
        'weight'           => [
          '#type'             => 'number',
          '#attributes'       => [
            'class'             => ['group-order-weight'],
          ],
          '#default_value'    => $this->basket->getSettings('delivery_settings', $tid . '.options.' . $key . '.weight'),
        ],
        'delete'        => [
          '#type'         => 'button',
          '#value'        => 'x',
          '#name'         => 'delete_options_' . $key,
          '#attributes'   => ['class' => ['button--delete']],
          '#deleteKey'    => $key,
          '#ajax'            => [
            'wrapper'        => 'basket_options_field_settings_ajax_wrap',
            'callback'        => __CLASS__ . '::ajaxCallback',
          ],
          '#validate'        => [__CLASS__ . '::deleteOptionValid'],
        ],
      ];
    }
    $form['settings']['add'] = [[
      '#type'            => 'button',
      '#value'        => $this->trans->t('Add option'),
      '#ajax'            => [
        'wrapper'        => 'basket_options_field_settings_ajax_wrap',
        'callback'        => __CLASS__ . '::ajaxCallback',
      ],
      '#validate'        => [__CLASS__ . '::addOptionValid'],
      '#wrapper_attributes' => ['colspan' => 4],
    ],
    ];
    $form['#submit'][] = __CLASS__ . '::formSubmit';
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxCallback($form, $form_state) {
    return $form['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function addOptionValid(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $storage['max']++;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteOptionValid(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($deleteKey = $triggerdElement['#deleteKey'])) {
      $storage['deleteItems'][$deleteKey] = $deleteKey;
    }
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsInfoList($tid) {
    $items = [];
    if (!empty($settings = $this->basket->getSettings('delivery_settings', $tid))) {
      /*required*/
      $items[] = [
        '#type'           => 'inline_template',
        '#template'       => '<b>{{ label }}: </b> {{ value }}',
        '#context'        => [
          'label'           => $this->trans->t('Required field'),
          'value'           => !empty($settings['required']) ? $this->trans->t('yes') : $this->trans->t('no'),
        ],
      ];
      /*title*/
      $items[] = [
        '#type'           => 'inline_template',
        '#template'       => '<b>{{ label }}: </b> {{ value }} {{ translate }}',
        '#context'        => [
          'label'           => $this->trans->t('Title'),
          'value'           => !empty($settings['title']) ? $this->trans->trans(trim($settings['title'])) : NULL,
          'translate'       => !empty($settings['title']) ? $this->trans->getTranslateLink(trim($settings['title'])) : NULL,
        ],
      ];
      /*title_display*/
      $items[] = [
        '#type'           => 'inline_template',
        '#template'       => '<b>{{ label }}: </b> {{ value }} {{ translate }}',
        '#context'        => [
          'label'           => $this->trans->t('Display title'),
          'value'           => !empty($settings['title_display']) ? $this->trans->t('yes') : $this->trans->t('no'),
        ],
      ];
      /*options*/
      $subList = [];
      if (!empty($settings['options'])) {
        foreach ($settings['options'] as $option) {
          $subList[] = [
            'value'         => $this->trans->trans(trim($option['name'])),
            'translate'     => $this->trans->getTranslateLink(trim($option['name'])),
          ];
        }
      }
      $items[] = [
        '#type'           => 'inline_template',
        '#template'       => '<b>{{ label }}: </b>
				<ul>
					{% if subList %}
						{% for item in subList %}
							<li>{{ item.value }} {{ item.translate }}</li>
						{% endfor %}
					{% else %}
						<li>{{ basket_t(\'The list is empty.\') }}</li>
					{% endif %}
				</ul>',
        '#context'        => [
          'label'           => $this->trans->t('Options'),
          'subList'         => $subList,
        ],
      ];
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public static function formSubmit($form, $form_state) {
    $tid = $form_state->getValue('tid');
    if (!empty($tid)) {
      $storage = $form_state->getStorage();
      $setSettings = [
        'required'       => $form_state->getValue([
          'basket_options_field_settings',
          'required',
        ]),
        'title'          => $form_state->getValue([
          'basket_options_field_settings',
          'title',
        ]),
        'title_display'  => $form_state->getValue([
          'basket_options_field_settings',
          'title_display',
        ]),
        'options'        => [],
      ];
      $options = $form_state->getValue([
        'basket_options_field_settings',
        'options',
      ]);
      if (!empty($options)) {
        $keyOption = 1;
        foreach ($options as $key => $optionInfo) {
          if (!empty($storage['deleteItems'][$key])) {
            continue;
          }
          if (empty(trim($optionInfo['name']))) {
            continue;
          }
          $setSettings['options'][$keyOption] = [
            'name'      => !empty($optionInfo['name']) ? trim($optionInfo['name']) : NULL,
            'weight'    => !empty($optionInfo['weight']) ? trim($optionInfo['weight']) : 0,
          ];
          $keyOption++;
        }
      }
      \Drupal::service('Basket')->setSettings('delivery_settings', $tid, $setSettings);
    }
  }

}
