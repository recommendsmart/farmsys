<?php

namespace Drupal\node_singles\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders an overview of all single nodes.
 */
class OverviewController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  protected $singles;

  /**
   * The settings service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesSettingsInterface
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->singles = $container->get('node_singles');
    $instance->settings = $container->get('node_singles.settings');

    return $instance;
  }

  /**
   * Renders an overview of all single nodes.
   */
  public function overview(): array {
    $output['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No @pluralLabel found.', [
        '@pluralLabel' => $this->settings->getPluralLabel(),
      ], ['context' => 'Node singles overview page']),
      '#sticky' => TRUE,
    ];

    /** @var \Drupal\node\NodeTypeInterface $item */
    foreach ($this->singles->getAllSingles() as $item) {
      $node = $this->singles->getSingleByBundle($item->id());

      if ($node) {
        $operations = $this->entityTypeManager->getListBuilder('node')->getOperations($node);

        $output['table'][$item->id()]['title'] = [
          '#markup' => sprintf(
            '<a href="%s">%s</a>',
            Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString(),
            $node->label() ?: $item->label()
          ),
        ];

        $output['table'][$item->id()]['description'] = [
          '#plain_text' => $item->getDescription(),
        ];

        $output['table'][$item->id()]['operations'] = [
          '#type' => 'operations',
          '#subtype' => 'node',
          '#links' => $operations,
        ];
      }
    }

    return $output;
  }

  /**
   * Returns the page title.
   */
  public function title() {
    return $this->settings->getCollectionLabel();
  }

}
