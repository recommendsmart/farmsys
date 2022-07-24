<?php

namespace Drupal\basket\Admin;

/**
 * {@inheritdoc}
 */
class BasketDeleteConfirm {

  /**
   * {@inheritdoc}
   */
  public static function confirmContent($params = []) {
    $trans = \Drupal::service('Basket')->Translate();
    return [
      '#prefix'       => '<div class="form-actions">',
      '#suffix'       => '</div>',
      'submit'        => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" class="button" onclick="{{onclick}}" data-post="{{post}}">' . $trans->t('Delete') . '</a><a href="javascript:void(0);" class="button button--danger" onclick="{{onclick_close}}"">' . $trans->t('Cancel') . '</a>',
        '#context'      => [
          'onclick'       => !empty($params['onclick']) ? $params['onclick'] : '',
          'post'          => !empty($params['post']) ? $params['post'] : '',
          'onclick_close' => \Drupal::service('BasketPopup')->getCloseOnclick(),
        ],
      ],
    ];
  }

}
