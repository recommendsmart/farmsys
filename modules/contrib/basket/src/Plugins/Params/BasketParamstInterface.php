<?php

namespace Drupal\basket\Plugins\Params;

/**
 * Provides an interface for all Basket Params plugins.
 */
interface BasketParamstInterface {

  /**
   * Form with parameters.
   */
  public function getParamsForm(&$form, $form_state, $entity, $ajax);

  /**
   * Interpretation of parameters.
   */
  public function getDefinitionParams(&$element, $params, $isInline = FALSE);

  /**
   * Validation of parameters when adding / updating an order item.
   */
  public function validParams(&$response, &$isValid, $post);

}
