<?php

namespace Drupal\eca_user\Plugin\ECA\Event;

use Drupal\Core\Session\AccountEvents;
use Drupal\Core\Session\AccountSetEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_user\Event\UserCancel;
use Drupal\eca_user\Event\UserEvents;
use Drupal\eca_user\Event\UserLogin;
use Drupal\eca_user\Event\UserLogout;
use Drupal\user\Event\UserEvents as CoreUserEvents;
use Drupal\user\Event\UserFloodEvent;

/**
 * Plugin implementation of the ECA Events for users.
 *
 * @EcaEvent(
 *   id = "user",
 *   deriver = "Drupal\eca_user\Plugin\ECA\Event\UserEventDeriver"
 * )
 */
class UserEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    return [
      'login' => [
        'label' => 'Login of a user',
        'event_name' => UserEvents::LOGIN,
        'event_class' => UserLogin::class,
        'tags' => Tag::WRITE | Tag::EPHEMERAL | Tag::AFTER,
      ],
      'logout' => [
        'label' => 'Logout of a user',
        'event_name' => UserEvents::LOGOUT,
        'event_class' => UserLogout::class,
        'tags' => Tag::WRITE | Tag::EPHEMERAL | Tag::AFTER,
      ],
      'cancel' => [
        'label' => 'Cancelling a user',
        'event_name' => UserEvents::CANCEL,
        'event_class' => UserCancel::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'floodblockip' => [
        'label' => 'Flood blocked IP',
        'event_name' => CoreUserEvents::FLOOD_BLOCKED_IP,
        'event_class' => UserCancel::class,
        'tags' => Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'floodblockuser' => [
        'label' => 'Flood blocked user',
        'event_name' => CoreUserEvents::FLOOD_BLOCKED_USER,
        'event_class' => UserFloodEvent::class,
        'tags' => Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'set_user' => [
        'label' => 'Set current user',
        'event_name' => AccountEvents::SET_USER,
        'event_class' => AccountSetEvent::class,
        'tags' => Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
    ];
  }

}
