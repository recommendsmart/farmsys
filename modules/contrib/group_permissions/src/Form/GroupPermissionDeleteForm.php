<?php

namespace Drupal\group_permissions\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group_permissions\Entity\GroupPermission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a form for deleting a group permissions revision.
 *
 * @ingroup group_permissions
 */
class GroupPermissionDeleteForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * The group permissions revision.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermissionInterface
   */
  protected $groupPermission;

  /**
   * The group permissions storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->groupPermissionStorage = $container->get('entity_type.manager')->getStorage('group_permission');
    $instance->groupPermissionsManager = $container->get('group_permission.group_permissions_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permissions_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete custom group permissions for group %title?', [
      '%title' => $this->group_permission->getGroup()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.group_permission.canonical', [
      'group' => $this->group_permission->getGroup()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group = NULL) {
    $this->group_permission = $this->groupPermissionsManager->loadByGroup($group);
    if (empty($this->group_permission)) {
      $this->messenger()->addError($this->t('Group permission does not exist.'));
      $form_state->setRedirect('<front>');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->groupPermissionStorage->delete([$this->group_permission]);

    $group_title = $this->group_permission->getGroup()->label();
    $this->logger('group_permissions')->notice('group permissions for group %title revision %revision.', [
      '%title' => $group_title,
    ]);
    $this->messenger()->addMessage($this->t('Group permissions for group %title has been deleted.', [
      '%title' => $group_title,
    ]));
    $form_state->setRedirect('entity.group.canonical', [
      'group' => $this->group_permission->getGroup()->id(),
    ]);
  }

}
