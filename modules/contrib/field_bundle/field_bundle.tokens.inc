<?php

/**
 * @file
 * Builds placeholder replacement tokens for "field bundle"-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function field_bundle_token_info() {
  $type = [
    'name' => t('Field bundles'),
    'description' => t('Tokens related to individual field bundle items.'),
    'needs-data' => 'bundle',
  ];

  $field_bundle = [];

  // Core tokens for field bundles.
  $field_bundle['id'] = [
    'name' => t("Field bundle ID"),
    'description' => t('The unique ID of the field bundle item.'),
  ];
  $field_bundle['revision-id'] = [
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the field bundle's latest revision."),
  ];
  $field_bundle['config-id'] = [
    'name' => t("Field bundle config ID"),
  ];
  $field_bundle['config-label'] = [
    'name' => t("Field bundle config label"),
    'description' => t("The human-readable name of the field bundle config."),
  ];
  $field_bundle['label'] = [
    'name' => t("Label"),
  ];
  $field_bundle['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the field bundle is written in.'),
  ];
  $field_bundle['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the field bundle's edit form."),
  ];

  // Chained tokens for field bundles.
  $field_bundle['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $field_bundle['changed'] = [
    'name' => t("Date changed"),
    'type' => 'date',
  ];
  $field_bundle['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];

  // Miscellaneous tokens for field bundles.
  $field_bundle['string-representation'] = [
    'name' => t("String representation"),
    'description' => t("A generic string representation of this field bundle. Warning: This might expose undesired field content."),
  ];

  return [
    'types' => ['bundle' => $type],
    'tokens' => ['bundle' => $field_bundle],
  ];
}

/**
 * Implements hook_tokens().
 */
function field_bundle_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  /** @var \Drupal\field_bundle\FieldBundleInterface $field_bundle */
  $field_bundle = NULL;
  $allowed_keys = ['bundle', 'field_bundle', 'field-bundle'];
  foreach ($allowed_keys as $key) {
    if (isset($data[$key])) {
      $field_bundle = $data[$key];
      break;
    }
  }

  if (in_array($type, $allowed_keys) && $field_bundle) {
    if (isset($options['langcode']) && $field_bundle->language()->getId() !== $options['langcode'] && $field_bundle->hasTranslation($options['langcode'])) {
      $field_bundle = $field_bundle->getTranslation($options['langcode']);
    }
    $bubbleable_metadata->addCacheableDependency($field_bundle);
    if ($config_id = $field_bundle->bundle()) {
      if ($field_bundle_config = \Drupal::entityTypeManager()->getStorage('field_bundle_config')->load($config_id)) {
        $bubbleable_metadata->addCacheableDependency($field_bundle_config);
      }
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $field_bundle->id();
          break;

        case 'revision-id':
          $replacements[$original] = $field_bundle->getRevisionId();
          break;

        case 'config-id':
          $replacements[$original] = $config_id;
          break;

        case 'config-label':
          $replacements[$original] = !empty($field_bundle_config) ? t($field_bundle_config->label()) : '';
          break;

        case 'label':
          $replacements[$original] = $field_bundle->label();
          break;

        case 'langcode':
          $replacements[$original] = $field_bundle->language()->getId();
          break;

        case 'edit-url':
          $replacements[$original] = $field_bundle->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($field_bundle->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($field_bundle->getChangedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'author':
          $account = $field_bundle->getOwner() ?: User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'string-representation':
          $replacements[$original] = $field_bundle->getStringRepresentation();
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $field_bundle->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $field_bundle->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $field_bundle->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
