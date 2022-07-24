<?php

namespace Drupal\basket\Plugins\Params;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Base class for product parameters form.
 */
abstract class BasketParamsBaseForm extends FormBase implements BasketParamsInterface {

  /**
   * Variable entity.
   *
   * @var object
   */
  protected $entity;

  /**
   * Variable view.
   *
   * @var array
   */
  protected $view = [];

  /**
   * Variable ajax.
   *
   * @var array
   */
  protected $ajax;

  /**
   * Variable basketItem.
   *
   * @var object
   */
  protected $basketItem;

  /**
   * Variable params.
   *
   * @var array
   */
  protected $params;

  /**
   * Variable display mode.
   *
   * @var array
   */
  protected $displayMode;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity, $basketItem = NULL) {
    $this->entity = $entity;
    $this->basketItem = $basketItem;
    if (!empty($this->entity->view)) {
      $this->view = [
        'id'            => $this->entity->view->id(),
        'display'       => $this->entity->view->current_display,
	      'args'          => implode('__', $this->entity->view->args),
        'dom_id'        => $this->entity->view->dom_id,
      ];
    }
    elseif (!empty($this->entity->view_id) && !empty($this->entity->view_current_display)) {
      $this->view = [
        'id'            => $this->entity->view_id,
        'display'       => $this->entity->view_current_display,
	      'args'          => $this->entity->view_args,
        'dom_id'        => $this->entity->view_dom_id,
      ];
    }
    if (!empty($this->entity->basketAddParams)) {
      $this->view['popup'] = 'popup';
    }
    if (!empty($this->basketItem)) {
      $this->view['basketItem'] = $this->basketItem->id;
      $this->params             = $this->basketItem->params;
    }
    $this->ajax = [
      'wrapper'       => $this->getFormId().'-wrap',
      'callback'      => [static :: class, 'ajaxCallback'],
      'progress'      => ['type' => 'fullscreen'],
      'disable-refocus' => TRUE
    ];
    if (empty($this->entity->basketAddParams)) {
      $this->ajax += [
        'url'           => new Url('basket.pages', ['page_type' => 'api-basket_ajax_params']),
        'options'       => [
          'query'         => \Drupal::request()->query->All() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayMode($mode){
    $this->displayMode = $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getBasketItem() {
    return $this->basketItem;
  }

  /**
   * {@inheritdoc}
   */
  public function getAjax() {
    return $this->ajax;
  }

  /**
   * Change default values.
   */
  public function setParams(array $params) {
    $this->params = $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return Html::cleanCssIdentifier('basket_button_params_' . $this->entity->id() . '_' . implode('_', $this->view));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // ---
    $this->setDefaultValues($form_state);
    // ---
    $form += [
      '#prefix'       => '<div id="' . $this->ajax['wrapper'] . '">',
      '#suffix'       => '</div>',
    ];
    $form['nid'] = [
      '#type'         => 'hidden',
      '#value'        => $this->entity->id(),
    ];
    $form['display_mode'] = [
      '#type'         => 'hidden',
      '#value'        => $this->displayMode,
    ];
    if (!empty($this->view['id']) && !empty($this->view['display'])) {
      $form['current_view'] = [
        '#type'         => 'hidden',
        '#value'        => implode('=>', [
					$this->view['id'],
	        $this->view['display'],
	        $this->view['dom_id'],
	        $this->view['args']
        ])
      ];
    }
    if (!empty($this->entity->basketAddParams)) {
      $form['isAddParamsPopup'] = [
        '#type'         => 'hidden',
        '#value'        => 1,
      ];
    }
    if (!empty($this->basketItem)) {
      $form['basketItemId'] = [
        '#type'         => 'hidden',
        '#value'        => $this->basketItem->id,
      ];
    }
    if (!empty($this->basketItem->orderId)) {
      $form['orderId'] = [
        '#type'         => 'hidden',
        '#value'        => $this->basketItem->orderId,
      ];
    }
    $form['#attributes']['data-params_key'] = $this->entity->id() . '_' . implode('_', $this->view);
    $form['#attributes']['data-params_nid'] = $this->entity->id();
    $form['#attributes']['class'][] = 'basket_button_params_form params_' . $this->entity->bundle();
    $form['params'] = [
      '#tree'         => TRUE,
    ];
    /*ParamsForm*/
    $this->getParamsForm($form['params'], $form_state, $this->entity, $this->ajax);
    /*Access*/
    $form['#access'] = $this->getAccess($this->entity);
    // ---
    $set_params = $form_state->getValue('params');
    $form['#attributes']['data-set_params'] = !empty($set_params) ? json_encode($set_params) : json_encode([]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccess($entity) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValues($form_state) {
    $values = $form_state->getValues();
    if (empty($values) && !empty($this->params)) {
      $input = &$form_state->getUserInput();
      $input['params'] = $this->params;
      $form_state->setValue('params', $input['params']);
    }
  }

}
