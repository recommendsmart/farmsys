<?php

namespace Drupal\widget_type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * {@inheritdoc}
 */
final class WidgetTypeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    if ($view_mode !== 'library') {
      return parent::view($entity, $view_mode, $langcode);
    }
    assert($entity instanceof WidgetTypeInterface);
    return [
      '#attached' => [
        'library' => [
          sprintf('widget_type/widget_type.%s.%s', $entity->getRemoteId(), $entity->id()),
        ],
      ],
    ];
  }

}
