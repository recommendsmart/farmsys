<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form for deleting a Flow configuration.
 */
class FlowDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getRedirectUrl();
  }

  /**
   * Returns the URL where the user should be redirected after deletion.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    /** @var \Drupal\flow\Entity\FlowInterface $flow */
    $flow = $this->getEntity();
    $target_type = \Drupal::entityTypeManager()->getDefinition($flow->getTargetEntityTypeId());
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';

    return Url::fromRoute("entity.flow.{$flow->getTargetEntityTypeId()}.default", [
      'entity_type_id' => $target_type->id(),
      $bundle_type_id => $flow->getTargetBundle(),
    ]);
  }

}
