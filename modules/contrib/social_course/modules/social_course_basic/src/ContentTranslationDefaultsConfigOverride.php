<?php

namespace Drupal\social_course_basic;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides content translation defaults for the Social Course Basic module.
 *
 * @package Drupal\social_course_basic
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ContentTranslationDefaultsConfigOverride object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    $is_content_translations_enabled = $this->moduleHandler
      ->moduleExists('social_content_translation');

    if ($is_content_translations_enabled) {
      // Translations for "Course Basic" group type.
      $config_name = 'language.content_settings.group.course_basic';
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'content_translation' => [
              'enabled' => TRUE,
            ],
          ],
        ];
      }
      $config_names = [
        'core.base_field_override.group.course_basic.label',
        'core.base_field_override.group.course_basic.menu_link',
        'core.base_field_override.group.course_basic.path',
        'core.base_field_override.group.course_basic.uid',
        'core.base_field_override.group.course_basic.status',
      ];
      foreach ($config_names as $config_name) {
        if (in_array($config_name, $names)) {
          $overrides[$config_name] = [
            'translatable' => TRUE,
          ];
        }
      }
      $config_name = 'field.field.group.course_basic.field_group_image';
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'content_translation' => [
              'translation_sync' => [
                'file' => 'file',
                'alt' => '0',
                'title' => '0',
              ],
            ],
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'social_course_basic.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}
