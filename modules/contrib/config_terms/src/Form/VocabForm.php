<?php

namespace Drupal\config_terms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VocabForm.
 *
 * @package Drupal\config_terms\Form
 */
class VocabForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /**
     * @var \Dupal\config_terms\Entity\VocabInterface $config_terms_vocab
     */
    $config_terms_vocab = $this->entity;

    if ($config_terms_vocab->isNew()) {
      $form['#title'] = $this->t('Add config terms vocab');
    }
    else {
      $form['#title'] = $this->t('Edit config terms vocab');
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config_terms_vocab->label(),
      '#description' => $this->t("Label for the Config term vocab."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config_terms_vocab->id(),
      '#machine_name' => [
        'exists' => '\Drupal\config_terms\Entity\Vocab::load',
      ],
      '#disabled' => !$config_terms_vocab->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $config_terms_vocab->getDescription(),
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $config_terms_vocab->getWeight(),
      '#description' => $this->t('Vocabs are displayed in ascending order by weight.'),
      '#required' => TRUE,
    ];

    // Set the hierarchy to "multiple parents" by default. This simplifies the
    // config_terms_vocab form and standardizes the term form.
    $form['hierarchy'] = [
      '#type' => 'value',
      '#value' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config_terms_vocab = $this->entity;
    $status = $config_terms_vocab->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Config term vocab.', [
          '%label' => $config_terms_vocab->label(),
        ]));
        $this->logger('config_terms')->notice('Created new config terms vocab %name.', ['%name' => $config_terms_vocab->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($config_terms_vocab->toUrl('overview-form'));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Config term vocab.', [
          '%label' => $config_terms_vocab->label(),
        ]));
        $this->logger('config_terms')->notice('Updated config terms vocab %name.', ['%name' => $config_terms_vocab->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($config_terms_vocab->toUrl('collection'));
    }
  }

}
