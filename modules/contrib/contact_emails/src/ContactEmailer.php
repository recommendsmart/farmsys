<?php

namespace Drupal\contact_emails;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\contact_emails\Entity\ContactEmailInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxy;

/**
 * Class ContactEmailer.
 *
 * @package Drupal\contact_emails
 */
class ContactEmailer {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails
   */
  protected $contactEmails;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Egulias\EmailValidator\EmailValidator definition.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Contact form machine name.
   *
   * @var string
   */
  protected $contactForm;

  /**
   * Contact message entity.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $contactMessage;

  /**
   * Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $plugin_manager_mail
   *   MailManagerInterface.
   * @param \Drupal\contact_emails\ContactEmails $contact_emails
   *   ContactEmails.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   AccountProxy.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   EmailValidatorInterface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   MessengerInterface.
   */
  public function __construct(
    MailManagerInterface $plugin_manager_mail,
    ContactEmails $contact_emails,
    AccountProxy $current_user,
    EmailValidatorInterface $email_validator,
    MessengerInterface $messenger
  ) {
    $this->mailManager = $plugin_manager_mail;
    $this->contactEmails = $contact_emails;
    $this->currentUser = $current_user;
    $this->emailValidator = $email_validator;
    $this->messenger = $messenger;
  }

  /**
   * Set the contact form.
   *
   * @param object $contactMessage
   *   The contact message.
   */
  public function setContactMessage($contactMessage) {
    $this->contactForm = $contactMessage->getContactForm()->id();
    $this->contactMessage = $contactMessage;
  }

  /**
   * Send the emails.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function sendEmails() {
    /** @var \Drupal\contact_emails\ContactEmailStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('contact_email');
    $contact_emails = $storage->loadValid($this->contactForm, TRUE);
    if ($contact_emails) {
      foreach ($contact_emails as $email) {
        $module = 'contact_emails';
        $key = 'contact_emails';
        $to = $this->getTo($email);
        $reply_to = $email->getReplyTo($this->contactMessage);

        // Stop here if we don't know who to send to.
        if (!$to) {
          $error = $this->t('Unable to determine who to send the message to for for email id @id', [
            '@id' => $email->id(),
          ]);
          $this->messenger->addWarning($error);
          continue;
        }

        $params['subject'] = $email->getSubject($this->contactMessage);
        $params['format'] = $email->getFormat($this->contactMessage);
        $params['message'] = $email->getBody($this->contactMessage);
        $params['contact_message'] = $this->contactMessage;
        $params['contact_email'] = $email;

        // Final prep and send.
        $language_code = $this->currentUser->getPreferredLangcode();
        $this->mailManager->mail($module, $key, $to, $language_code, $params, $reply_to, TRUE);
      }
    }
  }

  /**
   * Get who to send the email to.
   *
   * @param \Drupal\contact_emails\Entity\ContactEmailInterface $email
   *   The email settings.
   *
   * @return string
   *   The to string to be used by the mail manager.
   */
  protected function getTo(ContactEmailInterface $email) {
    $to = $this->removeInvalidEmails($email->getRecipients($this->contactMessage));
    return implode(', ', $to);
  }

  /**
   * Remove invalid emails.
   *
   * @param array $emails
   *   An array of potentially valid emails.
   *
   * @return array
   *   An array of valid emails.
   */
  protected function removeInvalidEmails(array $emails) {
    $valid_emails = [];
    foreach ($emails as $email) {
      if ($this->emailValidator->isValid($email)) {
        $valid_emails[] = $email;
      }
      else {
        $error = $this->t('The following email does not appear to be valid and was not sent to: @email', [
          '@email' => $email,
        ]);
        $this->messenger->addWarning($error);
      }
    }
    return $valid_emails;
  }

}
