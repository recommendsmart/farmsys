# Description

This module allows adding log messages to entities. Log messages are shown on
a "Log" tab on the entity they are associated with.

Log messages can be added to a configurable set of entity types.

This module is intended to be used in cases where you want to store log messages
but the default Drupal watchdog / logging interface is insufficient. E.g. you
want the log messages to appear within the context of an entity, you want more
persistency in the storage of these log message, and / or you want specific
roles to access these log messages, while you do not want these roles to access
the default Drupal watchdog UI.

# Usage

Module developers can add log messages to a specific entity, by using the
`entity_logger` service:

```
  // Get the entity logger service. Ideally this is done via dependency
  // injection.
  $entity_logger = \Drupal::service('entity_logger');
  $user = \Drupal\user\Entity\User::load(1);
  $entity_logger->log($user, 'This is a log message for user 1');
```
