<?php

/**
 * @file
 * Builds placeholder replacement tokens for "access record"-related data.
 */

use Drupal\access_records\AccessRecordInterface;
use Drupal\access_records\Entity\AccessRecord;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function access_records_token_info() {
  $type = [
    'name' => t('Access records'),
    'description' => t('Tokens related to individual access record items.'),
    'needs-data' => 'access_record',
  ];

  $access_record = [];

  // Core tokens for access records.
  $access_record['ar_id'] = [
    'name' => t("Access record ID"),
    'description' => t('The unique ID of the access record item.'),
  ];
  $access_record['ar_vid'] = [
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the access record's latest revision."),
  ];
  $access_record['ar_type'] = [
    'name' => t("Machine name of the access record type"),
  ];
  $access_record['ar_type_label'] = [
    'name' => t("Human-readable label of the access record type."),
    'description' => t("The human-readable name of the access record type."),
  ];
  $access_record['ar_label'] = [
    'name' => t("Label"),
  ];
  $access_record['ar_subject_type'] = [
    'name' => t("Subject type"),
    'description' => t("The entity type ID of the subject type (mostly 'user')."),
  ];
  $access_record['ar_subjec_type_label'] = [
    'name' => t("Subject type (label)"),
    'description' => t("The human-readable label of the subject type (mostly 'User')."),
  ];
  $access_record['ar_target_type'] = [
    'name' => t("Target type"),
    'description' => t("The entity type ID of the target type."),
  ];
  $access_record['ar_target_type_label'] = [
    'name' => t("Target type (label)"),
    'description' => t("The human-readable label of the target type."),
  ];
  $access_record['ar_operation'] = [
    'name' => t("Allowed operations"),
  ];
  $access_record['ar_operation_label'] = [
    'name' => t("Allowed operations (label)"),
  ];
  $access_record['ar_langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the access record.'),
  ];
  $access_record['edit_url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the access record's edit form."),
  ];

  // Chained tokens for access records.
  $access_record['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $access_record['changed'] = [
    'name' => t("Date changed"),
    'type' => 'date',
  ];
  $access_record['owner'] = [
    'name' => t("Owner"),
    'type' => 'user',
  ];

  // Miscellaneous tokens for access records.
  $access_record['string_representation'] = [
    'name' => t("String representation"),
    'description' => t("A generic string representation of this access record. Warning: This might expose undesired field content."),
  ];

  return [
    'types' => ['access_record' => $type],
    'tokens' => ['access_record' => $access_record],
  ];
}

/**
 * Implements hook_tokens().
 */
function access_records_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type !== 'access_record' || empty($data['access_record']) || !($data['access_record'] instanceof AccessRecordInterface)) {
    return $replacements;
  }

  $token_service = \Drupal::token();

  /** @var \Drupal\acces_records\AccessRecordInterface $access_record */
  $access_record = $data['access_record'];

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  if (isset($options['langcode']) && $access_record->language()->getId() !== $options['langcode'] && $access_record->hasTranslation($options['langcode'])) {
    $access_record = $access_record->getTranslation($options['langcode']);
  }
  $bubbleable_metadata->addCacheableDependency($access_record);
  if ($type_id = $access_record->bundle()) {
    if ($access_record_type = \Drupal::entityTypeManager()->getStorage('access_record_type')->load($type_id)) {
      $bubbleable_metadata->addCacheableDependency($access_record_type);
    }
  }

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'ar_id':
        $replacements[$original] = $access_record->id();
        break;

      case 'ar_vid':
        $replacements[$original] = $access_record->getRevisionId();
        break;

      case 'ar_type':
        $replacements[$original] = $type_id;
        break;

      case 'ar_type_label':
        $replacements[$original] = !empty($access_record_type) ? t($access_record_type->label()) : '';
        break;

      case 'ar_label':
        $replacements[$original] = $access_record->label();
        break;

      case 'ar_subject_type':
        $replacements[$original] = $access_record->ar_subject_type->value;
        break;

      case 'ar_subject_type_label':
        $subject_type_id = $access_record->ar_subject_type->value;
        if (\Drupal::entityTypeManager()->hasDefinition($subject_type_id)) {
          $replacements[$original] = \Drupal::entityTypeManager()->getDefinition($subject_type_id)->getLabel();
        }
        break;

      case 'ar_target_type':
        $replacements[$original] = $access_record->ar_target_type->value;
        break;

      case 'ar_target_type_label':
        $target_type_id = $access_record->ar_target_type->value;
        if (\Drupal::entityTypeManager()->hasDefinition($target_type_id)) {
          $replacements[$original] = \Drupal::entityTypeManager()->getDefinition($target_type_id)->getLabel();
        }
        break;

      case 'ar_operation':
        $operations = [];
        $available = AccessRecord::availableOperations();
        foreach ($access_record->get('ar_operation')->getValue() as $item) {
          if (isset($available[$item['value']])) {
            $operations[] = $item['value'];
          }
        }
        $replacements[$original] = implode(', ', $operations);
        break;

      case 'ar_operation_label':
        $operations = [];
        $available = AccessRecord::availableOperations();
        foreach ($access_record->get('ar_operation')->getValue() as $item) {
          if (isset($available[$item['value']])) {
            $operations[] = $available[$item['value']];
          }
        }
        $replacements[$original] = implode(', ', $operations);
        break;

      case 'ar_langcode':
        $replacements[$original] = $access_record->language()->getId();
        break;

      case 'edit_url':
        $replacements[$original] = $access_record->toUrl('edit-form', $url_options)->toString();
        break;

      // Default values for the chained tokens handled below.
      case 'created':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = \Drupal::service('date.formatter')->format($access_record->getCreatedTime(), 'medium', '', NULL, $langcode);
        break;

      case 'changed':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = \Drupal::service('date.formatter')->format($access_record->getChangedTime(), 'medium', '', NULL, $langcode);
        break;

      case 'owner':
        $account = $access_record->getOwner() ?: User::load(0);
        $bubbleable_metadata->addCacheableDependency($account);
        $replacements[$original] = $account->label();
        break;

      case 'string_representation':
        $replacements[$original] = $access_record->getStringRepresentation();
        break;
    }
  }

  if ($owner_tokens = $token_service->findWithPrefix($tokens, 'owner')) {
    $replacements += $token_service->generate('user', $owner_tokens, ['user' => $access_record->getOwner()], $options, $bubbleable_metadata);
  }

  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate('date', $created_tokens, ['date' => $access_record->getCreatedTime()], $options, $bubbleable_metadata);
  }

  if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
    $replacements += $token_service->generate('date', $changed_tokens, ['date' => $access_record->getChangedTime()], $options, $bubbleable_metadata);
  }

  return $replacements;
}
