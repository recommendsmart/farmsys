[![CircleCI](https://circleci.com/gh/dcycle/entity_inherit.svg?style=svg)](https://circleci.com/gh/dcycle/entity_inherit)

Entity Inherit
=====

Allows entities (say, a "School" entity) to inherit certain fields (say, the "Covid-19 policy" field) from another entity.

Fields' contents are evaluated every time a node is saved, so you can disable this module any time without losing data.

How it works
-----

You tell Entity Inherit, in /admin/config/entity_inherit which fields are used to define an entity's parent. For example you might create an entity reference field named `field_entity_inherit_parent` for this purpose.

Then, every time any entity is modified:

### If other entities inherit from it, and have the same fields, and the child's field has the same values as the parent field before it was changed, the "child" entities' fields are updated to reflect the parent entity's new value.

For example,

* node 2 has field field_whatever, value "hello"
* node 2 has field_entity_inherit_parent, value "1"
* node 1 has field field_whatever, value "hello"
* node 1 is modified so that field_whatever changes from "hello" to "hi"
* node 2's value for field_whatever changes to "hi".

### If an entity has a newly added value for field_entity_inherit_parent, all empty fields which exist in the parent, and are empty, are modified to contain the parent data.

for example,

* node 1 has field field_whatever, value "hello"
* node 2 has field field_whatever, which is empty.
* node 2 is modified so that its field_entity_inherit_parent contains a reference to node 1.
* node 2's value for field_whatever changes to "hello".

Raison d'être
-----

The idea is to be able to change multiple nodes' values easily without resorting to changing the data model.

Extending this module
-----

You can extend this module using Drupal's plugin system. Please see included plugins for examples.

Note about permissions
-----

This module does not check for access or permissions: if a user has the permission to change a parent node, changes will propagate to child nodes whether or not the current user has access to change the child nodes. Similarly if a user edits a child node and modifies its parent node, content from that parent node will propage to child nodes whether or not the user has access to view the parent node. It is up to the site builder to set up the "parent" field(s) to prevent undue access.

Note about infinite loops
-----

This module will not check for infinite loops. For example, if entity A inherits from entity B which inherits from entity A, it will result in errors. Site builders are responsible for avoiding such scenarios.

Local development
-----

If you install Docker on your computer:

* you can set up a complete local development workspace by downloading this codebase and running `./scripts/deploy.sh` for Drupal 9 development, or `./scripts/deploys.sh 8` for Drupoal 8 development. You do not need a separate Drupal instance or database for local development, only Docker. `./scripts/uli.sh` will provide you with a login link to your environment.
* you can destroy your local environment by running `./scripts/destroy.sh`.
* you can run all tests by running `./scripts/ci.sh`; please make sure all tests before submitting a patch.

Automated testing
-----

This module's main page is on [Drupal.org](http://drupal.org/project/entity_inherit); a mirror is kept on [GitHub](http://github.com/dcycle/entity_inherit).

Unit tests are performed on Drupal.org's infrastructure and in GitHub using CircleCI. Linting is performed on GitHub using CircleCI and Drupal.org. For details please see  [Start unit testing your Drupal and other PHP code today, October 16, 2019, Dcycle Blog](https://blog.dcycle.com/blog/2019-10-16/unit-testing/).

* [Test results on Drupal.org's testing infrastructure](https://www.drupal.org/project/entity_inherit)
* [Test results on CircleCI](https://circleci.com/gh/dcycle/entity_inherit)

To run automated tests locally, install Docker and type:

    ./scripts/ci.sh

Alternatives, similar or related modules
-----

* An alternative to this, especially for new sites, would be to keep data in a single place and use entity references to access that data (via views or database queries); however changing the data model for sites with thousands of nodes might be unweildy, making entity_inherit an option.
* [Template Entities](https://www.drupal.org/project/template_entities) is different from Entity Inherit in that it does not update entities when the template is updated.
* [Field Inheritance](https://www.drupal.org/project/field_inheritance) is different from Entity Inherit in that it requires patching view to [support computed bundle fields](https://www.drupal.org/project/drupal/issues/2981047). (Entity Inherit changes entities when an entity or its template are modified, thus not requiring any change to core.)


Drupal 9 readiness
-----

This project is certified Drupal 9 ready.
