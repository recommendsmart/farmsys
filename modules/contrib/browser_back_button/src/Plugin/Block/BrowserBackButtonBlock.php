<?php

namespace Drupal\browser_back_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block with Browser Back Button.
 *
 * @Block(
 *   id = "browser_back_button_block",
 *   admin_label = @Translation("Browser Back Button Block"),
 *   category = @Translation("Browser Back Button Block"),
 * )
 */
class BrowserBackButtonBlock extends BlockBase {  
  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'body' => [
        'value' => $this->t('Back'),
      ],
      'reload_status' => 1,
      
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [];
    $data['body'] = $this->configuration['body']['value'];
    $data['reload_status'] = $this->configuration['reload_status'];
    return [      
      '#theme' => 'browser_back_button_history',
      '#data' => $data,
      '#attached' => [
        'library' => [
          'browser_back_button/browser_back_button.history',
        ],
        'drupalSettings' => [
          'browser_back_button' => [
            'data' => $data,
          ]
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Back Button Text or Image'),
      '#default_value' => $this->configuration['body']['value'],
      '#format' => 'full_html',
    ];  
    $form['reload_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to reload page?'),
      '#default_value' => $this->configuration['reload_status'],
      '#description' => $this->t('Whether page should be reloaded or not while clicking the back button.')
    ];   
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('body', $form_state->getValue('body'));    
    $this->configuration['reload_status'] = $form_state->getValue('reload_status');
  }  

}