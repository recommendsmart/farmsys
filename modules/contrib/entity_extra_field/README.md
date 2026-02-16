Entity Extra Field
===========

The entity extra field module allows site administrators to add various extra fields to an entity display. These extra fields can consist of blocks, views, or token values. Both entity form and view displays are supported.

Adding extra fields is really common when you're building robust web applications that need to be highly configurable. Allowing for missing elements such as page title, or potentially a block that needs to be render between two pieces of content are some examples.

Installation
------------

* Normal module installation procedure. See
  https://www.drupal.org/documentation/install/modules-themes/modules-8

* Install the Entity Extra Field UI module, which has been separated into a submodule named `entity_extra_field_ui`. The UI module is required to add new extra fields to an entity.

	The UI module can be safely disabled after the extra fields have been attached to an entity. As there are use cases on where field_ui should be disabled in production.

Initial Setup
------------

After the installation, you'll be able to define extra fields for any fieldable entity. This process is similar to how you would go about adding a normal field to an entity. I'll be using the basic page content type as example in this documentation.

* Navigate to the content-types listing page (e.g.  `/admin/structure/types`).

* If you click on the dropdown within the operation column, you'll see a new option "Manage extra fields" available.

* Click on the "Manage extra fields" option (e.g. `/admin/structure/types/manage/page/extra-fields`).

* You'll be redirected to a page that shows all the extra fields that have been created for the given entity.

* In the top left you'll see an "Add extra field" action link.

* Click on the "Add extra field" action link.

* Now you'll be redirected to the extra field add screen. Which will give you different field types for the extra field you're wanting to add to the entity.

* After you're done with configuring the extra field. Click save.

* Depending if you selected `Form` or `View` for the display type. This setting dictates if the extra field will be displayed either at `/admin/structure/types/manage/page/form-display` or `/admin/structure/types/manage/page/display`.

* On the entity display you'll be able to adjust the render position. If you make any changes, be sure to save the display configurations.
