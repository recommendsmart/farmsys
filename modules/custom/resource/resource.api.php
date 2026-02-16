<?php

/**
 * @param \Drupal\resource\ResourceInterface $node
 * @param $op
 * @param \Drupal\Core\Session\AccountInterface $account
 */
function hook_resource_access(\Drupal\resource\ResourceInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  // Example.
}

/**
 * @param \Drupal\Core\Session\AccountInterface $account
 * @param array $context
 * @param null $entity_bundle
 */
function hook_resource_create_access(\Drupal\Core\Session\AccountInterface $account, $context = array(), $entity_bundle = NULL) {
  // Example.
}
