CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Recommended Modules
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows you to create custom publishing options for nodes. 
It allows you to add to the default options of Publish, Promote to Front Page, and Sticky. 
It also ingrates with views to allow you add as a field, sort and filter by, your custom options.

 * For the description of the module visit:
   https://www.drupal.org/project/custom_pub

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Custom Pub module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

Configure Custom Publishing Options on your site at admin/structure/custom_pub, under the Structure menu.
On each new option created, set the Node types the option should be available from.
Go to the Permissions page. Grant permission to each role that should be able to use the new publishing option.

Note that, using just Custom Publishing Options, on its own, a role needs 'administer nodes' in order to see any of the core publishing options at all. 
Without it, they still see custom publishing options, just not 'status', 'sticky', or 'promoted' states. 

RECOMMENDED MODULES
-------------------

 * Actions:
   If you want to use Custom Publish Options with Node operations bulk form, then you can enable Actions and configure an action.
   Visit https://www.drupal.org/docs/8/core/modules/action/overview for further information.
 * Views:
   Enabling Views opens up a whole new avenue of displaying content with Custom Publishing Options. 
   Create your View any way you like, and under Filter you will find all Custom Publishing Options available. 
   Create archived content sections by creating an Archive option, and Filter by that option!
 * Override Node Options:
   If you want greater permissions to allow a role to use custom publishing options or core states (status, sticky, promoted), it is suggested that you also pick up Override Node Options module. 
   This module adds access control to each publish state at the role level, so 'administer nodes' is not a requirement.

MAINTAINERS
-----------

 * Jake Bell - https://www.drupal.org/u/jacobbell84
 * Vladimir Roudakov - https://www.drupal.org/u/vladimiraus

Supporting organization:

 * Tomato Elephant Studio - https://www.drupal.org/tomato-elephant-studio
 * ZenSource - https://www.drupal.org/zensource
