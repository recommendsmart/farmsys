<?php

namespace Drupal\config_terms\Entity;

use ArrayIterator;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Config term entity.
 *
 * @ConfigEntityType(
 *   id = "config_terms_term",
 *   label = @Translation("Config term"),
 *   handlers = {
 *     "list_builder" = "Drupal\config_terms\TermListBuilder",
 *     "storage" = "Drupal\config_terms\TermStorage",
 *     "form" = {
 *       "default" = "Drupal\config_terms\Form\TermForm",
 *       "delete" = "Drupal\config_terms\Form\TermDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\config_terms\TermHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\config_terms\TermAccessControlHandler"
 *   },
 *   config_prefix = "config_terms_term",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "vid" = "vid",
 *     "description" = "description",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "parents" = "parents"
 *
 *   },
 *   config_export = {
 *     "id",
 *     "vid",
 *     "label",
 *     "parents",
 *     "description",
 *     "weight",
 *   },
 *   links = {
 *     "delete-form" = "/config-terms/config-term/{config_terms_term}/delete",
 *     "edit-form" = "/config-terms/config-term/{config_terms_term}/edit"
 *   }
 * )
 */
class Term extends ConfigEntityBase implements TermInterface {

  /**
   * The machine name of the config term.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the config term.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the config term.
   *
   * @var string
   */
  protected $description;

  /**
   * The associated vocabulary machine name.
   *
   * @var string
   */
  protected $vid;

  /**
   * The Config term weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The parents of this term in format [id => term object].
   *
   * @var array
   */
  protected $parents = ['0'];

  /**
   * The maximum amount of steps to reach the top parent.
   *
   * @var int
   */
  protected $depth = 0;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getVid() {
    return $this->vid;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getParents() {
    return $this->parents;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth() {
    return $this->depth;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepth($depth) {
    $this->depth = (int) $depth;
    return $this;
  }

  /**
   * Define empty array iterator.
   *
   * Needed for kint() to work properly.
   */
  public function getIterator() {
    return new ArrayIterator([]);
  }

  /**
   * {@inheritdoc}
   */
  public function setParents(array $parents) {
    $this->parents = $parents;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\config_terms\TermStorageInterface $storage */
    parent::postDelete($storage, $entities);

    // See if any of the term's children are about to be become orphans.
    $orphans = [];
    foreach (array_keys($entities) as $tid) {
      if ($children = $storage->loadChildren($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = $storage->loadParents($child->id());
          if (empty($parents)) {
            $orphans[] = $child->id();
          }
          else {
            // @todo recalculate depth
            $parents = $child->getParents();
            unset($parents[$tid]);
            $child->setParents($parents);
            $child->save();
          }
        }
      }
    }

    if (!empty($orphans)) {
      $entities = $storage->loadMultiple($orphans);
      $storage->delete($entities);
    }
  }

}
