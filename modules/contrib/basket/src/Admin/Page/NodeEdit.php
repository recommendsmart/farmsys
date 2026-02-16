<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

/**
 * {@inheritdoc}
 */
class NodeEdit {

  /**
   * Set entity.
   *
   * @var object
   */
  protected $entity;

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct($nid) {
    $this->entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function editPage() {
    if(empty($this->entity)) {
      return $this->basket->getError(404);
    }
    else if (!\Drupal::currentUser()->hasPermission('edit any ' . $this->entity->bundle() . ' content')) {
      return $this->basket->getError(403);
    }
    $operations = \Drupal::entityTypeManager()->getListBuilder($this->entity->getEntityTypeId())->getOperations($this->entity);
    $links = [
      'view'          => [
        'text'          => $this->basket->Translate()->t('View'),
        'ico'           => $this->basket->getIco('eye.svg'),
        'attributes'    => new Attribute([
          'target'        => '_blank',
          'href'          => Url::fromRoute('entity.node.canonical', ['node' => $this->entity->id()])->toString()
        ])
      ],
    ];
    if(!empty($operations['translate'])) {
      $links['translate'] = [
        'text'          => $operations['translate']['title'],
        'ico'           => $this->basket->getIco('google.svg'),
        'attributes'    => new Attribute([
          'target'        => '_blank',
          'href'          => $operations['translate']['url']->toString()
        ])
      ];
    }
    if(!empty($operations['quick_clone'])) {
      $links['quick_clone'] = [
        'text'          => $operations['quick_clone']['title'],
        'ico'           => $this->basket->getIco('clone.svg'),
        'attributes'    => new Attribute([
          'target'        => '_blank',
          'href'          => $operations['quick_clone']['url']->toString()
        ])
      ];
    }
    $isDelete = \Drupal::database()->select('basket_node_delete', 'n')
      ->fields('n')
      ->condition('n.nid', $this->entity->id())
      ->execute()->fetchField();
    if ($isDelete) {
      $links['restore'] = [
        'text'        => $this->basket->Translate()->t('Restore'),
        'ico'         => $this->basket->getIco('restore.svg'),
        'attributes'    => new Attribute([
          'href'          => 'javascript:void(0);',
          'data-post'     => json_encode(['nid' => $this->entity->id()]),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-node_restore'])->toString() . '\')'
        ])
      ];
      $links['delete'] = [
        'text'        => $this->basket->Translate()->t('Permanently remove'),
        'ico'         => $this->basket->getIco('trash.svg'),
        'attributes'    => new Attribute([
          'href'           => Url::fromRoute('entity.node.delete_form', ['node' => $this->entity->id()])->toString()
        ])
      ];
    }
    // Alter
    \Drupal::moduleHandler()->alter('stockProductLinks', $links, $this->entity);
    // ---
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
    [
      '#prefix'       => '<div class="b_title">',
      '#suffix'       => '</div>',
      '#type'         => 'inline_template',
      '#template'     => '{{ title }}
        {% for link in links %}
          <a{{ link.attributes.addClass(\'button--link\') }}><span class="ico">{{ link.ico|raw }}</span> {{ link.text }}</a>
        {% endfor %}',
      '#context'      => [
        'title'         => $this->entity->getTitle(),
        'links'         => $links
      ],
    ], [
      '#prefix'       => '<div class="b_content">',
      '#suffix'       => '</div>',
      'form'          => \Drupal::service('entity.form_builder')->getForm($this->entity),
    ],
    ];
  }

}
