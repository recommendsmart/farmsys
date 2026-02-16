<?php

namespace Drupal\social_pwa;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Minishlink\WebPush\Subscription;

/**
 * The web push manager helps in administering web push notification data.
 */
class WebPushManager implements WebPushManagerInterface {

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Create a new WebPushManager instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param \Drupal\user\UserDataInterface $userData
   *   Drupal user data.
   */
  public function __construct(StateInterface $state, UserDataInterface $userData) {
    $this->state = $state;
    $this->userData = $userData;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuth() : array {
    // Get the VAPID keys that were generated before.
    $vapid_keys = $this->state->get('social_pwa.vapid_keys');

    return [
      'VAPID' => [
        'subject' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
        'publicKey' => $vapid_keys['public'],
        'privateKey' => $vapid_keys['private'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionsForUser(UserInterface $user) : array {
    return array_map(
      static function ($subscription) {
        return new Subscription(
          $subscription['endpoint'],
          $subscription['key'],
          $subscription['token']
        );
      },
      $this->userData->get('social_pwa', $user->id(), 'subscription') ?? []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function removeSubscriptionsForUser(UserInterface $user, array $endpoints) : void {
    $this->userData->set('social_pwa', $user->id(), 'subscription',
      array_filter(
        $this->userData->get('social_pwa', $user->id(), 'subscription') ?? [],
        static function ($subscription) use ($endpoints) {
          return !in_array($subscription['endpoint'], $endpoints, TRUE);
        }
      )
    );
  }

}
