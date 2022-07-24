<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * Set nid.
   *
   * @var int
   */
  protected $nid;

  /**
   * {@inheritdoc}
   */
  public function __construct($nid) {
    $this->nid = $nid;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->merge('basket_node_delete')
      ->key([
        'nid'         => $this->nid,
      ])
      ->fields([
        'uid'         => \Drupal::currentUser()->id(),
        'delete_time' => time(),
      ])
      ->execute();
    $form_state->setRedirect('basket.admin.pages', ['page_type' => 'stock-product']);
    // ---
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($this->nid);
    if (!empty($entity)) {
      $entity->set('status', 0);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_basket_node_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.canonical', ['node' => $this->nid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete %id?', ['%id' => $this->nid]);
  }

}
