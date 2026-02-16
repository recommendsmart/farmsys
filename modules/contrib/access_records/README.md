# Access Records

**Manage your access system with customizable content entities.**

https://www.drupal.org/project/access_records

## 0. Contents

- 1. Introduction
- 2. Requirements
- 3. Installation
- 4. Usage
- 5. Maintainers
- 6. Support and contribute

## 1. Introduction

With access records, you have manageable content entities that may give an
answer to complex access rule situations. The abstract form of the question that
may be answered is:

*"Who is allowed to do what regarding what (and why)?"*

**Who?**
A user having a certain flag (ID, role, arbitrary string, reference etc.)

**Do what?**
View, update, delete (can be extended and modified via hooks)

**Regarding what?**
Content having a certain flag (ID, arbitrary string, reference)

**Why?**
Basically, an access record **is** a reason by itself why someone may have
access to something. As an optional addendum, you could add some more fields
to access records that describe what's going on. It is recommended to prefix
such fields that belong to access records with "ar_", for example
"field_ar_description". Fields with such a prefix won't be included when looking
for matching fields between subjects and targets.

**Access records work like the following:**
An access record refers to one or multiple subjects of a specific type (that is
mostly a user / User entity) and to one or multiple targets (that is a content
entity type defined by the according type of access record, for example node /
Content). One access record holds a certain set of fields, that is being used
to match up based on the approach "having at least one of the values".

A lookup is being performed for having a matching field based on the machine
name of the field. When such a match is given, then a lookup is being performed
whether at least one field value is contained both in the access record and
in the subject or target.

When creating a new type for access records, two fields are being automatically
created. One is a subject ID field that mostly hold a user ID. The other one
is a target ID field that holds an entity ID of a target, which can only be
of one specific type as defined by the access record type. Both fields are
completely optional and may be removed if they are of no use for that type of
access records. You may add further fields, whose machine names may match with
the name of a machine name of subject (i.e. mostly a user) and/or target.

You can distinguish that a certain field is only meant to be matched up for the
subject by having a field name prefix "field_subject_" or "subject_". The same
goes for targets, whereas a field name prefix would be "field_target_" or
"target_" accordingly. When skipping that prefix, a machine name could match
up for both subject and target. For example, "uid" is the ID field of a User
entity, but it's also the owner field of a node. If "field_uid" or "uid" would
be defined for an access record type, then this would match up for both
mentioned fields. If you want to make sure a certain field is never taken as
matching candidate, you may prefix such field with "ar_", e.g. "field_ar_text".

## 2. Requirements

This module builds on top of contrib Entity API, which provides the mechanic
to extend the entity system by a query access mechanic on database level.
Therefore, the contrib Entity API (https://www.drupal.org/project/entity) is
required to be additionally installed besides Drupal core.

## 3. Installation

Install the module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

## 4. Usage

Once installed, you can create access record types at
/admin/structure/access-record. To manage fields of access records through a
user interface, make sure to have the core's Field UI module installed.

You should also take a look at the permissions page at /admin/people/permissions
and make sure whether the configured permissions are properly set.

## 5. Maintainers

* Maximilian Haupt (mxh) - https://www.drupal.org/u/mxh

## 6. Support and contribute

To submit bug reports and feature suggestions, or to track changes visit:
https://www.drupal.org/project/issues/access_records

You can also use this issue queue for contributing, either by submitting ideas,
or new features and mostly welcome - patches and tests.
