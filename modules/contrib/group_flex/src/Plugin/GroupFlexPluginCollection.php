<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of group flex plugins.
 */
class GroupFlexPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID): int {
    $aObject = $this->get($aID);
    $bObject = $this->get($bID);

    return $aObject->getWeight() < $bObject->getWeight() ? -1 : 1;
  }

}
