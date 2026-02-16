<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\MembershipRequestManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for approving a group membership request.
 */
class GroupMembershipApproveForm extends ContentEntityConfirmFormBase {

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a reject membership form.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MembershipRequestManager $membership_request_manager, LoggerInterface $logger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->membershipRequestManager = $membership_request_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager'),
      $container->get('logger.factory')->get('group_content')
    );
  }

  /**
   * Returns the plugin responsible for this piece of group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The responsible group content enabler plugin.
   */
  protected function getContentPlugin() {
    return $this->getEntity()->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to approve a request for %user?', ['%user' => $this->getEntity()->getEntity()->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
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
  public function getConfirmText() {
    return $this->t('Approve');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $roles = $this->getGroupRoles();
    if (!empty($roles)) {
      $form['roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Roles'),
        '#description' => $this->t('These roles will be assigned to user when membership request is approved'),
        '#options' => $roles,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group_content = $this->getEntity();
    $group_roles = [];
    $form_roles_values = $form_state->getValue('roles');
    if (!empty($form_roles_values)) {
      $group_roles = array_values($form_roles_values);
    }
    $result = $this->membershipRequestManager->approve($group_content, $group_roles);

    if ($result) {
      $this->messenger()->addStatus($this->t('Membership request approved'));
    }
    else {
      $this->messenger()->addError($this->t('Error updating request'));
    }

    $this->logger->notice('@type: approved %title.', [
      '@type' => $group_content->bundle(),
      '%title' => $group_content->label(),
    ]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get roles.
   *
   * @return array|null
   *   List of group type's custom roles.
   */
  protected function getGroupRoles() {
    $options = [];
    $properties = [
      'group_type' => $this->getEntity()->getGroup()->getGroupType()->id(),
      'internal' => FALSE,
    ];

    $roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadByProperties($properties);
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }
    return $options;
  }

}
