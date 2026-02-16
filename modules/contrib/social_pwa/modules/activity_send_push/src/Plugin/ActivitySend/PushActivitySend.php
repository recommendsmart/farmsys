<?php

namespace Drupal\activity_send_push\Plugin\ActivitySend;

use Drupal\activity_send\Plugin\ActivitySendBase;
use Drupal\activity_creator\ActivityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Drupal\social_pwa\WebPushManagerInterface;
use Drupal\social_pwa\WebPushPayload;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Minishlink\WebPush\WebPush;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;

/**
 * Provides a 'PushActivitySend' activity action.
 *
 * @ActivitySend(
 *  id = "push_activity_send",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class PushActivitySend extends ActivitySendBase implements ContainerFactoryPluginInterface {

  /**
   * The web push manager.
   */
  protected WebPushManagerInterface $webPushManager;

  /**
   * The social PWA settings.
   */
  protected ImmutableConfig $settings;

  /**
   * The logger for this module.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebPushManagerInterface $web_push_manager, ImmutableConfig $settings, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webPushManager = $web_push_manager;
    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * Arguments have been adjusted for backwards compatibility with Open Social.
   */
  public static function create($container_or_activity, array $configuration = [], $plugin_id = NULL, $plugin_definition = NULL) {
    // Open Social 10 backwards compatibility layer.
    if ($container_or_activity instanceof ActivityInterface) {
      if (isset($this)) {
        $this->process($container_or_activity);
      }
      return;
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container_or_activity->get('social_pwa.web_push_manager'),
      $container_or_activity->get('config.factory')->get('social_pwa.settings'),
      $container_or_activity->get('logger.factory')->get('activity_send_push')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ActivityInterface $activity): void {
    // If push is disabled then we don't do anything.
    $push_enabled = $this->settings->get('status.all');
    if (!$push_enabled) {
      return;
    }

    $uid = $activity->field_activity_recipient_user->target_id;

    // If there is no recipient user for this activity then we can't send a push
    // notification to anyone.
    if (empty($uid)) {
      // We log this as a debug message since it doesn't make sense to use push
      // notifications for things that don't affect users. Detecting these
      // scenarios helps us improve the notification system.
      $this->logger->debug('Tried to send push notification for activity from message (%mid) that did not have a user recipient.', ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }

    $user = User::load($uid);
    if (!$user instanceof UserInterface) {
      $this->logger->debug('Ignored push notification for non-existing user.');
      return;
    }

    $user_subscriptions = $this->webPushManager->getSubscriptionsForUser($user);

    // If the user has no push subscriptions then we can stop early.
    if (empty($user_subscriptions)) {
      return;
    }

    // Prepare the payload with the message.
    $message_loaded = Message::load($activity->field_activity_message->target_id);
    $message = $message_loaded->getText();
    if (empty($message[0])) {
      $this->logger->error('Tried to send an empty push notification for mid: %mid', ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }
    $message_to_send = $message[0];

    $url = $activity->getRelatedEntityUrl();
    // If the related entity does not have a canonical URL then we don't have
    // anywhere for the user to go to when they click the push notification so
    // we shouldn't send a notification at all.
    if (!($url instanceof Url)) {
      $this->logger->error("Tried to send push notification for mid: %mid but the target entity doesn't have a canonical url", ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }

    // Set fields for payload.
    $message_to_send = html_entity_decode($message_to_send);
    $push_data = [
      'message' => strip_tags($message_to_send),
      'site_name' => $this->settings->get('name'),
      'url' => $url->toString(),
    ];

    $icon = $this->settings->get('icons.icon');
    if (!empty($icon)) {
      // Get the file id and path.
      $fid = $icon[0];
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($fid);
      $path = $file->createFileUrl(FALSE);

      $push_data['icon'] = file_url_transform_relative($path);
    }

    // Encode payload.
    $serialized_payload = (new WebPushPayload('legacy', $push_data))->toJson();

    $auth = $this->webPushManager->getAuth();
    $webPush = new WebPush($auth);

    foreach ($user_subscriptions as $subscription) {
      $webPush->queueNotification($subscription, $serialized_payload);
    }

    $outdated_subscriptions = [];
    // Send each notification and check the results.
    // flush() returns a generator that won't actually send all batches until
    // we've consumed all the results of the previous batch.
    /** @var \Minishlink\WebPush\MessageSentReport $push_result */
    foreach ($webPush->flush() as $push_result) {
      if ($push_result->isSubscriptionExpired()) {
        $outdated_subscriptions[] = $push_result->getEndpoint();
      }
    }

    if (!empty($outdated_subscriptions)) {
      $this->webPushManager->removeSubscriptionsForUser($user, $outdated_subscriptions);
    }
  }

}
