<?php

namespace Drupal\taxonomy_term_locks\Service;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

/**
 * Class TaxonomyTermLockService.
 *
 * Service to perform actions for the taxonomy term lock.
 *
 * @package Drupal\taxonomy_term_locks\Service
 */
class TaxonomyTermLocksService {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Entity type manager from core.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * UWAnalytics default constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database entity.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user.
   */
  public function __construct(
    Connection $database,
    CurrentRouteMatch $routeMatch,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxy $currentUser
  ) {

    $this->database = $database;
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Get the vocab id from the route.
   *
   * @return string|null
   *   The vocab id.
   */
  public function getVidFromRoute(): ?string {

    // Get the parameters from the route.
    $parameters = $this->routeMatch->getParameters();

    // If there is a vocab in the parameters, we are on an add page,
    // so we need to get vid from that vocab parameter.
    // If there is no vocab in parameters, we are on an edit or delete
    // page, so get the vid from the term parameter.
    if ($vocab = $parameters->get('taxonomy_vocabulary')) {
      return $vocab->get('vid');
    }
    elseif ($term = $parameters->get('taxonomy_term')) {
      return $term->get('vid')->target_id;
    }

    return NULL;
  }

  /**
   * Get all the terms from the vocab id.
   *
   * @param string $vid
   *   The vocab id.
   *
   * @return array|null
   *   Array of terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTermsFromVid(string $vid): ?array {

    // Return the terms of the vocab.
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
  }

  /**
   * Return the permission for setting the term lock.
   *
   * @return bool
   *   If user has permission to set taxonomy term lock.
   */
  public function getSetTermPermission(): bool {

    // Return if user has permission to set taxonomy term lock.
    return $this->currentUser->hasPermission('set taxonomy term lock');
  }

  /**
   * Check if there is a lock on a term.
   *
   * @param int $tid
   *   The term id.
   *
   * @return bool
   *   If there is a lock on the term.
   */
  public function checkLock(int $tid): bool {

    // Query to get a lock.
    $query = $this->database
      ->select('taxonomy_term_locks', 'ttl')
      ->fields('ttl', ['tid'])
      ->condition('tid', $tid);

    // Get the results.
    $results = $query->execute()->fetchAll();

    return (bool) $results;
  }

  /**
   * Function to check if there is a lock on a term, from a route.
   *
   * @return bool
   *   TRUE if admin page, FALSE otherwise.
   */
  public function checkLockFromRoute(): bool {

    // Get the parameters from the route.
    $parameters = $this->routeMatch->getParameters();

    // If there is a term in the route, then return if there
    // is a lock on this term.
    if ($term = $parameters->get('taxonomy_term')) {

      // Return if there is a lock on this term.
      return $this->checkLock($term->tid->value);
    }

    // Return by default that there is no lock on this term.
    return FALSE;
  }

  /**
   * Insert a new lock.
   *
   * @param int $tid
   *   The term id.
   */
  public function insertLock(int $tid): void {

    // Query to insert a lock.
    $this->database
      ->upsert('taxonomy_term_locks')
      ->fields([
        'tid' => $tid,
      ])
      ->key('tid')
      ->execute();
  }

  /**
   * Delete a lock.
   *
   * @param int $tid
   *   The term id.
   */
  public function deleteLock(int $tid): void {

    // Query to delete a lock.
    $this->database
      ->delete('taxonomy_term_locks')
      ->condition('tid', $tid)
      ->execute();
  }

  /**
   * Check if user can bypass taxonomy term lock.
   *
   * @return bool
   *   Flag if user can bypass taxonomy term lock.
   */
  public function getBypassTermPermission(): bool {

    // Return if user has permission to bypass taxonomy term lock.
    return $this->currentUser->hasPermission('bypass taxonomy term lock');
  }

  /**
   * Check user access to the edit/delete term page.
   *
   * This is done in order to prevent a user who knows
   * the URL to taxonomy term edit/delete poge from
   * loading that page, by throwing a 403 error.
   */
  public function blockUnauthorizedAccess(): void {

    // If there is a lock and the user does not have the bypass,
    // throw a 403.
    if ($this->checkLockFromRoute() && !$this->getBypassTermPermission()) {

      // Throw a 403.
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Function to insert term locks in bulk.
   *
   * @param array $tids
   *   Array of term ids.
   */
  public function bulkInsertLocks(array $tids): void {

    // Setup the upsert query on tid.
    $query = $this->database
      ->upsert('taxonomy_term_locks')
      ->fields(['tid']);

    // Step through each or the tids, and add to values
    // for the query.
    foreach ($tids as $tid) {
      $query->values(['tid' => $tid]);
    }

    // Add the key for the upsert and execute query.
    $query->key('tid');
    $query->execute();
  }

  /**
   * Function to delete term locks in bulk.
   *
   * @param array $tids
   *   Array of term ids.
   */
  public function bulkDeleteLocks(array $tids): void {

    // Query to delete the locks.
    $this->database
      ->delete('taxonomy_term_locks')
      ->condition('tid', $tids, 'IN')
      ->execute();
  }

}
