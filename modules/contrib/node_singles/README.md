Node Singles
======================

> Singles are node types used for one-off pages that have unique content
  requirements

## Why?
- Singles are node types used for
  **one-off pages that have unique content requirements**,
  such as the homepage, an _About Us_ page, a _Contact Us_ page, etc.
- Unlike other node types, singles have **only one node** associated with them
- This concept was taken from [Craft CMS](https://docs.craftcms.com/v2/sections-and-entries.html#singles)

## Installation

This package requires PHP 7.1 and Drupal 8.8 or higher. It can be
installed using Composer:

```bash
 composer require drupal/node_singles
```

## How does it work?
When adding a new node type, check the _This is a content type with a
single entity._ checkbox under the _Singles_ tab to mark this node type as
a single.

A node of this type will automatically be created and you will not be able 
to create others.

Only users with the `administer node singles` permission will be able to
delete singles.

An overview with all singles is available at `/admin/content/singles` for 
all users with the `access node singles overview` permission. 

## Limitations
- The single overview menu link does not appear if the user does not have the
  _Access the Content overview page_ permission.
- Just like with other entities, deleting entity bundles though a config import
  without deleting the entities first results in an error. You can work around
  this by first calling `drush entity:delete node --bundle=<bundle>` or by using
  a module like [_Config Import - Delete Entities_](https://www.drupal.org/project/config_import_de). 

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.
