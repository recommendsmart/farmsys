<?php

namespace Drupal\basket\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\basket\Ajax\BasketReplaceCommand;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Multimodule pages baskets.
 */
class Pages {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set cart.
   *
   * @var Drupal\basket\BasketCart
   */
  protected $cart;

  /**
   * Set basketParams.
   *
   * @var Drupal\basket\Plugins\Params\BasketParamsManager
   */
  protected $basketParams;

  /**
   * Set basketParams.
   *
   * @var Drupal\basket\Plugins\Popup\BasketPopupManager
   */
  protected $popup;

  /**
   * Set pluginBlock.
   *
   * @var object
   */
  protected $pluginBlock;

  /**
   * Set basketParams.
   *
   * @var Drupal\basket\Plugins\Payment\BasketPaymentManager
   */
  protected $basketPayment;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->cart = $this->basket->cart();
    $this->basketParams = \Drupal::getContainer()->get('BasketParams');
    $this->popup = \Drupal::getContainer()->get('BasketPopup');
    $this->pluginBlock = \Drupal::getContainer()->get('plugin.manager.block');
    $this->basketPayment = \Drupal::getContainer()->get('BasketPayment');
  }

  /**
   * {@inheritdoc}
   */
  public function page($page_type = NULL, $page_subtype = NULL) {
    $element = [];
    if (strpos($page_type, '-') !== FALSE) {
      list($page_type, $page_subtype) = explode('-', $page_type);
    }
    switch ($page_type) {
      case'api':
        $response = new AjaxResponse();
        $reload = FALSE;
        switch ($page_subtype) {
          case'change_currency':
            if (!empty($_POST['set_currency'])) {
              $this->basket->currency()->setCurrent($_POST['set_currency']);
              $reload = TRUE;
            }
            break;

          case'add':
            if (!empty($_POST['nid']) && empty($_POST['add_popup']) && empty($_POST['params']) || !empty($_POST['isAddParamsPopup'])) {
              $entity = \Drupal::entityTypeManager()->getStorage('node')->load($_POST['nid']);
              if (!empty($entity)) {
                $entity->basketAddParams = TRUE;
                $addParams = $this->basketParams->getField($entity);
                if (!empty($addParams['#access'])) {
                  $isAddParams = TRUE;
                  $this->popup->isSite(TRUE);
                  $this->popup->openModal(
                    $response,
                    $this->basket->Translate()->t('Extra options'),
                    [
                      '#theme'    => 'basket_add_extra_options_popup',
                      '#info'     => [
                        'form'      => $addParams,
                        'button'    => $this->basketParams->getPopupButton($entity),
                      ],
                    ], [
                      'width' => 400,
                      'class' => ['basket_add_popup_params'],
                    ]
                  );
                }
              }
            }
            if (empty($isAddParams)) {
              $isValid = TRUE;
              $this->basketParams->validParams($response, $isValid, $_POST);
              if ($isValid) {
                $GLOBALS['BasketCartSetItemID'] = 0;
                $this->cart->add($_POST);
                if (!empty($setItem = $this->cart->setItem)) {
                  switch ($this->basket->getSettings('popup_plugin', 'config.add_popup.type')) {
                    case'noty_message':
                      $response->addCommand(new InvokeCommand('.noty_close', 'trigger', ['click']));
                      $response->addCommand(new InvokeCommand(
												NULL,
												'NotyGenerate',
	                      [
													'basket_status', $this->basket->translate()->trans($this->basket->getSettings('popup_plugin', 'config.add_popup.noty_message')),
	                      ]
                      ));
                      break;

                    default:
                      $this->popup->isSite(TRUE);
                      $popupInfo = $this->cart->getPopupInfo();
                      $this->popup->openModal(
                        $response,
                        !empty($popupInfo['popup_title']) ? $popupInfo['popup_title'] : '',
                        [
                          '#theme'        => 'basket_add_popup',
                          '#info'            => $popupInfo,
                        ],
                        [
                          'width' => 400,
                          'class' => ['basket_add_popup'],
                        ]
                      );
                      break;
                  }
                }
                $response->addCommand(new BasketReplaceCommand('[id^="basket-count-block-wrap"]', $this->pluginBlock->createInstance('basket_count')->build()));
                $response->addCommand(new BasketReplaceCommand('#basket_user_discount_wrap', $this->pluginBlock->createInstance('basket_user_discount')->build()));
              }
            }
            break;

          case'change_count':
            $isValid = TRUE;
            if (empty($_POST['nid']) && !empty($_POST['update_id'])) {
              $loadItem = $this->cart->loadItem($_POST['update_id']);
              if (!empty($loadItem)) {
                $_POST['nid'] = $loadItem->nid;
                $_POST['params'] = $loadItem->all_params;
              }
            }
            $this->basketParams->validParams($response, $isValid, $_POST);
            if ($isValid) {
              $this->cart->UpdateCount($_POST);
              $response->addCommand(new BasketReplaceCommand('[id^="basket-count-block-wrap"]', $this->pluginBlock->createInstance('basket_count')->build()));
              $response->addCommand(new BasketReplaceCommand('#basket_user_discount_wrap', $this->pluginBlock->createInstance('basket_user_discount')->build()));
            }
						$this->replaceView($response, $_POST['view']);
            break;

          case'delete_item':
            $this->cart->deleteItem($_POST);
            $response->addCommand(new BasketReplaceCommand('[id^="basket-count-block-wrap"]', $this->pluginBlock->createInstance('basket_count')->build()));
            $response->addCommand(new BasketReplaceCommand('#basket_user_discount_wrap', $this->pluginBlock->createInstance('basket_user_discount')->build()));
            $this->replaceView($response, $_POST['view']);
            break;

          case'cart_clear_all':
            $this->cart->clearAll();
            $response->addCommand(new BasketReplaceCommand('[id^="basket-count-block-wrap"]', $this->pluginBlock->createInstance('basket_count')->build()));
            $response->addCommand(new BasketReplaceCommand('#basket_user_discount_wrap', $this->pluginBlock->createInstance('basket_user_discount')->build()));
            $this->replaceView($response, $_POST['view']);
            break;

          case'load_popup':
            if (!empty($_POST['load_popup'])) {
              switch ($_POST['load_popup']) {
                case'basket_view':
                  $this->popup->isSite(TRUE);
                  $this->popup->openModal(
                    $response,
                    $this->basket->Translate()->t('Basket'),
                    $this->basket->getView('cart_goods', 'cart'),
                    [
                      'width' => 960,
                      'class' => ['basket_popup_view'],
                    ]
                  );
                  break;
              }
            }
            break;

          case'basket_ajax_params':
            if (!empty($_POST['nid'])) {
              $entity = \Drupal::service('entity_type.manager')->getStorage('node')->load($_POST['nid']);
              if (!empty($_POST['current_view'])) {
                list($entity->view_id, $entity->view_current_display, $entity->view_dom_id, $entity->view_args) = explode('=>', $_POST['current_view']);
              }
              $mode = NULL;
              if ( !empty($_POST['display_mode']) ){
                $mode = $_POST['display_mode'];
              }
              if (!empty($entity)) {
                return $this->basketParams->getField($entity, NULL, NULL, $mode);
              }
            }
            break;
        }
        if ($reload) {
          $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
        }
        $element = $response;
        break;

      case'view':
        $_SESSION['delivery_tid'] = NULL;
        if (!\Drupal::currentUser()->hasPermission('basket add_button_access')) {
          throw new AccessDeniedHttpException();
        }
        $orderForm = [];
        if ($this->basket->getSettings('order_page', 'config.view_form')) {
          $orderForm = $this->getOrderForm();
        }
        $element = [
          '#title'        => $this->basket->Translate()->t('Basket'),
          '#cache'        => [
            'max-age'        => 0,
          ],
          '#theme'        => 'basket_page_view',
          '#info'            => [
            'view'            => $this->basket->getView('cart_goods', 'cart'),
            'orderForm'        => $orderForm,
          ],
        ];

        break;

      case'order':
        $_SESSION['delivery_tid'] = NULL;
        if (!\Drupal::currentUser()->hasPermission('basket user_create_order_access')) {
          throw new AccessDeniedHttpException();
        }
        // ---
        $element = [
          '#prefix'        => '<div class="basket-pages-wrap basket-pages-order">',
          '#suffix'        => '</div>',
          '#theme'        => 'basket_page_order',
          '#info'            => [
            'orderForm'        => $this->getOrderForm(),
            'Cart'            => $this->cart,
          ],
          '#title'        => $this->basket->Translate()->t('Checkout'),
          '#cache'        => [
            'max-age'        => 0,
          ],
        ];
        break;

      case'finish':
        $element = [
          '#theme'        => 'basket_page_finish',
          '#info'            => $this->basket->getSettings('templates', 'basket_finish_' . \Drupal::languageManager()->getCurrentLanguage()->getId()),
          '#prefix'        => '<div class="basket-pages-wrap basket-pages-finish">',
          '#suffix'        => '</div>',
          '#cache'        => [
            'max-age'        => 0,
          ],
        ];
        break;

      case'payment':
        $isPayPage = FALSE;
        if (!empty($payInfo = \Drupal::request()->query->get('payInfo'))) {
          $payInfo = unserialize(base64_decode($payInfo));
          if (!empty($payInfo['paySystem']) && !empty($payInfo['payId'])) {
            $paymentInfo = $this->basketPayment->paymentLoad($payInfo['paySystem'], $payInfo['payId']);
            if (!empty($paymentInfo['payment'])) {
              if (empty($paymentInfo['isPay'])) {/*Not yet paid*/
                $element['form'] = $this->basketPayment->getPaymentForm($payInfo['paySystem'], $paymentInfo['payment']);
              }
              $element['#title'] = $this->basket->Translate()->t('Payment');
              $isPayPage = TRUE;
            }
          }
        }
        if (!$isPayPage) {
          throw new NotFoundHttpException();
        }
        $element['#cache']['max-age'] = 0;
        break;

      case'payment_callback':
      case'payment_result':
      case'payment_cancel':
        if (!empty($payInfo = \Drupal::request()->query->get('payInfo'))) {
          @list($paySystem, $payId) = explode('|', $payInfo);
          if (!empty($paySystem)) {
            switch ($page_type) {
              case'payment_callback':
                $element = $this->basketPayment->getInstanceById($paySystem)->basketPaymentPages('callback');
                if (!empty($_GET['payment_success']['nid'])) {
                  $this->basket->paymentFinish($_GET['payment_success']['nid']);
                }
                break;

              case'payment_result':
                $element['page'] = $this->basketPayment->getInstanceById($paySystem)->basketPaymentPages('result');
                break;

              case'payment_cancel':
                $element['page'] = $this->basketPayment->getInstanceById($paySystem)->basketPaymentPages('cancel');
                break;
            }
          }
        }
        $element['#cache']['max-age'] = 0;
        break;
    }
    /*Alter*/
    \Drupal::moduleHandler()->alter('basket_pages', $element, $page_type, $page_subtype);
    /*END Alter*/
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderForm() {
    if (\Drupal::currentUser()->hasPermission('basket user_create_order_access')) {
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
          'type'        => 'basket_order',
          'status'      => FALSE,
          'title'       => 'Order',
        ]);
      $node->enforceIsNew();
      // Tokens.
      $default_values = $this->basket->getSettings('order_form', 'config.default_values');
      if (!empty($default_values)) {
        foreach ($default_values as $fieldName => $token) {
          if (empty(trim($token))) {
            continue;
          }
          if (!isset($node->{$fieldName})) {
            continue;
          }
          $node->set($fieldName, \Drupal::token()->replace($token, [], ['clear' => TRUE]));
        }
      }
      /*Alter*/
      \Drupal::moduleHandler()->alter('basket_order_tokenDefaultValue', $node);
      /*END Alter*/
      $formMode = 'default';
      $formModeSettings = $this->basket->getSettings('order_form', 'config.form_mode');
      if(!empty($formModeSettings) && !empty(\Drupal::service('entity_display.repository')->getFormModes('node')[$formModeSettings])) {
        $formMode = $formModeSettings;
      }
      return \Drupal::service('entity.form_builder')->getForm($node, $formMode);
    }
    return [];
  }
	
	public function replaceView($response, $view) {
		$selector = 'view_wrap-' . $view['id'] . '-' . $view['display'];
		$funcArgs = [
			$view['id'],
			$view['display']
		];
		if(!empty($view['args'])) {
			$selector .= '-'.implode('-', $view['args']);
			$funcArgs = array_merge($funcArgs, $view['args']);
		}
		$response->addCommand(
			new BasketReplaceCommand(
				'[data-cartid="' . $selector . '"]',
				call_user_func_array([$this->basket, 'getView'], $funcArgs)
			)
		);
		$response->addCommand(new InvokeCommand(
			NULL,
			'BasketReattachBehaviors',
			[]
		));
	}

}
