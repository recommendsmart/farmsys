<?php

namespace Drupal\node_singles\Service;

use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * An interface for the node singles service.
 */
interface NodeSinglesInterface {

  /**
   * Checks whether a single node exists for this node type.
   *
   * If missing, it will create one.
   */
  public function checkSingle(NodeTypeInterface $type): void;

  /**
   * Returns a loaded single node by node type.
   */
  public function getSingle(NodeTypeInterface $type, ?string $langcode = NULL): ?NodeInterface;

  /**
   * Returns a loaded single node by node type ID.
   */
  public function getSingleByBundle(string $bundle, ?string $langcode = NULL): ?NodeInterface;

  /**
   * Check whether a node type is single or not.
   */
  public function isSingle(NodeTypeInterface $type): bool;

  /**
   * Get all single content types.
   */
  public function getAllSingles(): array;

}
