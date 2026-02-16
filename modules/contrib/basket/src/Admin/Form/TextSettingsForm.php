<?php

namespace Drupal\basket\Admin\Form;

use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\Condition;

/**
 * {@inheritdoc}
 */
class TextSettingsForm extends FormBase {

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
  public function getFormId() {
    return 'basket_text_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ajax_popup = FALSE) {
    if (!$this->trans->isEnabled()) {
      $form['message'] = [
        '#markup'       => $this->trans->t('The site is not multilingual!'),
        '#prefix'       => '<div class="empty_text">',
        '#suffix'       => '</div>',
      ];
      return $form;
    }
    $form['filter'] = [
      '#type'         => 'details',
      '#title'        => t('Filter translatable strings'),
      '#open'         => TRUE,
      'string'        => [
        '#type'         => 'search',
        '#title'        => t('String contains'),
        '#description'  => t('Leave blank to show all strings. The search is case sensitive.'),
        '#default_value' => @$_GET['string'],
      ],
      'actions'        => [
        '#type'         => 'actions',
        '#attributes'   => [
          'class'         => ['container-inline'],
        ],
        'submit'        => [
          '#type'         => 'submit',
          '#value'        => t('Filter'),
          '#submit'       => ['::formFilter'],
        ],
        'reset'            => [
          '#type'         => 'submit',
          '#value'        => t('Reset'),
          '#attributes'   => [
            'class'         => ['button--danger'],
          ],
          '#submit'       => ['::formReset'],
        ],
      ],
    ];
    $languages = \Drupal::languageManager()->getLanguages();
    $form['strings'] = [
      '#type'         => 'table',
      '#header'         => [
        t('Source string'),
      ],
      '#empty'        => $this->trans->t('The list is empty.'),
      '#attributes'    => [
        'class'            => ['translate_settings_table'],
      ],
    ];
    $translate_english = \Drupal::configFactory()->getEditable('locale.settings')->get('translate_english');
    foreach ($languages as $langcode => $language) {
      if ($langcode == 'en') {
        continue;
      }
      $form['strings']['#header'][$langcode] = $language->getName();
    }
    $strings = $this->getStrings($languages);
    if (!empty($strings)) {
      foreach ($strings as $string) {
        $form['strings'][$string->lid]['name'] = [
          '#type'         => 'item',
          '#plain_text'   => $string->source,
          '#wrapper_attributes'  => [
            'class'             => ['td_ico'],
            'title'             => implode(': ', [
              t('In Context'),
              $string->context,
            ]),
          ],
        ];
        $form['strings'][$string->lid]['name']['delete'] = [
          '#type'             => 'inline_template',
          '#template'         => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}">{{ ico|raw }}</a>',
          '#context'          => [
            'onclick'           => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-text_delete_string'])->toString() . '\')',
            'post'              => json_encode([
              'delete_lid'        => $string->lid,
            ]),
            'ico'               => $this->basket->getIco('delete.svg', 'base'),
          ],
        ];
        if ($translate_english) {
          $form['strings'][$string->lid]['name']['edit'] = [
            '#type'             => 'inline_template',
            '#template'         => '<a href="javascript:void(0);" onclick="jQuery(\'.replace_en_' . $string->lid . '\').toggle();jQuery(this).remove();">{{ico|raw}}</a>',
            '#context'          => [
              'ico'               => $this->basket->getIco('pencil.svg', 'base'),
            ],

          ];
          $form['strings'][$string->lid]['name']['replace'] = [
            '#type'             => 'textarea',
            '#rows'             => 1,
            '#default_value'    => !empty($string->{$langcode}) ? trim($string->{$langcode}) : '',
            '#parents'          => ['strings', $string->lid, 'en'],
            '#prefix'           => Markup::create('<div class="replace_en_' . $string->lid . '" style="display:none">'),
            '#suffix'           => '</div>',
          ];
        }
        foreach ($languages as $langcode => $language) {
          if ($langcode == 'en') {
            continue;
          }
          $form['strings'][$string->lid][$langcode] = [
            '#type'             => 'textarea',
            '#rows'             => 1,
            '#default_value'    => !empty($string->{$langcode}) ? trim($string->{$langcode}) : '',
            '#field_suffix'     => $this->getTranslateLink($string->lid, $langcode),
            '#wrapper_attributes' => [
              'class'             => ['td_ico', 'translate_textarea'],
            ],
          ];
        }
      }
      $form['pager'] = [
        '#type'         => 'pager',
      ];
      $form['actions'] = [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#id'           => 'save_translate',
          '#value'        => t('Save translations'),
          '#submit'       => ['::saveTranslate'],
        ],
      ];
      if ($ajax_popup) {
        $form['filter']['#access'] = FALSE;
        $form['#prefix'] = '<div id="basket_text_settings_ajax_wrap">';
        $form['#suffix'] = '</div>';
        $form['actions']['submit']['#ajax'] = [
          'wrapper'       => 'basket_text_settings_ajax_wrap',
          'callback'      => __CLASS__ . '::ajaxTextSubmit',
        ];
        $form['strings'] += [
          '#prefix'       => '<div class="basket_table_wrap">',
          '#suffix'       => '</div>',
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){}

  /**
   * {@inheritdoc}
   */
  public static function ajaxTextSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  private function getStrings($languages) {
    $context = ['basket'];
    /*Alter*/
    \Drupal::moduleHandler()->alter('basket_translate_context', $context);
    /*END alter*/

    $strings = [];
    $query = \Drupal::database()->select('locales_source', 's');
    $query->fields('s');
    $query->condition('s.context', $context, 'in');
    // ---
    foreach ($languages as $langcode => $language) {
      $query->leftJoin('locales_target', $langcode, $langcode . '.lid = s.lid AND ' . $langcode . '.language = \'' . $langcode . '\'');
      $query->addExpression($langcode . '.translation', $langcode);
    }
    if (!empty($_GET['string'])) {
      $db_or = new Condition('OR');
      $db_or->condition('s.source', '%' . trim($_GET['string']) . '%', 'LIKE');
      foreach ($languages as $langcode => $language) {
        $db_or->condition($langcode . '.translation', '%' . trim($_GET['string']) . '%', 'LIKE');
      }
      $query->condition($db_or);
    }
    // ---
    $query->orderBy('s.lid', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
    return $pager->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function formFilter(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('basket.admin.pages', [
      'page_type'        => 'settings-text',
    ], [
      'query'     => [
        'string'        => $form_state->getValue('string'),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function formReset(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('basket.admin.pages', [
      'page_type'        => 'settings-text',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslate(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['strings'])) {
      $updated = [];
      $langcodes = [];
      $lids = array_keys($form_state->getValue('strings'));
      $languages = \Drupal::languageManager()->getLanguages();
      $existing_translation_objects = [];
      foreach ($languages as $langcode => $language) {
        foreach (\Drupal::service('locale.storage')->getTranslations([
          'lid'       => $lids,
          'language'  => $langcode,
          'translated' => TRUE,
        ]) as $existing_translation_object) {
          $existing_translation_objects[$langcode][$existing_translation_object->lid] = $existing_translation_object;
        }
      }
      foreach ($values['strings'] as $lid => $string) {
        foreach ($languages as $langcode => $language) {
          if (!empty($string[$langcode])) {
            $target = isset($existing_translation_objects[$langcode][$lid]) ? $existing_translation_objects[$langcode][$lid] : \Drupal::service('locale.storage')->createTranslation([
              'lid'           => $lid,
              'language'      => $langcode,
            ]);
            $target->setString(trim($string[$langcode]))->setCustomized()->save();
            $updated[] = $target->getId();
            $langcodes[$langcode] = $langcode;
          }
          else {
            if (!empty($existing_translation_objects[$langcode][$lid])) {
              $existing_translation_objects[$langcode][$lid]->delete();
              $updated[] = $lid;
              $langcodes[$langcode] = $langcode;
            }
          }
        }
      }
      \Drupal::messenger()->addMessage(t('The strings have been saved.'));
      if ($updated) {
        _locale_refresh_translations($langcodes, $updated);
        _locale_refresh_configuration($langcodes, $updated);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getTranslateLink($lid, $langcode) {
    return [
      '#type'            => 'inline_template',
      '#template'        => '<a class="translate_link" href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}">{{ico|raw}}</a>',
      '#context'        => [
        'onclick'     => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-text_get_translate'])->toString() . '\')',
        'post'     => json_encode([
          'lid'           => $lid,
          'source'        => 'en',
          'target'        => $langcode,
          'field_name'    => 'strings[' . $lid . '][' . $langcode . ']',
        ]),
        'ico'       => $this->basket->getIco('google.svg'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function apiResponseAlter(&$response) {
    if (!empty($_POST['lid'])
          && !empty($_POST['source'])
          && !empty($_POST['target'])
          && !empty($_POST['field_name'])) {
      $string = \Drupal::service('locale.storage')->getTranslations(['lid' => $_POST['lid']]);
      if (!empty($string[0]->source)) {
        $translate = GoogleTranslate::translate($_POST['source'], $_POST['target'], $string[0]->source);
        if (!empty($translate)) {
          $response->addCommand(new InvokeCommand('textarea[name="' . $_POST['field_name'] . '"]', 'val', [$translate]));
        }
      }
    }
    if (!empty($_REQUEST['string'])) {
      \Drupal::service('BasketPopup')->openModal(
        $response,
        t('Text settings'),
        \Drupal::formBuilder()->getForm(
            '\Drupal\basket\Admin\Form\TextSettingsForm',
            TRUE
        ), [
          'width' => '90%',
          'class' => ['basket_add_popup'],
        ]
      );
    }
    if (!empty($_POST['delete_lid'])) {
      if (!empty($_POST['confirm'])) {
        // Delete.
        \Drupal::database()->delete('locales_target')->condition('lid', $_POST['delete_lid'])->execute();
        \Drupal::database()->delete('locales_source')->condition('lid', $_POST['delete_lid'])->execute();
        // Reset cashe.
        $languages = \Drupal::languageManager()->getLanguages();
        _locale_refresh_translations(array_keys($languages), [$_POST['delete_lid']]);
        _locale_refresh_configuration(array_keys($languages), [$_POST['delete_lid']]);
        // Reload.
        $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      }
      else {
        $sourse_text = \Drupal::database()->select('locales_source', 's')
          ->fields('s', ['source'])
          ->condition('s.lid', $_POST['delete_lid'])
          ->execute()->fetchField();
        if (isset($sourse_text)) {
          \Drupal::service('BasketPopup')->openModal(
            $response,
            \Drupal::service('Basket')->Translate()->t('Delete') . ' "' . $sourse_text . '"',
            BasketDeleteConfirm::confirmContent([
              'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-text_delete_string'])->toString() . '\')',
              'post'          => json_encode([
                'delete_lid'    => $_POST['delete_lid'],
                'confirm'       => 1,
              ]),
            ]),
            [
              'width' => 400,
              'class' => ['basket_add_popup'],
            ]
          );
        }
      }
    }
  }

}
/**
 * {@inheritdoc}
 */
class GoogleTranslate {

  /**
   * {@inheritdoc}
   */
  public static function translate($source, $target, $text) {
    $response = self::requestTranslation($source, $target, $text);
    $translation = self::getSentencesFromJson($response);
    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  protected static function requestTranslation($source, $target, $text) {
    $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
    $fields = [
      'sl' => urlencode($source),
      'tl' => urlencode($target),
      'q' => urlencode($text),
    ];
    if (strlen($fields['q']) >= 5000) {
      throw new \Exception("Maximum number of characters exceeded: 5000");
    }
    $fields_string = "";
    foreach ($fields as $key => $value) {
      $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getSentencesFromJson($json) {
    $sentencesArray = json_decode($json, TRUE);
    $sentences = "";
    foreach ($sentencesArray["sentences"] as $s) {
      $sentences .= isset($s["trans"]) ? $s["trans"] : '';
    }
    return $sentences;
  }

}
