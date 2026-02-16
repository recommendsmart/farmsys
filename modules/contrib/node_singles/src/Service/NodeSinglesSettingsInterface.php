<?php

namespace Drupal\node_singles\Service;

/**
 * An interface for the service providing settings for the Node Singles module.
 */
interface NodeSinglesSettingsInterface {

  /**
   * Gets the human-readable name of a single node.
   *
   * This label should be used to present a human-readable name of the
   * entity type.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The human-readable name of the entity type.
   */
  public function getLabel();

  /**
   * Gets the uppercase plural form of the name of a single node.
   *
   * This should return a human-readable version of the name that can refer
   * to all the entities of the given type, collectively. An example usage of
   * this is the page title of a page devoted to a collection of entities such
   * as "Workflows" (instead of "Workflow entities").
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The collection label.
   */
  public function getCollectionLabel();

  /**
   * Gets the indefinite singular form of the name of a single node.
   *
   * This should return the human-readable name for a single instance of
   * the entity type. For example: "opportunity" (with the plural as
   * "opportunities"), "child" (with the plural as "children"), or "content
   * item" (with the plural as "content items").
   *
   * Think of it as an "in a full sentence, this is what we call this" label. As
   * a consequence, the English version is lowercase.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The singular label.
   */
  public function getSingularLabel();

  /**
   * Gets the indefinite plural form of the name of a single node.
   *
   * This should return the human-readable name for more than one instance of
   * the entity type. For example: "opportunities" (with the singular as
   * "opportunity"), "children" (with the singular as "child"), or "content
   * items" (with the singular as "content item").
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The plural label.
   */
  public function getPluralLabel();

  /**
   * Gets the label's definite article form for use with counts of single nodes.
   *
   * This label should be used when the quantity of entities is provided. The
   * name should be returned in a form usable with a count of the
   * entities. For example: "1 opportunity", "5 opportunities", "1 child",
   * "6 children", "1 content item", "25 content items".
   *
   * @param int $count
   *   The item count to display if the plural form was requested.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The count label.
   */
  public function getCountLabel(int $count);

}
