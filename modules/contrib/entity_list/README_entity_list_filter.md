EntityListFilter
----------------

#### Create your custom EntityListFilter plugin :

`entity_list` define the plugin type `EntityListQuery` to manage multiple filters.

You can extend `EntityListFilterBase` or other default filter plugin to make your own.

Example of extending the `EntityListFilterBase`:

```php
<?php

namespace Drupal\mymodule\Plugin\EntityListFilter;

/**
 * Class MyEntityListFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListFilter(
 *   id = "my_entity_list_filter",
 *   label = @Translation("My Entity List Filter"),
 *   content_type = {},
 *   entity_type = {},
 * )
 */
class MyEntityListFilter extends EntityListFilterBase {

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    return parent::buildFilter($parameters, $entity_list);
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    return parent::setFields($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    return parent::configurationFilter($default_value, $entity_list);
  }

}

```
