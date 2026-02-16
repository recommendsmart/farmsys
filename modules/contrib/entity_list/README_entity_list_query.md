EntityListQuery
-----------------

#### If you want to customize the EntityListQuery :

`entity_list` define the plugin type `EntityListQuery` to manage the query.

You can extend `DefaultEntityListQuery` or `EntityListQueryBase` plugin to
make your own.

Example of extending the `DefaultEntityListQuery`:


```php
<?php

namespace Drupal\mymodule\Plugin\EntityListQuery;

/**
 * Class MyEntityListQuery.
 *
 * Use a Drupal\Core\Entity\Query\QueryInterface implementation by default.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListQuery(
 *   id = "my_entity_list_query",
 *   label = @Translation("My Entity List Query")
 * )
 */
class MyEnityListQuery extends DefaultEntityListQuery {

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    parent::buildQuery();
  }

}

```
