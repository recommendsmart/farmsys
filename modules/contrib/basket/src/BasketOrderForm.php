<?php

namespace Drupal\basket;

use Drupal\Core\Form\FormStateInterface;
use Drupal\basket\Admin\Page\Order;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class BasketOrderForm {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set phoneMaskSettings.
   *
   * @var string
   */
  protected static $phoneMaskSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    self::$phoneMaskSettings = $this->basket->getSettings('order_form', 'config.phone_mask');
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(&$form, &$form_state) {
    $form['status_messages'] = [
      '#type'                => 'status_messages',
    ];
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		$ajaxSuffix = $entity->basketFormAjaxSuffix ?? '';
    // Extra fields.
    \Drupal::service('BasketDelivery')->extraFieldFormAlter($form, $form_state, $entity);
    \Drupal::service('BasketPayment')->extraFieldFormAlter($form, $form_state, $entity);
    // Form ajax.
    $form['#prefix'] = '<div id="basket_node_basket_order_form_ajax_wrap'.$ajaxSuffix.'">';
    $form['#suffix'] = '</div>';
    hide($form['status']);
    hide($form['advanced']);
    if (!empty($form['title'])) {
      $form['title']['#access'] = FALSE;
    }
    if (!empty($entity->basket_admin_process)) {
      $order = new Order($entity->basket_admin_process->id);
      $order->nodeFormAlter($form, $form_state);
    }
    else {
      // Form submit.
      if (empty($entity->id()) && !empty($submitButtonText = $this->basket->getSettings('order_form', 'config.submit_button'))) {
        $form['actions']['submit']['#value'] = $this->basket->Translate()->trans(trim($submitButtonText));
      }
      $form['actions']['submit']['#ajax'] = [
        'wrapper'       => 'basket_node_basket_order_form_ajax_wrap'.$ajaxSuffix,
        'callback'      => [static :: class, 'submitAjax'],
        'event'         => 'click',
        'progress'      => ['type' => 'fullscreen'],
        'disable-refocus' => TRUE
      ];
      if (empty($entity->id())) {
        $form['actions']['submit']['#submit']['basketInsert'] = [static :: class, 'insertSubmit'];
      }
      if (!\Drupal::currentUser()->hasPermission('basket order_access')) {
        $form['#validate']['empty_validate'] = [static :: class, 'emptyOrderValidate'];
      }
    }
    // Phone mask.
    if (!empty(self::$phoneMaskSettings['field']) && !empty($form[self::$phoneMaskSettings['field']]['widget'][0]['value'])) {
      $form['#attached']['library'][] = 'basket/jquery.inputmask';
      $form[self::$phoneMaskSettings['field']]['widget'][0]['value']['#attributes']['class'][] = 'js-basket-input-mask';
      $form[self::$phoneMaskSettings['field']]['widget'][0]['value']['#attributes']['data-inputmask'] = '\'mask\': \'' . trim(self::$phoneMaskSettings['mask']) . '\'';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function submitAjax($form, $form_state) {
    $response = new AjaxResponse();
		$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $response->addCommand(new HtmlCommand('#' . $form['actions']['submit']['#ajax']['wrapper'], $form));
    }
    else {
      if (!empty($entity->basket_admin_process)) {
        // Admin order edit/create.
        \Drupal::messenger()->deleteAll();
        $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
          'status',
          \Drupal::service('Basket')->Translate()->t('Settings saved.'),
        ]));
        // Return form.
        unset($form['#prefix'], $form['#suffix']);
        $response->addCommand(new HtmlCommand('#' . $form['actions']['submit']['#ajax']['wrapper'], $form));
        $response->addCommand(new InvokeCommand('input[name="changed"]', 'val', [$entity->get('changed')->value]));
      }
      else {
        $_SESSION['basket_finish_nid'] = $entity->id();
        // Redirect.
        $redirect = \Drupal::service('Basket')->getSettings('order_form', 'config.submit_redirect');
        // Payment redirect.
        $getPaymentInfo = \Drupal::service('BasketPayment')->getPayInfo($entity);
        $paySystem = $payId = NULL;
        if (!empty($getPaymentInfo->payInfo)) {
          $redirect = 'payment';
          list($paySystem, $payId) = explode('|', $getPaymentInfo->payInfo);
          if (!empty($getPaymentInfo->payUrl)) {
            $redirect = $getPaymentInfo->payUrl;
          }
        }
        // ---
        switch ($redirect) {
          case'<front>':
            $response->addCommand(
              new RedirectCommand(
                Url::fromRoute('<front>')->toString()
              )
            );
            break;

          case'finish':
            $response->addCommand(
             new RedirectCommand(
              Url::fromRoute('basket.pages', [
                'page_type'     => 'finish',
              ])->toString()
             )
            );
            break;

          case'reload':
            $response->addCommand(
              new InvokeCommand('body', 'append', ['<script>location.reload();</script>'])
            );
            break;

          case'payment':
            $response->addCommand(
             new RedirectCommand(Url::fromRoute('basket.pages', [
               'page_type'         => 'payment',
             ], [
               'query'                => [
                 'payInfo'            => base64_encode(serialize([
                   'paySystem'            => $paySystem,
                   'payId'                => $payId,
                 ])),
               ],
             ])->toString())
            );
            break;

          default:
            $response->addCommand(
              new RedirectCommand($redirect)
            );
            break;
        }
        // Message.
        \Drupal::messenger()->deleteAll();
        if (!empty($message = \Drupal::service('Basket')->getSettings('order_form', 'config.submit_message'))) {
          \Drupal::messenger()->addMessage(\Drupal::service('Basket')->Translate()->trans(trim($message)), 'status');
        }
        
        /* Alter */
	      \Drupal::moduleHandler()->alter('basket_submit_ajax_response', $response, $form, $form_state);
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function insertSubmit($form, FormStateInterface $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    \Drupal::service('Basket')->getClass('Drupal\basket\Entity')->insertOrder($entity, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function emptyOrderValidate($form, FormStateInterface $form_state) {
    $count = \Drupal::service('Basket')->Cart()->getCount();
    if (empty($count)) {
      $form_state->setErrorByName('orderCount', \Drupal::service('Basket')->Translate()->t('You have added 0 items to cart!'));
    }
  }

}
