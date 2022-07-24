<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketMailCenter {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set templateInfo.
   *
   * @var array
   */
  protected $templateInfo;

  /**
   * Set getLastOrder.
   *
   * @var object
   */
  protected static $getLastOrder;

  /**
   * Set mailManager.
   *
   * @var object
   */
  protected static $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    self::$mailManager = \Drupal::service('plugin.manager.mail');
  }

  /**
   * {@inheritdoc}
   */
  public function send($email, $params) {
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $params = [
      'subject'        => $this->getSubject($params),
      'message'        => $this->getHtml($params),
    ];
    self::$mailManager->mail('basket', 'send', trim($email), $langcode, $params, NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject($params) {
    if (empty($params['template'])) {
      return '';
    }
    $templateInfo = $this->getTemplateInfo($params['template']);
    if (!empty($templateInfo['subject'])) {
      $langPrefix = '';
      if (!empty($templateInfo['language'])) {
        $langPrefix = '_' . \Drupal::languageManager()->getCurrentLanguage()->getId();
      }
      $settings = $this->basket->getSettings('templates', $params['template'] . $langPrefix);
      if (!empty($settings['config']['subject'])) {
        $getLastOrder = $this->getLastOrder($params);
        $subject = [
          '#type'            => 'inline_template',
          '#template'        => $settings['config']['subject'],
          '#context'        => $this->getContext($params['template'], [
            'order'            => $getLastOrder,
          ]),
        ];
        return \Drupal::token()->replace(
        \Drupal::service('renderer')->renderPlain($subject), [
          'user'        => isset($params['uid']) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($params['uid']) : \Drupal::service('entity_type.manager')->getStorage('user')->load(\Drupal::currentUser()->id()),
          'node'        => !empty($params['nid']) ? \Drupal::service('entity_type.manager')->getStorage('node')->load($params['nid']) : NULL,
        ], [
          'clear'     => TRUE,
        ]
        );
      }
    }
    return 'Subject';
  }

  /**
   * {@inheritdoc}
   */
  public function getHtml($params) {
    if (empty($params['template'])) {
      return '';
    }
    // ---
    $getLastOrder = $this->getLastOrder($params);
    if (empty($params['nid']) && !empty($getLastOrder->nid)) {
      $params['nid'] = $getLastOrder->nid;
    }
    if (empty($params['uid']) && !empty($params['nid'])) {
      $order = \Drupal::service('entity_type.manager')->getStorage('node')->load($params['nid']);
      if (!empty($order) && !empty($order->get('uid')->target_id)) {
        $params['uid'] = $order->get('uid')->target_id;
      }
    }
    // Body.
    $settings_html = $this->basket->getSettings('templates', 'notification_html');
    $html = [
      '#type'            => 'inline_template',
      '#template'        => $this->getTemplate($settings_html),
      '#context'        => $this->getContext('notification_html', [
        'order'            => $getLastOrder,
      ]),
    ];
    // Content.
    $templateInfo = $this->getTemplateInfo($params['template']);
    $langPrefix = '';
    if (!empty($templateInfo['language'])) {
      $langPrefix = '_' . \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $settings = $this->basket->getSettings('templates', $params['template'] . $langPrefix);
    $html['#context']['content'] = [
      '#theme'        => $params['template'],
      '#info'            => [
        'body'            => [
          '#type'            => 'inline_template',
          '#template'        => $this->getTemplate($settings),
          '#context'        => $this->getContext($params['template'], [
            'order'            => $getLastOrder,
          ]),
          '#params'        => $params,
        ],
      ],
    ];
    $html['#context']['content'] = \Drupal::service('renderer')->renderPlain($html['#context']['content']);
    // ---
    $html = str_replace([
      'class="text-align-center"',
      'src="/',
    ], [
      'style="text-align:center;"',
      'src="' . $GLOBALS['base_url'] . '/',
    ], \Drupal::service('renderer')->renderPlain($html));
    return \Drupal::token()->replace(
    $html, [
      'user'        => isset($params['uid']) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($params['uid']) : \Drupal::service('entity_type.manager')->getStorage('user')->load(\Drupal::currentUser()->id()),
      'node'        => !empty($params['nid']) ? \Drupal::service('entity_type.manager')->getStorage('node')->load($params['nid']) : NULL,
    ], [
      'clear'     => TRUE,
    ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate($settings) {
    $template = '';
    if (!empty($settings['config']['template'])) {
      $template = $settings['config']['template'];
      if (is_array($template)) {
        $template = [
          '#type'         => 'processed_text',
          '#text'         => $template['value'],
          '#format'         => $template['format'],
        ];
        $template = \Drupal::service('renderer')->renderPlain($template);
      }
    }
    return $template;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateInfo($templateName) {
    if (empty($this->templateInfo[$templateName])) {
      $this->templateInfo[$templateName] = NULL;
      $ymldata = $this->basket->getClass('Drupal\basket\Admin\Page\Templates')->getTemplateYamls();
      if (!empty($ymldata)) {
        foreach ($ymldata as $groupInfo) {
          if (!empty($groupInfo['templates'])) {
            foreach ($groupInfo['templates'] as $templateKey => $templateInfo) {
              if ($templateKey == $templateName) {
                $this->templateInfo[$templateName] = $templateInfo;
              }
            }
          }
        }
      }
    }
    return $this->templateInfo[$templateName];
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($templateName, $params) {
    $templateInfo = $this->getTemplateInfo($templateName);
    $context = [];
    if (empty($templateInfo['token_twig'])) {
      $templateInfo['token_twig'] = [];
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basketTemplateTokens', $templateInfo['token_twig'], $templateName);
    // ---
    if (!empty($templateInfo['token_twig'])) {
      foreach ($templateInfo['token_twig'] as $keyTwig => $nameTwig) {
        $context[$keyTwig] = $this->basket->Token()->getToken($keyTwig, $params);
      }
    }
    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastOrder($params) {
    if (!isset(self::$getLastOrder)) {
      $query = \Drupal::database()->select('basket_orders', 'b');
      $query->fields('b');
      if (!empty($params['nid'])) {
        $query->condition('b.nid', $params['nid']);
      }
      $query->orderBy('b.id', 'DESC');
      $query->range(0, 1);
      self::$getLastOrder = $query->execute()->fetchObject();
      if (!empty(self::$getLastOrder->currency)) {
        self::$getLastOrder->currency = $this->basket->Currency()->load(self::$getLastOrder->currency);
      }
    }
    return self::$getLastOrder;
  }

}
