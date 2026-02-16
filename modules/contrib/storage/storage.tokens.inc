<?php

/**
 * @file
 * Builds placeholder replacement tokens for "Storage entity"-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function storage_token_info() {
  $type = [
    'name' => t('Storage entities'),
    'description' => t('Tokens related to individual storage entities.'),
    'needs-data' => 'storage',
  ];

  $storage = [];

  // Core tokens for storage entities.
  $storage['id'] = [
    'name' => t("Storage ID"),
    'description' => t('The unique ID of the Storage entity.'),
  ];
  $storage['vid'] = [
    'name' => t("Storage revision ID"),
    'description' => t("The unique ID of the Storage entity's latest revision."),
  ];
  $storage['type'] = [
    'name' => t("Storage type"),
  ];
  $storage['type-name'] = [
    'name' => t("Storage type name"),
    'description' => t("The human-readable name of the storage type."),
  ];
  $storage['name'] = [
    'name' => t("Name"),
  ];
  $storage['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the Storage entity is written in.'),
  ];
  $storage['url'] = [
    'name' => t("URL"),
    'description' => t("The canonical URL of the Storage entity."),
  ];
  $storage['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the Storage entity's edit form."),
  ];

  // Chained tokens for storage entities.
  $storage['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $storage['changed'] = [
    'name' => t("Date changed"),
    'type' => 'date',
  ];
  $storage['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];

  // Miscellaneous tokens for storage entities.
  $storage['string-representation'] = [
    'name' => t("String representation"),
    'description' => t("A generic string representation of this Storage entity. Warning: This might expose undesired field content."),
  ];

  return [
    'types' => ['storage' => $type],
    'tokens' => ['storage' => $storage],
  ];
}

/**
 * Implements hook_tokens().
 */
function storage_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
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

  if ($type == 'storage' && !empty($data['storage'])) {
    /** @var \Drupal\storage\Entity\StorageInterface $storage */
    $storage = $data['storage'];
    if (isset($options['langcode']) && $storage->language()->getId() !== $options['langcode'] && $storage->hasTranslation($options['langcode'])) {
      $storage = $storage->getTranslation($options['langcode']);
    }
    $bubbleable_metadata->addCacheableDependency($storage);
    if ($config_id = $storage->bundle()) {
      if ($storage_type = \Drupal::entityTypeManager()->getStorage('storage_type')->load($config_id)) {
        $bubbleable_metadata->addCacheableDependency($storage_type);
      }
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $storage->id();
          break;

        case 'vid':
          $replacements[$original] = $storage->getRevisionId();
          break;

        case 'type':
          $replacements[$original] = $config_id;
          break;

        case 'type-name':
          $replacements[$original] = !empty($storage_type) ? t($storage_type->label()) : '';
          break;

        case 'name':
          $replacements[$original] = $storage->label();
          break;

        case 'langcode':
          $replacements[$original] = $storage->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $storage->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $storage->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($storage->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($storage->getChangedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'author':
          $account = $storage->getOwner() ?: User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'string-representation':
          $replacements[$original] = $storage->getStringRepresentation();
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $storage->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $storage->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $storage->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
