<?php

namespace Drupal\widget_type\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\widget_type\WidgetRegistrySourceInterface;

/**
 * Defines the widget registry source entity type.
 *
 * @ConfigEntityType(
 *   id = "widget_registry_source",
 *   label = @Translation("Widget Registry Source"),
 *   label_collection = @Translation("Widget Registry Sources"),
 *   label_singular = @Translation("widget registry source"),
 *   label_plural = @Translation("widget registry sources"),
 *   label_count = @PluralTranslation(
 *     singular = "@count widget registry source",
 *     plural = "@count widget registry sources",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\widget_type\WidgetRegistrySourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\widget_type\Form\WidgetRegistrySourceForm",
 *       "edit" = "Drupal\widget_type\Form\WidgetRegistrySourceForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "widget_registry_source",
 *   admin_permission = "administer widget registry sources",
 *   links = {
 *     "collection" = "/admin/content/interactive-components/widget-registry-source",
 *     "add-form" = "/admin/content/interactive-components/widget-registry-source/add",
 *     "edit-form" = "/admin/content/interactive-components/widget-registry-source/{widget_registry_source}",
 *     "delete-form" = "/admin/content/interactive-components/widget-registry-source/{widget_registry_source}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "endpoint"
 *   }
 * )
 */
class WidgetRegistrySource extends ConfigEntityBase implements WidgetRegistrySourceInterface {

  /**
   * The widget registry source ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The widget registry source label.
   *
   * @var string
   */
  protected $label;

  /**
   * The widget registry source status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The widget_registry_source description.
   *
   * @var string
   */
  protected $description;

  /**
   * The widget_registry_source endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * {@inheritdoc}
   */
  public function calculateColors(): array {
    $seed = $this->id();
    $color = substr(hash('sha512', (string) $seed), 0, 6);
    $red = dechex(round(hexdec(substr($color, 0, 2)) / 2));
    $green = dechex(round(hexdec(substr($color, 2, 2)) / 2));
    $blue = dechex(round(hexdec(substr($color, 4, 2)) / 2));
    $color = sprintf('%02d%02d%02d', $red, $green, $blue);
    $complementary = 0xFFFFFF - hexdec($color);
    $complementary_color = dechex($complementary);
    return [$color, $complementary_color];
  }

}
