CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module provides a type of configuration entity to display entities in list
 form with plugins to manage each step of creating the list.

You have the possibility to create a list of any entity: Node, Media, Taxonomy
Term, User, Files, etc.
The module allows you to:
* Add a pager to your list.
* Choose the type of entity you want
* Choose the language of the elements to be uploaded
* Choose the number of items to display per page
* Added custom filter with EntityListFilters (custom configuration, custom rendering ...)

The display of the list is divided into regions: Header - Before - Content -
After.
You can place: The list items ( with the desired views mode), the total number
of items, the pager, or your filters in one of these regions.

Once your list is created, all you have to do is place it either through a block
 or return it as a field to an entity

REQUIREMENTS
------------
This module requires the following modules:
 * Layout Discovery (in core Drupal 8 / 9)

RECOMMENDED MODULES
-------------------
 * You can use [FAPI_collapsible]() for render filter as collapsible.

INSTALLATION
------------
  * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will prevent the links from appearing. To get the links
back, disable the module and clear caches.
    admin/structure/entity_list

### Documentation Developer

 - [First Entity List](/README_first_entity_list.md)
 - [EntityListDisplay Plugin](/README_entity_list_display.md)
 - [EntityListQuery Plugin](/README_entity_list_query.md)
 - [EntityListFilter Plugin](/README_entity_list_filter.md)

MAINTAINERS
-----------

Current maintainers:
 * Marc-Antoine Marty (Martygraphie) - https://www.drupal.org/u/martygraphie
 * Leonard Treille (leonardT) - https://www.drupal.org/u/leonardt
 * Alexis Iannone (Anwoon) - https://www.drupal.org/u/anwoon

This project has been sponsored by:
 * Ecedi
