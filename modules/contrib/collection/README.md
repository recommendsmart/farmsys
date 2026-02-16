# Collection

## Introduction

The Collection module allows users to organize content or configuration entities in arbitrary collections.

Examples include _blogs_ (collections of posts), _periodicals_ (collections of articles or stories), _subsites_ (collections of pages, along with a related menu, for a discreet section of the site) and _personal collections_ of content or configuration of interest to individual users.

## Features

- Fieldable, revisionable _Collection entities_, which can be used as content pages like nodes.
- _Collection types_, similar to content types.
- Fieldable _Collection item entities_, used to link content (or configuration) entities (e.g. nodes) to Collections.
- _Collection item types_.
- Ability to place content in multiple Collections, while designating one Collection as the canonical (or primary) Collection.
- Ordering of items in Collections.
- Collection item listings as Paragraphs (via the included, experimental Collection Listing module).
- Multiple extension points (events and hooks) allowing developers to implement the specific requirements of their use cases.

## Requirements

This module requires the following modules:

- dynamic_entity_reference (https://www.drupal.org/project/dynamic_entity_reference)
- drupal:path
- key_value_field (https://www.drupal.org/project/key_value_field)
- A core patch from https://www.drupal.org/project/drupal/issues/2901412

## Optional modules

- Inline entity form (https://www.drupal.org/project/inline_entity_form):  
When enabled, Collection item forms will be embedded in content entity forms (e.g. node edit form) when that content is in a Collection.
- Paragraphs (https://www.drupal.org/project/paragraphs):  
When enabled along with the included Collection Listing module, users can create listings of the items in a given Collection for placement on another content entity.
- Pathauto (https://www.drupal.org/project/pathauto):  
When enabled along with the included Collection Pathauto module, the URL alias of the Collection will be prepended to the URL alias of the content items with the Collection.

## Installation

Install as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.

## Configuration

Create one or more _Collection types_ at Administration » Structure » Collection types. Some examples:

- A `blog` collection type to represent multiple blogs.
- A `personal` collection type to allow individual users to create arbitrary Collections in which to place content they wish to track.

Configure the user permissions at Administration » People » Permissions:

- `Administer collections`  
  Users with this permission can create and modify _Collection types_ and _Collection item types_.

- `Access collection overview`  
  Users with this permission can access the collection overview page.

- `View`, `Create`, `Edit`, and `Delete` permissions  
  Adjust these permissions as required. Some examples:

    - For a `blog` collection type, you might allow anonymous users the `Blog: View collection` permission and give the `Edit own collections` permission to, for example, a `Blog Editor` role.

    - For a `personal` collection type, you could give the following permissions to authenticated users: `Personal: Create new collection`, `Delete own collections`, `Edit own collections`, and `View own collections`.

Note that edit and delete permissions are not per collection type, but based on individual Collection _ownership_. Unlike many other content entities, Collections can have multiple owners for finer-grain control.

Optionally, create new _Collection item_ types at Administration » Structure » Collection item types. Collection items are fieldable and can be edited using the 'Edit collection item' operation on Collection item listings (the _Items_ tab on Collections). If _Inline entity form_ is enabled, the Collection item entity form will be embedded on the content entity edit form (e.g. the node edit form). It will use the `mini` form mode for Collection items if configured for that Collection item type.

## Notes

### Automatically created Collection items

There are times when adding a content entity to a collection (for example adding an Article node to a Blog collection) when the collection item type must be deduced.

For example, a _Blog_ collection type might allow two collection item types: _Blog item_ and _Page item_. If both _Blog item_ and _Page item_ types allow _Article_ nodes, which one should be used?

Currently, for the 'Add new content' (`collection_new_node_form`) functionality, we pick the first collection item type that can handle the entity/bundle combo. In this example, _Blog item_ would be used since it's returned first alphabetically.

To override this default behavior, you can manually set the type by altering the form to call a `#submit` handler. For example:

```
function MYMODULE_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_id === 'collection_new_node_form') {
    if ($form_state->get('collection')->bundle() === 'special') {
      // Add a 'presubmit' handler to allow setting the collection item type to special.
      array_unshift($form['#submit'], 'MYMODULE_form_collection_new_node_form_presubmit');
    }
  }
}

function MYMODULE_form_collection_new_node_form_presubmit($form, FormStateInterface $form_state) {
  if ($form_state->getValue('bundle') === 'article') {
    $form_state->set('collection_item_type', 'special_item');
  }
}
```

## Similar modules

Collection has some similarities to the Group module (https://www.drupal.org/project/group), in that it uses Collection item entities as relation objects to join content/configuration to Collections. But Collection does not enable custom permissions and roles per Collection, and Collection allows users to place configuration entities, such as menus, into Collections.

## Roadmap

- Add tests.
- Add example module for `blog` use case.
- Customize allowed content and/or configuration entity types and bundles per collection item type. Research how node module allows base field overrides to be stored in separate config items. See https://drupal.stackexchange.com/questions/253257/how-to-easily-alter-an-entitys-base-field-definition-per-bundle
- Add a drag and drop UI for Collection item weights.
- Add a previous/next block, paragraph, and/or extra field to allow navigation of items in Collections according to their ordering by weight.
- Improve interface for adding items to collection (e.g. contextual links, custom block, node add/edit form).
- Implement exposed filters in the collection item listing. Investigate using Views as the listbuilder.
- Allow bulk operations on collection item lists.
- Fix Views integration.
- Improve collection permissions
  - Per collection type add/edit/delete
- Offer to remove collection items when deleting a collection
  - Or prevent deletion of collections with items
