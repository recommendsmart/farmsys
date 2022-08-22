<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Subset of methods for adding user account fields into configuration forms.
 */
abstract class UserAccount {

  /**
   * Get available user account fields.
   *
   * @return array
   *   Available user account fields.
   */
  public static function getAvailableFields(): array {
    return [
      'status' => t('Status'),
      'roles' => t('Roles'),
    ];
  }

  /**
   * Process callback to insert elements of the user account form.
   *
   * @param array &$form
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function processUserAccountForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $form['#flow__entity'];

    $form['status'] = [
      '#type' => 'radios',
      '#title' => t('Status'),
      '#default_value' => (int) $account->get('status')->value,
      '#options' => [t('Blocked'), t('Active')],
    ];

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => $account->getRoles(),
      '#options' => $roles,
    ];

    // Special handling for the inevitable "Authenticated user" role.
    $form['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#access' => FALSE,
    ];
  }

  /**
   * Submit callback for elements of the user account form.
   *
   * @param array &$element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submitUserAccountForm(array &$form, FormStateInterface $form_state): void {
    $wrapper_id = $form['#wrapper_id'];
    $content_config_entities = $form_state->get('flow__content_configuration') ?? [];
    if (!isset($content_config_entities[$wrapper_id])) {
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    [$account] = $content_config_entities[$wrapper_id];

    if ($form_state->hasValue('roles') && is_string(key($form_state->getValue('roles')))) {
      $form_state->setValue('roles', array_keys(array_filter($form_state->getValue('roles'))));
    }

    $roles = [];
    foreach ($form_state->getValue('roles', []) as $rk => $rv) {
      if ($rv && $rv !== RoleInterface::AUTHENTICATED_ID) {
        $roles[] = $rv;
      }
    }
    $account->get('roles')->setValue($roles);
  }

}
