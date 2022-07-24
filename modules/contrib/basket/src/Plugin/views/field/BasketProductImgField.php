<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\basket\Query\BasketGetNodeImgQuery;

/**
 * Product picture field.
 *
 * @ViewsField("basket_product_img_field")
 */
class BasketProductImgField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['image_style'] = ['default' => 'thumbnail'];
    $options['image_link']  = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['image_style'] = [
      '#type'             => 'select',
      '#title'            => t('Image style'),
      '#options'          => image_style_options(FALSE),
      '#default_value'    => $this->options['image_style'],
    ];
    $form['image_link'] = [
      '#type'             => 'checkbox',
      '#title'            => t('Link to the @entity_label', ['@entity_label' => t('Content')]),
      '#default_value'    => $this->options['image_link'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // We don't need to modify query for this particular example.
    BasketGetNodeImgQuery::viewsJoin($this);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (empty($values->basket_node_first_img)) {
      $values->basket_node_first_img = BasketGetNodeImgQuery::getDefFid($values->_entity);
    }
    if (!empty($values->basket_node_first_img)) {
      $file = \Drupal::service('entity_type.manager')->getStorage('file')->load($values->basket_node_first_img);
    }
    if (empty($file)) {
      return [];
    }
    if (!empty($this->options['image_link'])) {
      return [
        '#type'         => 'link',
        '#title'        => [
          '#theme'        => 'image_style',
          '#style_name'   => $this->options['image_style'],
          '#uri'          => $file->getFileUri(),
        ],
        '#url'          => new Url('entity.node.canonical', [
          'node'          => $values->_entity->id(),
        ]),
      ];
    }
    else {
      return [
        '#theme'        => 'image_style',
        '#style_name'   => $this->options['image_style'],
        '#uri'          => $file->getFileUri(),
      ];
    }
  }

}
