CONTENTS OF THIS FILE
---------------------
- Introduction
- Installation
- Permissions
- Usage


INTRODUCTION
------------
Color Palette module provides a widget that launches a color palette with
a pre-approved color options for capturing entity field inputs.
Unlike any other colorpicker, this widget only shows configured set of
color(managed over a taxonomy) options in the palette. Further, color palette
could be configured per field level to filter palette color options with
say, Light colors only, Dark colors only, both Light & Dark colors etc.

Color palette widget collect color input value in hexcode for entity fields.
Example value, #ff0000, $#00ff00 etc.
This widget supports 'Text' and 'Taxonomy Term' field type at the moment.


INSTALLATION
------------
Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/documentation/install/modules-themes/modules-8


PERMISSIONS
-------------
Find 'Permission' link on Extend page i.e., at admin/modules page against
the module name, OR direct link admin/people/permissions#module-colorpalette.

Available user permissions:
1. Administer Palette
   User with this permission can create new color(s) on-the-fly
   or re-use existing color(s) from the launched palette itself.


USAGE
-------------
Once installed this module provides two new taxonomy/vocabulary:
1. Colors
   New colors can be introduced creating terms within this vocabulary.
   Direct link-
   admin/structure/taxonomy/manage/colorpalette_filter_tags/overview
2. Color Filter Tags
   Terms of this vocabulary could be used to filter colors options
   in the palette. Example, Light, Dark etc.
   Direct link-
   admin/structure/taxonomy/manage/colorpalette_filter_tags/overview

Now, any of the entity field of type 'Text' OR 'Taxonomy Term' could leverage
the color palette input options while creating respective contents.

Say, in case of node entity fields, perform below actions:
- Navigate to 'Structure > Content Types > My Node Type > Manage Form Display'
  against the concerned node type
- Opt for 'Color Palette' widget against the respective field(s)
- Click on respective gear-icon for settings now choose 'Filter tags'(Optional)
- Finally, save the page
- Now, navigate to 'Structure > Content Types > My Node Type > Manage Fields'
  and make sure correct Vocabulary(Color) is selected for the respective
  field(s) settings
Now, while creating the node, the user will find that a color palette launches
for the respective fields.

COLOR PALETTE INTERACTION:
1. Selecting/Deselecting colors:
   - Click on any of the color to use color's hexcode value for the field
   - Click on 'Clear' button to clear the field value
2. Create New Color
   - 'Administer Palette' permission is required to perform this action
   - Click on 'New Color' button
   - Choose a 'Color'
   - Provide 'Name'
   - Add 'Filter Tags'
   - Click on 'Submit & Apply' button

IMPORTANT:
While creating a new color by using above steps, please remember that
1. If provided 'Color' hexcode-value DOES NOT already exists, then
   - New Color will be created
   - 'Field Tags' input value
     & Filter tags configured for field while opting the widget will be merged
   - Finally, the new Color will have above merged 'Filter Tags' value
2. And if provided 'Color' hexcode-value DO exists, then
   - New Color will NOT be created
   - Exiting color will be used
   - Existing color if NOT published will get published forcefully
   - 'Field Tags' input value
     & Filter tags configured for field while opting the widget
     & Field Tags of exiting color will be merged
   - Finally, Color will have above merged 'Filter Tags' value
