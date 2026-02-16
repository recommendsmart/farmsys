CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Usage
 * Maintainers

INTRODUCTION
------------

The taxonomy term locks module provides support to lock specific terms
so that users are not able to edit or delete terms that have a lock
placed on them.

Taxonomy term locks also provides integration to do bulk set/delete
locks on taxonomy terms programmatically.

REQUIREMENTS
------------

This module requires only that Drupal 8 or greater is installed.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
 * Visit https://www.drupal.org/node/1897420 for further information.

USAGE
-----
 * Download and enable the module.
 * There are two permissions with this module.  Set term lock, allows
users to set the term lock and bypass term lock, which allows users
to still view the edit/delete page and see the operations link on
the taxonomy overview page.
 * Visit any taxonomy term or add a new taxonomy term as an
administrator and there is a field to set the term lock.
 * Once the lock is set, only users with bypass taxonomy will
see the operations link for that term, all other users will
not have the operations displayed.
 * If you would like to do a bulk set and/or delete, then use
the service taxonomy_term_locks.term_lock and the functions
bulkSetLocks and bulkDeleteLocks.

MAINTAINERS
-----------

Current maintainer:

 * Eric Bremner - https://www.drupal.org/u/ebremner
