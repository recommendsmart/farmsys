Introduction
------------
Element are re-usable bits of content. Examples could include "tips"
that are displayed on various pages, or locations to be shown in a table 
overview. It is highly dependent on your application whether element 
are a good fit. In some cases standard nodes will be more appropriate 
(in general, when each item will needs its own page in addition to where 
ever it is shown), in others custom blocks might serve you better (be
aware of the limited control over permissions core blocks currently 
offers, though). However, in some cases, neither nodes nor blocks are 
appropriate; Element fills a gap between these two solutions. 
Element are similar to 
[Paragraphs](https://www.drupal.org/project/paragraphs), but where 
Paragraphs are bound to their host entity (and revisioned along with 
it), element are meant to be re-used and potentially update the same 
bit of content across many pages. Element are fieldable and 
revisionable.

Requirements
------------
### Entity Reference
Although not *technically* a requirement (i.e. Drupal won't force you to
install the module when it's not already installed), Element's use is
extremely limited without it. Entity Reference is the basic method with
which you associate Element to e.g. nodes in order to display them on
a page for end users. With that said, please report any other uses you
might find for Element that do not require Entity Reference (if that
sounds like a challenge, it is).

Recommended modules
-------------------
### Enhance the administrative overview
By default, Element has a simple administrative view. A much more 
powerful view will be installed when the 
[Views Bulk Operations](https://www.drupal.org/project/views_bulk_operations) 
module is available, containing filters and sorting options. It is 
highly recommended to install this module, the default screen is only 
meant for the most basic of use cases where it is absolutely undesirable 
to install VBO.

From an editorial workflow point of view, several modules go together 
particularly well with Element, depending on your use-case.

### Create new element where you need them
[Inline Entity Form](https://www.drupal.org/project/inline_entity_form) 
offers a entity reference widget that will display a form to create a 
new fragment inline in e.g. the node form where you reference 
element. In certain use cases you may want to enforce the need to 
create a fragment separately, but in many cases it is extremely 
convenient to be able to create a new fragment on the fly, right where
you need it.

### Automatically create an administrative label from a display label
Every fragment needs a label to identify it in the editorial 
interface, but in many cases you do not want to tie this to a title 
displayed to users (i.e. two element may need the same title on your 
site's frontend, but editors still need a way to distinguish them when 
picking them in an entity reference field). So, you end up adding a
<em>Display Title</em> field. 
[Automatic Entity Label](https://www.drupal.org/project/auto_entitylabel) 
will help to set things up so editorial users only need to fill in the 
display title in most cases, but still have the option to override the 
administrative label when needed.

Installation
------------
Install as you would normally install a contributed Drupal module. Visit
the documentation page on [installing Drupal 8 
modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

Configuration
-------------
Once enabled, it will be possible to define fragment types, much like 
you would create content types. element types can have their own 
field and display configuration. You will also have an additional 
section under /admin/content, titled Element, at 
/admin/content/element.

Once you created one or more element types, you probably want to set up
some entity reference fields to refer to them in one of your content 
types. 

### Permissions
Take note that element defines a view permission for every element
type created. Be sure to assign it if you want anyone but administrators
to see any element.

In addiiton, element defines the following permissions appropriate for 
administrative users:

*   Access element overview. Users with this permission can 
    access the element overview page.
*   Administer element items. Allows users to edit, create and delete 
    any element items.
*   Administer element types. Allows users to configure element types 
    and their fields.
    
Element defines the following permissions appropriate for editors (and
administrative users):

*   Per element type: create element. Allows users to create 
    element of the particular type.
*   Per element type: delete any element. Allows users to delete any 
    element of the particular type.
*   Per element type: delete own element. Allows users to delete 
    element they created of that particular type.
*   Per element type: Edit any element. Allows users to edit any 
    element of the particular type.
*   Per element type: Edit own element. Allows users to edit the 
    element they created the particular type.

Troubleshooting
---------------
### If users don't see your element
Make sure you assigned the view permission to some user roles. In most 
scenario's, you want to assign the view permission to both the anonymous
and verified user roles. 
