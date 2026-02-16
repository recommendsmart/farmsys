# Field Bundle

**An un-opinionated entity type for creating arbitrary bundles of fields.**

https://www.drupal.org/project/field_bundle

## 0. Contents

- 1. Introduction
- 2. Requirements
- 3. Installation
- 4. Usage
- 5. Maintainers
- 6. Support

## 1. Introduction

The Field Bundle module adds a new content entity type that enables site
builders to create bundles of fields.

The purpose of this entity type might be confused with the purpose of what the
Paragraphs module does already solve. One key difference to Paragraphs is, that
field bundles are not opinionated about a "parent" (or "host" in terms of the D7
Field Collection module).

Field bundles may be referenced or embedded within other entities, e.g. by using
Inline Entity Form (https://www.drupal.org/project/inline_entity_form). In that
perspective, a field bundle is similar to the core concept of Media entities,
which are also usually not aware of a "parent" relationship.

A field bundle might be suitable for anything that does not logically fit into
the concept of a node, paragraph or media entity, but where it makes sense to
bundle fields together. Think of a generic entity type for standalone objects.

One might argue that the Entity Construction Kit (ECK) module already solves
what the Field Bundle module aims to solve. This argument is valid especially
for more advanced cases, e.g. where it makes sense to define a standalone entity
type that holds its own bundles and access rules. ECK or custom module
development should be considered if you need more than a generic entity type for
flat-managed field bundles.

Field bundles optionally support revisioning and also work with the Entity
Reference Revisions (https://www.drupal.org/project/entity_reference_revisions)
module.

## 2. Requirements

This module requires no modules outside of Drupal core.

The contrib Entity API (https://www.drupal.org/project/entity) can be installed
to make automatically use of its query access handler and extended views data
integration.

## 3. Installation

Install the module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

## 4. Usage

Once installed, you can create field bundle configurations at
/admin/structure/field-bundles. To manage fields of field bundles through a
user interface, make sure to have the core Field UI module installed.

You should also take a look at the permissions page at /admin/people/permissions
and make sure whether the configured permissions are properly set.

### 4.1 Why do field bundles not have a canonical URL?

Field bundles are primarily designed to be usable as "second-class" entities
besides nodes. They may be managed via Views lists and do provide an edit form,
plus they come with a translation and revision UI if needed. The usage of field
bundles can be considered for "embedded" or "inline" grouped content. If you
want to have canonical URLs for field bundles, just install the
`field_bundle_canonical` sub-module, which is already included in
this module.

## 5. Maintainers

* Maximilian Haupt (mxh) - https://www.drupal.org/u/mxh

## 6. Support

To submit bug reports and feature suggestions, or to track changes visit:
https://www.drupal.org/project/issues/field_bundle
