<?php

namespace Drupal\config_terms\Form;

use Drupal\config_terms\Entity\VocabInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigTermForm.
 *
 * @package Drupal\config_terms\Form
 */
class TermForm extends EntityForm {

  /**
   * The config term entity.
   *
   * @var \Drupal\config_terms\Entity\TermInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $config_term = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config_term->label(),
      '#description' => $this->t("Label for the Config term."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config_term->id(),
      '#machine_name' => [
        'exists' => '\Drupal\config_terms\Entity\Term::load',
      ],
      '#disabled' => !$config_term->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $config_term->getDescription(),
      '#title' => $this->t('Description'),
    ];

    $vocab_storage = $this->entityTypeManager->getStorage('config_terms_vocab');
    /** @var \Drupal\config_terms\Entity\VocabInterface $vocab */
    $vocab = $vocab_storage->load($config_term->getVid());

    $parents = $config_term->getParents();
    $form_state->set(['config_terms', 'parents'], $parents);
    $form_state->set(['config_terms', 'vocab'], $vocab);

    $form['relations'] = [
      '#type' => 'details',
      '#title' => $this->t('Relations'),
      '#open' => $vocab->getHierarchy() == VocabInterface::HIERARCHY_MULTIPLE,
      '#weight' => 10,
    ];

    // \Drupal\config_terms\ConfigTermTermStorageInterface::loadTree() and
    // \Drupal\config_terms\ConfigTermTermStorageInterface::loadParents() may
    // contain large numbers of items so we check for
    // config_terms.settings:override_selector before loading the full vocab.
    // Contrib modules can then intercept before hook_form_alter to provide
    // scalable alternatives.
    if (!$this->config('config_terms.settings')->get('override_selector')) {
      $parents = $config_term->getParents();
      if (empty($parents) || $parents === [0]) {
        $parents = ['0'];
      }

      /**
       * @var \Drupal\config_terms\TermStorageInterface $term_storage
       */
      $term_storage = $this->entityTypeManager->getStorage('config_terms_term');

      $children = $term_storage->loadTree($vocab->id(), $config_term->id());

      // A term can't be the child of itself, nor of its children.
      $exclude = [];
      foreach ($children as $child) {
        $exclude[$child->id()] = 1;
      }
      $exclude[$config_term->id()] = 1;

      $options = ['<' . $this->t('root') . '>'];
      $options += $term_storage->getTermOptions($vocab->id());
      $options = array_diff_key($options, $exclude);

      $form['relations']['parents'] = [
        '#type' => 'select',
        '#title' => $this->t('Parent terms'),
        '#options' => $options,
        '#default_value' => $parents,
        '#multiple' => TRUE,
      ];
    }

    $form['relations']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $config_term->getWeight(),
      '#description' => $this->t('Terms are displayed in ascending order by weight.'),
      '#required' => TRUE,
    ];

    $form['vid'] = [
      '#type' => 'value',
      '#value' => $vocab->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config_term = $this->entity;

    $status = $config_term->save();

    $link = $config_term->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t(
          'Created new term %term.',
          ['%term' => $config_term->getName()]
        ));
        $this->logger('config_terms')->notice(
          'Created new term %term.',
          ['%term' => $config_term->getName(), 'link' => $link]
        );
        break;

      default:
        $this->messenger()->addMessage($this->t(
          'Updated term %term.',
          ['%term' => $config_term->getName()]
        ));
        $this->logger('config_terms')->notice(
          'Updated term %term.',
          ['%term' => $config_term->getName(), 'link' => $link]
        );
    }

    $current_parent_count = count($form_state->getValue('parents'));
    $previous_parent_count = count($form_state->get(['config_terms', 'parents']));

    // Root doesn't count if it's the only parent.
    if ($current_parent_count == 1 && $form_state->hasValue(['parents', '0'])) {
      $current_parent_count = 0;
      $form_state->setValue('parents', []);
    }

    // If the number of parents has been reduced to one or none, do a check on
    // the parents of every term in the vocab value.
    $vocab = $form_state->get(['config_terms', 'vocab']);
    if ($current_parent_count < $previous_parent_count && $current_parent_count < 2) {
      config_terms_check_vocab_hierarchy($vocab, $form_state->getValues());
    }
    // If we've increased the number of parents and this is a single or flat
    // hierarchy, update the vocab immediately.
    elseif ($current_parent_count > $previous_parent_count && $vocab->getHierarchy() != VocabInterface::HIERARCHY_MULTIPLE) {
      $vocab->setHierarchy($current_parent_count == 1 ? VocabInterface::HIERARCHY_SINGLE : VocabInterface::HIERARCHY_MULTIPLE);
      $vocab->save();
    }

    $form_state->setValue('id', $config_term->id());
    $form_state->set('id', $config_term->id());
  }

}
