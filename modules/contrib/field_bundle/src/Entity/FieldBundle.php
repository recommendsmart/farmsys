<?php

namespace Drupal\field_bundle\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\FieldConfigInterface;
use Drupal\field_bundle\FieldBundleInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the field bundle content entity.
 *
 * @ContentEntityType(
 *   id = "field_bundle",
 *   label = @Translation("Field bundle"),
 *   label_collection = @Translation("Field bundle items"),
 *   bundle_label = @Translation("Field bundle config"),
 *   label_singular = @Translation("field bundle item"),
 *   label_plural = @Translation("field bundle items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field bundle item",
 *     plural = "@count field bundle items",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\field_bundle\FieldBundleListBuilder",
 *     "access" = "Drupal\field_bundle\FieldBundleAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\field_bundle\Form\FieldBundleForm",
 *       "edit" = "Drupal\field_bundle\Form\FieldBundleForm",
 *       "delete" = "Drupal\field_bundle\Form\FieldBundleDeleteForm",
 *       "default" = "Drupal\field_bundle\Form\FieldBundleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "field_bundle",
 *   data_table = "field_bundle_data",
 *   revision_table = "field_bundle_revision",
 *   revision_data_table = "field_bundle_revision_data",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer field_bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "config",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "owner" = "uid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/field-bundle/add/{field_bundle_config}",
 *     "add-page" = "/field-bundle/add",
 *     "edit-form" = "/field-bundle/{field_bundle}/edit",
 *     "delete-form" = "/field-bundle/{field_bundle}/delete",
 *     "collection" = "/admin/content/field-bundle"
 *   },
 *   bundle_entity_type = "field_bundle_config",
 *   field_ui_base_route = "entity.field_bundle_config.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   token_type = "bundle"
 * )
 */
class FieldBundle extends EditorialContentEntityBase implements FieldBundleInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $time = \Drupal::time()->getCurrentTime();
    $values += [
      'created' => $time,
      'changed' => $time,
      'uid' => static::getDefaultEntityOwner(),
    ];
    if (!isset($values['status'])) {
      /** @var \Drupal\field_bundle\FieldBundleConfigInterface $field_bundle_config */
      if (isset($values['config']) && ($field_bundle_config = \Drupal::entityTypeManager()->getStorage('field_bundle_config')->load($values['config']))) {
        $values['status'] = $field_bundle_config->getStatus();
      }
      else {
        $values['status'] = FALSE;
      }
    }
    if (isset($values['label']) && $values['label'] !== '') {
      // Disable the label pattern when a label is already there.
      $values['label_pattern'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the field bundle
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    $this->applyLabelPattern();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringRepresentation() {
    $string = '';
    \Drupal::moduleHandler()->invokeAllWith('field_bundle_get_string_representation', function (callable $hook, string $module) use (&$string) {
      $string = $hook($this, $string);
    });

    if (trim($string) === '') {
      $string = $this->generateFallbackStringRepresentation();
    }

    if (mb_strlen($string) > 255) {
      $string = Unicode::truncate($string, 255, TRUE, TRUE, 20);
    }

    return $string;
  }

  /**
   * Implements the magic __toString() method.
   *
   * When a string representation is explicitly needed, consider directly using
   * ::getStringRepresentation() instead.
   */
  public function __toString() {
    return $this->getStringRepresentation();
  }

  /**
   * {@inheritdoc}
   */
  public function applyLabelPattern() {
    if (isset($this->label_pattern)) {
      $label_pattern = $this->hasField('label_pattern') ? $this->get('label_pattern')->getString() : $this->label_pattern;
    }
    elseif ($config_id = $this->bundle()) {
      /** @var \Drupal\field_bundle\FieldBundleConfigInterface $config */
      if ($config = \Drupal::entityTypeManager()->getStorage('field_bundle_config')->load($config_id)) {
        $label_pattern = $config->getLabelPattern();
      }
    }
    if (!empty($label_pattern)) {
      $string = (string) \Drupal::token()->replace($label_pattern, ['bundle' => $this], [
        'langcode' => $this->language()->getId(),
        'clear' => TRUE,
      ]);
      if (mb_strlen($string) > 255) {
        $string = Unicode::truncate($string, 255, TRUE, TRUE, 20);
      }
      $this->label->value = $string;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['uid']
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the field bundle author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the field bundle was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the field bundle was last edited.'));

    $fields['status']
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Fallback method for generating a string representation.
   *
   * @see ::getStringRepresentation()
   *
   * @return string
   *   The fallback value for the string representation.
   */
  protected function generateFallbackStringRepresentation() {
    $components = \Drupal::service('entity_display.repository')->getFormDisplay('field_bundle', $this->bundle())->getComponents();

    // The label is available in the form, thus the user is supposed to enter
    // a value for it. For this case, use the label directly and return it.
    if (!empty($components['label'])) {
      return $this->label();
    }

    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $values = [];

    foreach (array_keys($components) as $field_name) {
      // Components can be extra fields, check if the field really exists.
      if (!$this->hasField($field_name)) {
        continue;
      }
      $field_definition = $this->getFieldDefinition($field_name);

      // Only take care for accessible string fields.
      if (!($field_definition instanceof FieldConfigInterface) || $field_definition->getType() !== 'string' || !$this->get($field_name)->access('view')) {
        continue;
      }

      if ($this->get($field_name)->isEmpty()) {
        continue;
      }

      foreach ($this->get($field_name) as $field_item) {
        $values[] = $field_item->value;
      }

      // Stop after two value items were received.
      if (count($values) > 2) {
        return implode(' ', array_slice($values, 0, 2)) . '...';
      }
    }

    return implode(' ', $values);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if (!$this->hasLinkTemplate($rel)) {
      $args = func_get_args();
      if (empty($args) || empty(reset($args))) {
        // For any caller that does not explicitly require a canonical url,
        // offer the edit form url.
        $rel = 'edit-form';
      }
    }
    return parent::toUrl($rel, $options);
  }

}
