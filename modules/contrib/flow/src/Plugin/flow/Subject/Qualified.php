<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\flow\Entity\EntityQualifier;
use Drupal\flow\Plugin\FlowQualifierInterface;
use Drupal\flow\Plugin\FlowSubjectBase;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subject for qualified content.
 *
 * @FlowSubject(
 *   id = "qualified",
 *   label = @Translation("Qualified content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\QualifiedDeriver"
 * )
 */
class Qualified extends FlowSubjectBase {

  /**
   * The list of qualifier plugin instances.
   *
   * @var \Drupal\flow\Plugin\FlowQualifierInterface[]
   */
  protected array $qualifiers = [];

  /**
   * The list of qualifying subject plugin instances.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface[]
   */
  protected array $qualifying = [];

  /**
   * The entity qualifier.
   *
   * @var \Drupal\flow\Entity\EntityQualifier
   */
  protected EntityQualifier $entityQualifier;

  /**
   * The current offset when working on a large list.
   *
   * @var int
   */
  protected int $listOffset = 0;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Subject\Qualified $instance */
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->setEntityQualifier($container->get('flow.entity_qualifier'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    foreach ($this->qualifiers as $i => $qualifier) {
      $qualifying = $this->qualifying[$i];
      foreach ($qualifying->getSubjectItems() as $item) {
        if ($this->entityQualifier->qualifies($item, [$qualifier])) {
          $this->listOffset++;
          yield $item;
        }
      }
    }
    if ($this->listOffset === 0) {
      return [];
    }
  }

  /**
   * Set the entity qualifier.
   *
   * @param \Drupal\flow\Entity\EntityQualifier $qualifier
   *   The entity qualifier.
   */
  public function setEntityQualifier(EntityQualifier $qualifier): void {
    $this->entityQualifier = $qualifier;
  }

  /**
   * Adds a qualifying subject with its according qualifier.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectInterface $subject
   *   The qualifying subject plugin instance.
   * @param Drupal\flow\Plugin\FlowQualifierInterface $qualifier
   *   The plugin instance that is responsible for qualifying the subject.
   *
   * @return $this
   */
  public function addQualifying(FlowSubjectInterface $subject, FlowQualifierInterface $qualifier): Qualified {
    $this->qualifiers[] = $qualifier;
    $this->qualifying[] = $subject;
    return $this;
  }

}
