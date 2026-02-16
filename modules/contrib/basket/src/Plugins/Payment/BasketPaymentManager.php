<?php

namespace Drupal\basket\Plugins\Payment;

use Drupal\basket\Ajax\BasketReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an Basket Payment plugin manager.
 *
 * @see \Drupal\basket\Plugins\Payment\Annotation\BasketPayment
 * @see \Drupal\basket\Plugins\Payment\BasketPaymentInterface
 * @see plugin_api
 */
class BasketPaymentManager extends DefaultPluginManager {

  /**
   * Set Basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Constructs a PaymentManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Basket/Payment',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Payment\BasketPaymentInterface',
      'Drupal\basket\Plugins\Payment\Annotation\BasketPayment'
    );
    $this->alterInfo('basket_payment_info');
    $this->setCacheBackend($cache_backend, 'basket_payment_info_plugins');
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceById(string $id) {
    $defs = $this->getDefinitions();
    if (!isset($defs[$id])) {
      return FALSE;
    }
    return $this->getInstance($defs[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (!$this->providerExists($options['provider'])) {
      return FALSE;
    }
    static $cache;
    if (isset($cache[$options['id']])) {
      return $cache[$options['id']];
    }

    $cls = $options['class'];
    $instance = new $cls();
    // @todo .
    $cache[$options['id']] = $instance;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function extraFieldFormAlter(&$form, &$form_state, $entity) {
    if (empty($this->basket->getSettings('enabled_services', 'payment'))) {
      return [];
    }
		$ajaxSuffix = $entity->basketFormAjaxSuffix ?? '';
    $form_display = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load('node.' . $entity->getType() . '.default');
    if ($form_display->getComponent('basket_payment')) {
      $options = $this->basket->term()->getOptions('payment');
      if (!empty($options)) {
        $activePayments = $this->basket->getSettings('active_services', 'payment');
        foreach ($options as $key => $value) {
          if (empty($activePayments[$key])) {
            if (\Drupal::currentUser()->hasPermission('basket access_page order_page')) {
              $options[$key] .= ' (' . $this->basket->translate()->t('Not active on site') . ')';
            }
            else {
              unset($options[$key]);
            }
          }
          if (!$this->optionAccess($form_state, $key, $entity)) {
            unset($options[$key]);
          }
        }
      }
      if (empty($form_state->getValue('basket_payment'))) {
        if (!empty($entity->id())) {
          $order = $this->basket->orders(NULL, $entity->id())->load();
          if (!empty($order->payment_id)) {
            $form_state->setValue('basket_payment', $order->payment_id);
          }
        }
        else {
          $paymentDefault = $this->basket->getSettings('active_services', 'payment_default');
          if (!empty($paymentDefault) && is_array($paymentDefault)) {
            $form_state->setValue('basket_payment', key($paymentDefault));
          }
        }
      }
      $form['basket_payment'] = [
        '#prefix'       => '<div id="payment_ajax_wrap'.$ajaxSuffix.'">',
        '#suffix'       => '</div>',
        '#type'         => 'container'
      ];
      $paymentWidget = $this->basket->getSettings('enabled_services', 'payment_widget');
			$form['basket_payment'] += [
				'value'         => [
					'#type'         => !empty($paymentWidget) ? $paymentWidget : 'select',
					'#title'        => $this->basket->translate()->t('Payment'),
					'#options'      => $options,
					'#empty_option' => $this->basket->translate()->t('Not specified'),
					'#required'     => !empty($options),
					'#ajax'         => [
						'wrapper'       => 'payment_ajax_wrap'.$ajaxSuffix,
						'callback'      => __CLASS__ . '::ajaxReloadPayment',
						'progress'      => ['type' => 'fullscreen'],
						'disable-refocus' => TRUE
					],
					'#default_value' => $form_state->getValue('basket_payment'),
					'#parents'        => ['basket_payment'],
				],
			];
			// Alter.
	    \Drupal::moduleHandler()->alter('basketPaymentField', $form['basket_payment'], $form_state);
			// ---
	    if (!empty($form['basket_payment']['#validate'])) {
				foreach ($form['basket_payment']['#validate'] as $validate) {
					$form['#validate'][] = $validate;
				}
			}
      
      $_SESSION['payment_tid'] = $form_state->getValue('basket_payment');
      $GLOBALS['cartNotDiscount'] = $this->basket->getSettings('payment_not_discounts', $_SESSION['payment_tid']);
      $this->basket->cart()->reset();
      $this->basket->cart()->getTotalSum();
      
      if (!empty($_SESSION['payment_tid'])) {
        $paymentTerm = $this->basket->term()->load($_SESSION['payment_tid']);
        if (!empty($paymentTerm->description)) {
          $form['basket_payment']['value']['#description'] = $this->basket->translate()->trans(trim($paymentTerm->description));
        }
        if(!empty($GLOBALS['cartNotDiscountMessage'])) {
          $form['basket_payment']['not_discount'] = [
            '#type'       => 'html_tag',
            '#tag'        => 'div',
            '#value'      => $this->basket->translate()->t('When choosing this payment method - discounts on goods do not apply!'),
            '#attributes' => [
              'class'       => ['not-discount-warning', 'description']
            ]
          ];
         }
      }
    }
    if (!empty($entity->basket_admin_process) && !empty($entity->id())) {
      $form['actions']['submit']['#submit'][] = __CLASS__ . '::saveOrderPayment';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxReloadPayment($form, $form_state) {
    $_SESSION['payment_tid'] = $form_state->getValue('basket_payment');
    $GLOBALS['cartNotDiscount'] = \Drupal::getContainer()->get('Basket')->getSettings('payment_not_discounts', $_SESSION['payment_tid']);
    \Drupal::getContainer()->get('Basket')->cart()->reset();
    
    $response = new AjaxResponse();
    
		$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		$ajaxSuffix = $entity->basketFormAjaxSuffix ?? '';
    
    if(!empty($form['basket_payment']['#group'])) {
      unset($form['basket_payment']['#group']);
    }
    $response->addCommand(new BasketReplaceCommand('#payment_ajax_wrap'.$ajaxSuffix, $form['basket_payment']));
    // Alter.
    \Drupal::moduleHandler()->alter('basket_ajaxReloadPayment', $response);
    // ---
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function saveOrderPayment(&$form, $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    if (!empty($entity)) {
      $pid = $form_state->getValue('basket_payment');
      \Drupal::database()->merge('basket_orders_payment')
        ->key([
          'nid'       => $entity->id(),
        ])
        ->updateFields([
          'pid'           => $pid,
        ])
        ->insertFields([
          'nid'           => $entity->id(),
          'pid'           => $pid,
          'payInfo'       => NULL,
          'payUrl'        => NULL,
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function paymentSettingsFormAlter(&$form, $form_state) {
    if (!empty($paymentSettingSystems = $this->getDefinitions())) {
      $form['service'] += [
        '#ajax'     => [
          'wrapper'   => 'basket_payment_settings_form_ajax_wrap',
          'callback'  => __CLASS__ . '::ajaxReload',
        ],
      ];
      $activeSystem = $form_state->getValue(['service']);
      if (empty($activeSystem) && empty($form_state->getValues()) && !empty($form['service']['#default_value'])) {
        $activeSystem = $form['service']['#default_value'];
      }
      if (!empty($activeSystem)) {
        foreach ($paymentSettingSystems as $keySystem => $paymentSettingSystem) {
          if ($keySystem == $activeSystem) {
            $this->getInstanceById($keySystem)->settingsFormAlter($form, $form_state);
            break;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxReload($form, $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsInfoList($tid, $system) {
    $items = [];
    if (!empty($deliverySettingSystems = $this->getDefinitions())) {
      foreach ($deliverySettingSystems as $keySystem => $deliverySettingSystem) {
        if ($keySystem == $system['id']) {
          $items = $this->getInstanceById($keySystem)->getSettingsInfoList($tid);
          break;
        }
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment($entity, $form_state) {
    $order = $form_state->get('BasketOrder');
    if (empty($order)) {
      return FALSE;
    }
    $fields = [
      'nid'           => $entity->id(),
      'pid'           => NULL,
      'payInfo'       => NULL,
      'payUrl'        => NULL,
    ];
    if (!empty($pid = $form_state->getValue('basket_payment'))) {
      $fields['pid'] = $pid;
      $order->payment_id = $pid;
      if (!empty($paySystem = $this->basket->getSettings('payment_services', $pid))) {
        $order->paySystem = $paySystem;
        $payInfo = $this->getInstanceById($paySystem)->createPayment($entity, $order);
        if ($payInfo['payID']) {
          $fields['payInfo'] = $order->paySystem . '|' . $payInfo['payID'];
        }
        if ($payInfo['redirectUrl']) {
          $fields['payUrl'] = $payInfo['redirectUrl'];
        }
      }
    }
    \Drupal::database()->merge('basket_orders_payment')
      ->key([
        'nid'       => $entity->id(),
      ])
      ->fields($fields)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getPayInfo($entity = NULL, $nid = NULL) {
    if (!empty($entity) || !empty($nid)) {
      return \Drupal::database()->select('basket_orders_payment', 'p')
        ->fields('p')
        ->condition('p.nid', (!empty($entity) ? $entity->id() : $nid))
        ->execute()->fetchObject();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function paymentLoad($paySystem, $payId) {
    if (!empty($paymentSettingSystems = $this->getDefinitions())) {
      foreach ($paymentSettingSystems as $keySystem => $system) {
        if ($keySystem == $paySystem) {
          $payInfo = $this->getInstanceById($paySystem)->loadPayment($payId);
          if (!empty($payInfo)) {
            return $payInfo;
          }
          break;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentForm($paySystem, $payment) {
    if (!empty($this->getDefinitions()[$paySystem]) && !empty($payment)) {
      return \Drupal::formBuilder()->getForm(new BasketPaymentForm($paySystem, $payment));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionAccess($form_state, $key, $entity) {
    $access = FALSE;
    if (!empty($delivery = $form_state->getValue(['basket_delivery', 'value']))) {
      if (!empty($deliveryReference = $this->basket->getSettings('payment_delivery_reference', $key))) {
        if (!empty($deliveryReference[$delivery])) {
          $access = TRUE;
        }
      }
      else {
        $access = TRUE;
      }
    }
    else {
      $access = TRUE;
    }
    if ($access && empty($entity->id())) {
      \Drupal::moduleHandler()->alter('basket_payment_option_access', $access, $key, $form_state);
    }
    return $access;
  }

}
/**
 * Base payment form.
 */
class BasketPaymentForm extends FormBase {

