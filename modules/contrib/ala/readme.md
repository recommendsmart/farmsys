Advance Link Attributes
=======================

INTRODUCTION
------------

Advance Link attributes widget provides an additional widget for the link field.
The widget allows users to set following attributes/options on their link.

- Target
- A class
- A class for an icon
- Visibility for user roles

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ala

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/ala
   
REQUIREMENTS
------------
This module requires the following modules:

 * Field Link

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
 * Composer require drupal/ala
 
CONFIGURATION
-------------

 * Using '**Manage form display**' select the 'Advance Link Attributes' widget.
 * Global class list cab be configured in module configuration page.
      
   - Class Settings: you have following options in every single field.
     - Disabled (default)
     - Global List (to define your global class list go to /admin/config/ala )
     - Custom List
   - Icon Settings
   - User Role settings
   - Custom Color & BG color
 
Select Advance Link Attributes formatter and you have following options.

- Class Option (where must be added the class selected)
    - Link Element (as attribute class of tag "a")
    - Parent Element (as attribute class of parent element)
- Icon Position
    - As tag "i" inside element
    - As a class
    - As data-attr
- Role Visibility
    - Hide (No render)
    - Visually Hidden (Rendered but hidden)

RECOMMENDED MODULES
-------------------

 * Link Attributes widget: https://www.drupal.org/project/link_attributes
 * Menu attributes: https://www.drupal.org/project/menu_attributes
 * Menu Link Attributes: https://www.drupal.org/project/menu_link_attributes
 * Menu Item Extras: https://www.drupal.org/project/menu_item_extras

MAINTAINERS
-----------

Current maintainers:
 * Kushan Gunasinghe - https://www.drupal.org/user/3619158
