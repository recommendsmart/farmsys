<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * {@inheritdoc}
 */
class Templates {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set getTemplateYamls.
   *
   * @var array
   */
  protected $getTemplateYamls;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function page($activeTemplate = NULL) {
    return [
      'orders'        => [
        '#prefix'        => '<div class="basket_table_wrap">',
        '#suffix'        => '</div>',
        'title'            => [
          '#prefix'        => '<div class="b_title">',
          '#suffix'        => '</div>',
          '#markup'        => $this->basket->Translate()->t('Templates'),
        ],
        'content'        => $this->getTemplates($activeTemplate) + [
          '#prefix'        => '<div class="b_content">',
          '#suffix'        => '</div>',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview($activeTemplate = NULL) {
    print $this->basket->MailCenter()->getHtml([
      'template'      => $activeTemplate,
    ]);
    exit;
  }

  /**
   * {@inheritdoc}
   */
  private function getTemplates($activeTemplate) {
    $templates = [];
    $ymldata = $this->getTemplateYamls();
    if (!empty($ymldata)) {
      foreach ($ymldata as $groupKey => $groupInfo) {
        $templates[$groupKey] = [
          '#type'         => 'details',
          '#title'        => $this->basket->Translate()->trans($groupInfo['title']),
        ];
        if (!empty($groupInfo['templates'])) {
          foreach ($groupInfo['templates'] as $templateKey => $templateInfo) {
            $class = ['template_item'];
            if ($activeTemplate == $templateKey) {
              $class[] = 'is-active';
              $templates[$groupKey]['#open'] = TRUE;
            }
            $title_context = !empty($templateInfo['title_context']) ? $templateInfo['title_context'] : 'basket';
            $templates[$groupKey][$templateKey] = [
              '#prefix'       => '<div class="' . implode(' ', $class) . '">',
              '#suffix'       => '</div>',
              [
                '#type'         => 'link',
                '#title'        => $this->basket->Translate($title_context)->trans(trim($templateInfo['title'])),
                '#url'          => new Url('basket.admin.pages', [
                  'page_type'     => 'settings-templates-' . $templateKey,
                ]),
                '#prefix'       => '<div class="link_wrap">',
                '#suffix'       => '</div>',
              ],
            ];
            if ($activeTemplate == $templateKey) {
              $templateInfo['id'] = $templateKey;
              $templates[$groupKey][$templateKey][] = \Drupal::formBuilder()->getForm(new TemplateSettingsForm($templateInfo));
            }
          }
        }
      }
    }
    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateYamls() {
    if (!isset($this->getTemplateYamls)) {
      $ymldata = [];
      $templates_file = drupal_get_path('module', 'basket') . '/config/basket_install/Templates.yml';
      if (file_exists($templates_file)) {
        $ymldata = Yaml::decode(file_get_contents($templates_file));
        // subModules.
        $subModules = [];
        \Drupal::moduleHandler()->alter('basket_translate_context', $subModules);
        if (!empty($subModules)) {
          foreach ($subModules as $subModule) {
            $templatesSubFile = drupal_get_path('module', $subModule) . '/config/basket_install/Templates.yml';
            if (file_exists($templatesSubFile)) {
              $ymlSubData = Yaml::decode(file_get_contents($templatesSubFile));
              if (!empty($ymlSubData)) {
                $ymldata = $this->basket->arrayMergeRecursive($ymldata, $ymlSubData);
              }
            }
          }
        }
      }
      $this->getTemplateYamls = $ymldata;
    }
    return $this->getTemplateYamls;
  }

}

/**
 * {@inheritdoc}
 */
class TemplateSettingsForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function __construct($templateInfo) {
    $this->basket = \Drupal::service('Basket');
    $this->templateInfo = $templateInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_template_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form['status_messages'] = [
      '#type'            => 'status_messages',
    ];
    $form['#prefix'] = '<div id="basket_template_settings_form_ajax_wrap">';
    $form['#suffix'] = '</div>';
    $form['config'] = [
      '#tree'            => TRUE,
    ];
    $langPrefix = '';
    if (!empty($this->templateInfo['language'])) {
      $languages = \Drupal::languageManager()->getLanguages();
      if (count($languages) > 1) {
        $options = [];
        foreach ($languages as $language) {
          $options[$language->getId()] = $language->getName();
        }
        $form['config']['language'] = [
          '#type'            => 'radios',
          '#options'        => $options,
          '#default_value' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
          '#ajax'            => [
            'wrapper'        => 'basket_template_settings_form_ajax_wrap',
            'callback'        => '::ajaxCallback',
          ],
          '#prefix'        => '<div class="language_switch">',
          '#suffix'        => '</div>',
        ];
      }
      $langPrefix = '_' . \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (!empty($values['config']['language'])) {
        $langPrefix = '_' . $values['config']['language'];
      }
    }
    // ---
    $config = \Drupal::service('Basket')->getSettings('templates', $this->templateInfo['id'] . $langPrefix);
    if (!empty($this->templateInfo['subject'])) {
      $form['config']['subject' . $langPrefix] = [
        '#type'         => 'textfield',
        '#title'        => $this->basket->Translate()->t('Subject'),
        '#default_value' => !empty($config['config']['subject']) ? $config['config']['subject'] : NULL,
      ];
    }
    // ---
    $form['config']['template' . $langPrefix] = [
      '#type'            => 'textarea',
      '#title'        => $this->basket->Translate()->t('Template'),
      '#rows'            => 15,
      '#default_value' => !empty($config['config']['template']) ? $config['config']['template'] : NULL,
    ];
    if (!empty($this->templateInfo['text_format'])) {
      $form['config']['template' . $langPrefix]['#type'] = 'text_format';
      $form['config']['template' . $langPrefix]['#default_value'] = !empty($config['config']['template']['value']) ? $config['config']['template']['value'] : NULL;
      $form['config']['template' . $langPrefix]['#format'] = !empty($config['config']['template']['format']) ? $config['config']['template']['format'] : NULL;
    }
    if (!empty($this->templateInfo['inline_twig'])) {
      $form['config']['template' . $langPrefix]['#attributes']['class'][] = 'inline_twig';
      $form['#attached']['library'][] = 'basket/codemirror';
    }
    if (!empty($this->templateInfo['token'])) {
      $this->templateToken($form);
    }
    $this->templateTokenTwig($form);
    if (!empty($this->templateInfo['twig_template'])) {
      $this->templateTwigTemplate($form);
    }
    $form['actions'] = [
      '#type'            => 'actions',
      'submit'        => [
        '#type'            => 'submit',
        '#value'        => t('Save configuration'),
        '#name'         => 'save',
        '#ajax'            => [
          'wrapper'        => 'basket_template_settings_form_ajax_wrap',
          'callback'        => '::ajaxCallback',
        ],
      ],
    ];
    if (!empty($this->templateInfo['preview'])) {
      $form['actions']['preview'] = [
        '#type'         => 'button',
        '#value'        => $this->basket->Translate()->t('Preview'),
        '#attributes'   => [
          'class'         => ['preview'],
          'title'         => $this->basket->Translate()->t('Check the pop-up blocker in the browser'),
        ],
        '#name'         => 'preview',
        '#ajax'         => [
          'wrapper'       => 'basket_template_settings_form_ajax_wrap',
          'callback'      => '::ajaxCallback',
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'save') {
      $langPrefix = '';
      $config = $form_state->getValue('config');
      if (!empty($this->templateInfo['language'])) {
        $langPrefix = '_' . \Drupal::languageManager()->getCurrentLanguage()->getId();
        if (!empty($config['language'])) {
          $langPrefix = '_' . $config['language'];
        }
      }
      $setSettings = [
        'template'      => !empty($config['template' . $langPrefix]) ? $config['template' . $langPrefix] : [],
      ];
      if (isset($config['subject' . $langPrefix])) {
        $setSettings['subject'] = !empty($config['subject' . $langPrefix]) ? $config['subject' . $langPrefix] : '';
      }
      $this->basket->setSettings('templates', $this->templateInfo['id'] . $langPrefix, ['config' => $setSettings]);
      \Drupal::messenger()->addMessage($this->basket->Translate()->t('Settings saved.'), 'status');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'preview') {
      $response = new AjaxResponse();
      $config = $form_state->getValue('config');
      $languages = \Drupal::languageManager()->getLanguages();
      if (!empty($config['language']) && !empty($languages[$config['language']])) {
        $language = $languages[$config['language']];
      }
      else {
        $language = $languages[\Drupal::languageManager()->getCurrentLanguage()->getId()];
      }
      $response->addCommand(new InvokeCommand(NULL, 'BasketOpenNewWindow', [
        Url::fromRoute('basket.admin.pages', ['page_type' => 'settings-templates_preview-' . $this->templateInfo['id']], ['language' => $language])->toString(),
        600,
        '100%',
      ]));
      return $response;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  private function templateToken(&$form) {
    $form['token'] = [
      '#theme'        => 'token_tree_link',
      '#token_types'  => ['user', 'node'],
      '#text'         => $this->basket->Translate()->t('[available tokens]'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function templateTokenTwig(&$form) {
    $tokents = [];
    if (!empty($this->templateInfo['token_twig_class'])) {
      list($token_twig_class, $token_twig_class_function) = explode('::', $this->templateInfo['token_twig_class']);
      $class = new $token_twig_class();
      $this->templateInfo['token_twig'] += $class->{$token_twig_class_function}();
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basketTemplateTokens', $this->templateInfo['token_twig'], $this->templateInfo['id']);
    // ---
    if (!empty($this->templateInfo['token_twig'])) {
      $title_context = !empty($this->templateInfo['title_context']) ? $this->templateInfo['title_context'] : 'basket';
      foreach ($this->templateInfo['token_twig'] as $keyToken => $label) {
        $tokents[] = '{{' . $keyToken . '}} - <b>' . $this->basket->Translate($title_context)->trans(trim($label)) . '</b>';
      }
    }
    if (!empty($tokents)) {
      $form['token_twig'] = [
        '#type'         => 'details',
        '#title'        => $this->basket->Translate()->t('Twig tokens'),
        '#description'  => implode('<br/>', $tokents),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  private function templateTwigTemplate(&$form) {
    $form['twig_template'] = [
      '#type'         => 'item',
      '#title'        => 'Twig template:',
      '#markup'       => $this->templateInfo['twig_template'],
    ];
  }

}
