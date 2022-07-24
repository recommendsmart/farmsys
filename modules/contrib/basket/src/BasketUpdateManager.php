<?php

namespace Drupal\basket;

use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Element;

/**
 * {@inheritdoc}
 */
class BasketUpdateManager {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->moduleHandler = \Drupal::moduleHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(&$form, $form_state) {
    if(!empty($form['manual_updates']['#rows'])) {
      $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
      $available = update_get_available(TRUE);
      $projectData = update_calculate_project_data($available);
      $rows = [];
      foreach ($form['manual_updates']['#rows'] as $moduleKey => $row) {
        if(empty($projectData[$moduleKey]))    continue;
        if(!empty($projectData[$moduleKey]['recommended']) && !empty($projectData[$moduleKey]['releases'][$projectData[$moduleKey]['recommended']]['alternativecommerce'])) {
          $row['data']['recommended_version']['data']['#template'] = '{{ release_version }}';
          $row['data']['title'] = [
            'data'    => [
              '#type'       => 'inline_template',
              '#template'   => '<strong>{{ name }}</strong>',
              '#context'    => $projectData[$moduleKey]
            ]
          ];
          $rows[$moduleKey] = $row;
          unset($form['manual_updates']['#rows'][$moduleKey]);
        }
      }
      if(!empty($rows)) {
        $form['basket_updates'] = $form['manual_updates'];
        $form['basket_updates']['#rows'] = $rows;

        $prefix = '<h2>' . $this->basket->Translate()->t('Store updates are available') . '</h2>';
        $prefix .= '<p>' . $this->basket->Translate()->t('To update these modules, refer to the @mail@ mail', ['@mail@' => Markup::create( '<a href="mailto:' . $this->basket->getMail() . '">' . $this->basket->getMail() . '</a>' ) ]) . '</p>';
        $form['basket_updates']['#prefix'] = $prefix;
      }
      if(empty($form['manual_updates']['#rows'])) {
        $form['manual_updates']['#access'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateReport(&$vars) {
    if(!empty($vars['project_types'])) {
      $vars['project_types']['basket'] = [
        'label'     => t('Shop', [], ['context' => 'basket']),
        'table'     => [
          '#type'     => 'table',
          '#is_basket' => TRUE
        ]
      ];
      foreach ($vars['project_types'] as &$type) {
        if(!empty($type['table']['#is_basket']))  continue;
        if(empty($type['table']))                 continue;
        foreach (Element::children($type['table']) as $moduleKey) {
          if(empty($type['table'][$moduleKey]['status']['#project'])) continue;
          $project = $type['table'][$moduleKey]['status']['#project'];
          if(!empty($project['info']['package']) && $project['info']['package'] == 'Online store') {
            $vars['project_types']['basket']['table'][$moduleKey] = $type['table'][$moduleKey];
            unset($type['table'][$moduleKey]);
          }
        }
      }
    }
  }

}
