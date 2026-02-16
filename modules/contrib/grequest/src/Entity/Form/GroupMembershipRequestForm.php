<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\MembershipRequestManager;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for requesting a group membership.
 */
class GroupMembershipRequestForm extends ContentEntityForm implements ConfirmFormInterface {

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Constructs a request membership form.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MembershipRequestManager $membership_request_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->membershipRequestManager = $membership_request_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->getGroup()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->getConfirmText();
    $actions['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $actions;
  }

  /**
   * Form cancel handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Make field not accessible, because we set it programmatically.
    $form['entity_id']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $violations = $this->entity->validate();
    foreach ($violations as $violation) {
      $constraint = $violation->getConstraint();
      if ($constraint instanceof GroupContentCardinality && $constraint->entityMessage == $violation->getMessage()->getUntranslatedString()) {
        $form_state->setError($form, $this->t('You have already sent a request'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Your request is waiting for approval'));
    $form_state->setRedirectUrl($this->getCancelUrl());
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {}

  /**
   * {@inheritdoc}
   */
  public function getDescription() {}

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Request group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {}

}
