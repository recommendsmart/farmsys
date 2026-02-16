<?php

namespace Drupal\field_bundle\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\field_bundle\FieldBundleConfigInterface;

/**
 * Defines the field bundle config entity.
 *
 * @ConfigEntityType(
 *   id = "field_bundle_config",
 *   label = @Translation("Field bundle config"),
 *   label_collection = @Translation("Field bundle configurations"),
 *   bundle_label = @Translation("Field bundle config"),
 *   label_singular = @Translation("field bundle config"),
 *   label_plural = @Translation("field bundle configurations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field bundle config",
 *     plural = "@count field bundle configurations",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\field_bundle\Form\FieldBundleConfigForm",
 *       "edit" = "Drupal\field_bundle\Form\FieldBundleConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\field_bundle\FieldBundleConfigListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer field_bundle_config",
 *   bundle_of = "field_bundle",
 *   config_prefix = "config",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/field-bundle/add",
 *     "edit-form" = "/admin/structure/field-bundle/manage/{field_bundle_config}",
 *     "delete-form" = "/admin/structure/field-bundle/manage/{field_bundle_config}/delete",
 *     "collection" = "/admin/structure/field-bundle"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "uuid",
 *     "status",
 *     "new_revision",
 *     "label_pattern",
 *   }
 * )
 */
class FieldBundleConfig extends ConfigEntityBundleBase implements FieldBundleConfigInterface {

  /**
   * The machine name of this field bundle config.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the field bundle config.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this field bundle config.
   *
   * @var string
   */
  protected $description;

  /**
   * Whether field bundles should be published by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Whether a new revision should be created by default.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * A pattern to use for creating the label of the field bundle.
   *
   * @var string
   */
  protected $label_pattern = '[bundle:string-representation]';

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
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
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelPattern() {
    return $this->label_pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelPattern($pattern) {
    $this->label_pattern = $pattern;
  }

}
