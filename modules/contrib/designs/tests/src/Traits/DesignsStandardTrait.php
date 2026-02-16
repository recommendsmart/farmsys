<?php

namespace Drupal\Tests\designs\Traits;

/**
 * Provides a standardized UI designs setup.
 */
trait DesignsStandardTrait {

  use DesignsTestTrait;

  /**
   * Create a standard design for testing.
   *
   * @param string $parents
   *   The parents.
   * @param array $settings
   *   The settings.
   * @param array $custom
   *   The custom details.
   * @param array $region
   *   The region order.
   */
  protected function drupalDesign($parents, array $settings, array $custom, array $region) {
    $this->drupalSetupDesign($parents, 'content');
    if ($settings) {
      $this->drupalSetupDesignSettings($parents, [
        'attributes' => [
          'attributes' => $settings['attributes'],
        ],
        'tag' => [
          'value' => $settings['tag'] ?? 'article',
        ],
      ]);
    }
    if ($custom) {
      $this->drupalSetupDesignContent($parents, [
        $custom['id'] => [
          'plugin' => 'text',
          'config' => [
            'label' => $custom['label'],
            'value' => $custom['text'],
          ],
        ],
      ]);
    }
    if ($region) {
      $this->drupalSetupDesignRegions($parents, [
        'content' => $region,
      ]);
    }
  }

  /**
   * Create a standard contextual design for testing.
   *
   * @param string $parents
   *   The parents.
   * @param array $custom
   *   The custom details.
   * @param array $region
   *   The region order.
   */
  protected function drupalDesignContext($parents, array $custom, array $region) {
    $this->drupalSetupDesign($parents, 'content');
    $this->drupalSetupDesignSettings($parents, [
      'attributes' => [
        'plugin' => 'token',
        'config' => [
          'value' => 'id="node-[node:nid]"',
        ],
      ],
      'tag' => [
        'plugin' => 'twig',
        'config' => [
          'value' => '{{ node.id ? "div" : "span" }}',
        ],
      ],
    ]);
    if ($custom) {
      $custom_id = $custom['id'];
      $this->drupalSetupDesignContent($parents, [
        $custom_id => [
          'plugin' => 'token',
          'config' => [
            'label' => $custom['label'],
            'value' => 'node [node:nid] token',
          ],
        ],
        "{$custom_id}_1" => [
          'plugin' => 'twig',
          'config' => [
            'label' => $custom['label'],
            'value' => 'node {{ node.id }} twig',
          ],
        ],
      ]);
    }
    if ($region) {
      $this->drupalSetupDesignContent($parents, [
        'content' => $region,
      ]);
    }
  }

}
