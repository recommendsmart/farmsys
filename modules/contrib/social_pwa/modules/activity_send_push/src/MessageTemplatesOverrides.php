<?php

namespace Drupal\activity_send_push;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class MessageTemplatesOverrides.
 *
 * @package Drupal\activity_send_push
 */
class MessageTemplatesOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_names = $this->getTemplates();

    foreach ($config_names as $template) {
      if (in_array($template, $names)) {
        $overrides[$template]['third_party_settings']['activity_logger']['activity_destinations']['push'] = 'push';
      }
    }

    return $overrides;
  }

  /**
   * Templates we need to add the 'web push' destination to.
   *
   * @return array
   *   The list of templates.
   */
  protected function getTemplates() {
    return [
      'message.template.create_post_profile',
      'message.template.create_mention_post',
      'message.template.create_mention_comment',
      'message.template.create_comment_reply_mention',
      'message.template.create_comment_reply',
      'message.template.create_comment_post_profile',
      'message.template.create_like_node_or_post',
      'message.template.create_comment_author_node_post',
      'message.template.create_comment_following_node',
      'message.template.create_content_in_joined_group',
      'message.template.create_private_message',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'MessageTemplatesOverrider';
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
