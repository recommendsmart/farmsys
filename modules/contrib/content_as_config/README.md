INTRODUCTION
------------

There are several entities which Drupal considers to be content
which are, in the main, site-structure configuration. These entities
include custom block content, menu links, and taxonomy terms,
among others. The “Content as Configuration” module allows you to
save the content of these entities as configuration items, which
means you can export them as YAML files and move them from one
environment to another. It is easily extensible to handle other such
content entities; an example submodule supporting import/export of
feeds is included.

This module started out as a fork of the
[structure_sync](https://drupal.org/project/structure_sync)
module, which (at the time of this writing) is not Drupal 9 compatible,
and additionally does not offer export of feeds. This turned into
a full module rewrite to take full advantage of modern PHP’s
more advanced object-oriented capabilities.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core. The “Feeds as
Configuration” submodule requires the
[Feeds](https://drupal.org/project/feeds) contrib module.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/node/1897420 for further information.
  
CONFIGURATION
-------------

Configure import/export settings at Administration » Structure » Content as
Config. Local-task tabs on that page provide further import/export settings
on a per-entity-type basis.

DRUSH COMMANDS
--------------

This module also exposes Drush commands to allow automation of
import/export tasks.

Available Drush commands are as follows:

- `drush content_as_config:import <entity-type> [--style=safe|full|force]` -
  Imports entities of the given type (block_content, menu_link_content,
  taxonomy_term) using the given import style. (See below for an explanation of
  import styles.) Other modules may extend the list of supported exportable
  entity types; the key will always be the machine name of the entity type.

- `drush content_as_config:export <entity-type>` -
  Exports all entities of the given entity-type to configuration.

- `drush content_as_config:import-all [--style=safe|full|force]` -
  Performs import of all supported entity types in the given style. By default,
  this list includes block_content, menu_link_content, and taxonomy_term
  entities, as well as any types supported by other modules.

- `drush content_as_config:export-all` -
  Exports all instances of supported entity types to configuration.

More granular control over what gets imported and exported may be
achieved via the admin UI at /admin/structure/content-as-config.

##Import styles

### Safe
This import style imports all entities of the given type which
have been exported to configuration, *unless* they already exist
in the current Drupal instance. Safe import *will not* update
already-existing content.

### Full
This import style will delete any entities of the given type that
are *not* in configuration. It will then perform a full insert +
update import.

### Force
Deletes *all* entities of the given type in the system (without
any further checks), and then performs an insert-only import from
configuration. ***This is the nuclear option; use with care.***

MAINTAINERS
-----------

Current maintainers:
* Daniel Johnson (daniel_j) - https://www.drupal.org/user/970952

This project has been sponsored by:
* WebFirst
