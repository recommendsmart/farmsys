<?php

namespace Drupal\node_singles\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A service providing settings for the Node Singles module.
 */
class NodeSinglesSettings implements NodeSinglesSettingsInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    $config = $this->configFactory->get('node_singles.settings');
    return Markup::create($config->get('label'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionLabel() {
    $config = $this->configFactory->get('node_singles.settings');
    if ($label = $config->get('label_collection')) {
      return $label;
    }

    $label = $this->getLabel();
    return $this->t('@label nodes', ['@label' => $label]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSingularLabel() {
    $config = $this->configFactory->get('node_singles.settings');
    if ($label = $config->get('label_singular')) {
      return $label;
    }

    return mb_strtolower($this->getLabel());
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel() {
    $config = $this->configFactory->get('node_singles.settings');
    if ($label = $config->get('label_plural')) {
      return $label;
    }

    $lowercaseLabel = $this->getSingularLabel();
    return $this->t('@label nodes', ['@label' => $lowercaseLabel]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCountLabel(int $count) {
    $config = $this->configFactory->get('node_singles.settings');
    $label = $config->get('label_count');

    if (isset($label['singular']) && isset($label['plural'])) {
      return $this->formatPlural($count, $label['singular'], $label['plural']);
    }

    return $this->formatPlural(
      $count,
      '@count @label',
      '@count @label nodes',
      ['@label' => $this->getSingularLabel()],
      ['context' => 'Single node type label']
    );
  }

}
