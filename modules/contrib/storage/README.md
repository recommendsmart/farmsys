CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Storage Entities module provides a new bundlable entity type, specifically
for content that needs to be stored but not accessed or managed directly by
site users. If you find yourself creating a content type only to use something
like Rabbit Hole to prevent direct access, then Storage Entities may be a better
fit.


 * For the description of the module visit:
   https://www.drupal.org/project/storage

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/3206347


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Storage Entities module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Content > Extend and enable the module.
    2. Navigate to Structure > Storage Types to add one or more bundles,
       similar to content types. Each can have their own fields and other
       settings and configuration. By design, the process is meant to be
       similar to creating content types.

Additional notes:
 * The "Published" toggle and author information are hidden from the form by
   default, but can be configured to display if necessary.
 * The contrib Entity API (https://www.drupal.org/project/entity) can be
   installed to make automatically use of its query access handler, so that
   views can filter for accessible storage entities on database level.

MAINTAINERS
-----------

 * Martin Anderson-Clutz - https://www.drupal.org/u/mandclu
 * Maximilian Haupt - https://www.drupal.org/u/mxh

Supporting organization:

 * Northern - https://www.drupal.org/digital-echidna-a-northern-company
