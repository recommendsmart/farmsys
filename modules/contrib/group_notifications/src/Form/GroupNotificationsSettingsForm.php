<?php

namespace Drupal\group_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Handles the settings form for group_notifications.
 */
class GroupNotificationsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'group_notifications_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'group_notifications.mail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $mailConfig = $this->config('group_notifications.mail');
    $form['email'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
    ];
    // These email tokens are shared for all settings, so just define
    // the list once to help ensure they stay in sync.
    $emailTokenHelp = $this->t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url], [group:title].');

    $form['email_membership_added'] = [
      '#type' => 'details',
      '#title' => $this->t('Membership added'),
      '#description' => $this->t('Enable and edit email messages sent to users when their memberships are added.') . ' ' . $emailTokenHelp,
      '#group' => 'email',
    ];
    $form['email_membership_added']['membership_added_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when their membership is added'),
      '#default_value' => $mailConfig->get('membership_added.enabled'),
    ];
    $form['email_membership_added']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the settings when the enabled checkbox is disabled.
        'invisible' => [
          'input[name="membership_added_enabled"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['email_membership_added']['settings']['membership_added_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mailConfig->get('membership_added.mail.subject'),
      '#maxlength' => 180,
    ];
    $form['email_membership_added']['settings']['membership_added_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mailConfig->get('membership_added.mail.body'),
      '#rows' => 5,
    ];

    $form['email_membership_removed'] = [
      '#type' => 'details',
      '#title' => $this->t('Membership removed'),
      '#description' => $this->t('Enable and edit email messages sent to users when their memberships are removed.') . ' ' . $emailTokenHelp,
      '#group' => 'email',
    ];
    $form['email_membership_removed']['membership_removed_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when their membership is removed'),
      '#default_value' => $mailConfig->get('membership_removed.enabled'),
    ];
    $form['email_membership_removed']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the settings when the enabled checkbox is disabled.
        'invisible' => [
          'input[name="membership_removed_enabled"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['email_membership_removed']['settings']['membership_removed_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mailConfig->get('membership_removed.mail.subject'),
      '#maxlength' => 180,
    ];
    $form['email_membership_removed']['settings']['membership_removed_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mailConfig->get('membership_removed.mail.body'),
      '#rows' => 5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('group_notifications.mail')
      ->set('membership_added.enabled', $form_state->getValue('membership_added_enabled'))
      ->set('membership_added.mail.subject', $form_state->getValue('membership_added_subject'))
      ->set('membership_added.mail.body', $form_state->getValue('membership_added_body'))
      ->set('membership_removed.enabled', $form_state->getValue('membership_removed_enabled'))
      ->set('membership_removed.mail.subject', $form_state->getValue('membership_removed_subject'))
      ->set('membership_removed.mail.body', $form_state->getValue('membership_removed_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
