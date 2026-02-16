<?php

/**
 * @file
 * Hooks provided by the Entity Reference Tree module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the label of a taxonomy term node.
 *
 * Modules may implement this hook to control the label of a taxonomy tree node
 * in the tree. By default the $text is equal to the taxonomy term's name. This
 * hook allows data from other fields to be used, that can make the term search
 * easier.
 *
 * @param string $text
 *   The label that will be rendered. Defaults to the term's name.
 * @param $entity
 *   The term object. Note that this is NOT an \Drupal\taxonomy\TermInterface
 *	 object. The terms tree is constructed by the 
 *   \Drupal\taxonomy\TermStorageInterface::loadTree method where the 4th
 *   argument ($load_entities) is set to FALSE so that the entities are not loaded.
 *   The module that implements this hook can decide whether to load the entities
 *   or not.
 */
function hook_entity_reference_tree_create_term_node_alter(&$text, $entity) {
  // Example: If the term has no parents, prepend "Root: " before its label.
  if (empty($entity->parents[0])) {
  	$text = t('Root: ') . $text;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