  /**
   * Set payment.
   *
   * @var object
   */
  protected $payment;

  /**
   * Set paySystem.
   *
   * @var string
   */
  protected $paySystem;

  /**
   * Set Basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct($paySystem, $payment) {
    $this->paySystem = $paySystem;
    $this->payment = $payment;
    // payment_result.
    $this->payment->result_url = Url::fromRoute('basket.pages', [
      'page_type'     => 'payment_result',
    ], [
      'absolute'      => TRUE,
      'query'         => [
        'payInfo'       => $this->paySystem . '|' . $this->payment->id,
      ],
    ])->toString();
    // callback_url.
    $this->payment->callback_url = Url::fromRoute('basket.pages', [
      'page_type'     => 'payment_callback',
    ], [
      'absolute'      => TRUE,
      'query'         => [
        'payInfo'       => $this->paySystem . '|' . $this->payment->id,
      ],
    ])->toString();
    // cancel_url.
    $this->payment->cancel_url = Url::fromRoute('basket.pages', [
      'page_type'     => 'payment_cancel',
    ], [
      'absolute'      => TRUE,
      'query'         => [
        'payInfo'       => $this->paySystem . '|' . $this->payment->id,
      ],
    ])->toString();
    // ---
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'basket_payment_form';
    $form['timer'] = [
      '#type'         => 'inline_template',
      '#template'     => '<label>{{ label }}</label>
			<div class="payment-loader-container">
				<div class="payment-loader">
					<div class="payment-circle">
						<div class="payment-inner-circle"></div>
						<div class="seconds">5</div>
					</div>
				</div>
			</div>',
      '#context'      => [
        'label'         => $this->basket->Translate()->t('Automatic transition to the payment system through'),
      ],
      '#prefix'       => '<div class="timer_wrap">',
      '#suffix'       => '</div>',
      '#attached'     => [
        'library'       => [
          'basket/payment',
        ],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->translate()->t('Go to payment'),
      ],
    ];
    // Service alter.
    \Drupal::service('BasketPayment')->getInstanceById($this->paySystem)->paymentFormAlter($form, $form_state, $this->payment);
    // ---
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){}

}
