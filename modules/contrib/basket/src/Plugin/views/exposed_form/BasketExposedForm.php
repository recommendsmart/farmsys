<?php

namespace Drupal\basket\Plugin\views\exposed_form;

use Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposed form plugin that provides a basket exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "basket",
 *   title = @Translation("Basket Exposed Form"),
 *   help = @Translation("Basket exposed form")
 * )
 */
class BasketExposedForm extends ExposedFormPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set request.
   *
   * @var object
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->request = \Drupal::request();
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);
    if ($this->view->id() == 'basket' || $this->view->id() == 'basket_users') {
      if (!empty($form['actions']['submit']['#value'])) {
        $form['actions']['submit']['#value'] = $this->basket->Translate()->trans(trim($form['actions']['submit']['#value']));
      }
      if (!empty($form['#info'])) {
        foreach ($form['#info'] as &$field) {
          if (!empty($field['label'])) {
            $field['label'] = $this->basket->Translate()->trans(trim($field['label']));
          }
        }
      }
    }
    $form['page'] = [
      '#type'         => 'hidden',
      '#default_value' => 0,
      '#attached'     => [
        'drupalSettings' => [
          'pageFilter'    => $this->request->query->get('page'),
        ],
      ],
    ];
    $form['sort'] = [
      '#type'         => 'hidden',
      '#default_value' => $this->request->query->get('sort'),
    ];
    $form['order'] = [
      '#type'         => 'hidden',
      '#default_value' => $this->request->query->get('order'),
    ];
  }

}
