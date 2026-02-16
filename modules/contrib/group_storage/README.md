CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers

# INTRODUCTION

`Group Storage` - This module extends [Group](https://www.drupal.org/project/group) and [Storage Entities](https://www.drupal.org/project/storage) modules and allows user to add storage entites as content the group.

# REQUIREMENTS

This module requires the following modules:

* [Group](https://www.drupal.org/project/group), version 8.x-1.4
* [Storage Entities](https://www.drupal.org/project/storage), version 1.1.0

Additional, but optional:

* [Subgroup](https://www.drupal.org/project/subgroup)
  or [Subgroup (Graph)](https://www.drupal.org/project/ggroup)

# INSTALLATION

Install the `Group Storage` module as you would normally install a contributed
Drupal module.

```
drush en group_storage -y
```

* Visit for [further information](https://www.drupal.org/node/1897420).

# CONFIGURATION

## Note

> Multiple Storage Entities can be assigned to a group.
> Once a new storage entity is created, it can be installed in the group as content.

## Configuration steps

1) Create Storage Entity type `YourStorageEntityTypeName` with your desired fields

```
/admin/structure/storage_types/add
```

2) Install Group content type Group Storage `YourStorageEntityTypeName`

```
/admin/group/types/manage/[ YOUR_GROUP_TYPE_MACHINE_NAME ]/content
```

3) Provide permission `YourStorageEntityTypeName` to the group roles

```
/admin/group/types/manage/[ YOUR_GROUP_TYPE ]/permissions
```

4) Add new or existing storage entity items to the group

```
/group/[ GROUP ID ]/storage
```

MAINTAINERS
-----------

Supporting organization:

* TRENDKRAFT 
  * https://www.drupal.org/trendkraft