# Entity Version Workflows

This module provides additional functionality that allows to control version numbers through workflow state transitions.

## Usage

The module introduces extra configuration options that allow to set for each available workflow transition if any
of the version numbers will increase, decrease or stay the same.

## API

It is possible to flag entities to bypass the configuration and prevent the version number from being altered
by setting the custom property "entity_version_no_update" to TRUE.

```
$entity->entity_version_no_update = TRUE;
```

## Configuration
A version field needs to be selected in the entity version configuration at "admin/config/entity-version/settings" to
apply the transition changes for that specific entity bundle. If no field is selected, no changes to version fields will apply.
