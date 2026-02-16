<?php

namespace Drupal\storage\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\storage\Entity\StorageInterface;
use Drupal\storage\Entity\StorageType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Storage edit forms.
 *
 * @ingroup storage
 */
class StorageForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\storage\Entity\StorageInterface $entity */
    $entity = $this->entity;
    // Load the bundle.
    $bundle = StorageType::load($entity->bundle());

    // If explanation or submission guidelines are set, display them.
    if ($help = $bundle->getHelp()) {
      $form['help'] = [
        '#markup' => $help,
        '#weight' => -1000,
      ];
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @name', [
        '@type' => $entity->bundle(),
        '@name' => $entity->label(),
      ]);
    }
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('storage', $entity->bundle());
    $form['name']['#title'] = $fields['name']->getLabel();

    $revision_default = $bundle->get('new_revision');

    // Only expose the log field if so configured.
    if (!$bundle->shouldShowRevisionLog()) {
      $form['revision_log']['#access'] = FALSE;
    }

    if ($bundle->shouldShowRevisionToggle()) {
      if (!$this->entity->isNew()) {
        $form['new_revision'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Create new revision'),
          '#default_value' => $revision_default,
          '#weight' => 10,
        ];
      }
    }
    else {
      $form['new_revision'] = [
        '#type' => 'value',
        '#value' => $revision_default,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#entity_builders']['apply_name_pattern'] = [
      static::class,
      'applyNamePattern',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Storage.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Storage.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.storage.canonical', ['storage' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Explicitly set weights to a high value.
    $element['submit']['#weight'] = 100;
    if (array_key_exists('delete', $element)) {
      $element['delete']['#weight'] = 100;
    }

    return $element;
  }

  /**
   * Entity builder callback that applies the name pattern.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\storage\Entity\StorageInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function applyNamePattern($entity_type_id, StorageInterface $entity, array $form, FormStateInterface $form_state) {
    if (!isset($entity->original) && !$entity->isNew()) {
      // Load the unchanged values from the database in order to access
      // previous values.
      $entity->original = \Drupal::entityTypeManager()
        ->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    }
    $entity->applyNamePattern();
    // Disable the name pattern afterwards, in order to avoid redundant
    // rebuilds during the save operation chain.
    if ($entity->hasField('name_pattern')) {
      $entity->get('name_pattern')->setValue('');
    }
    else {
      $entity->name_pattern = '';
    }
  }

}
