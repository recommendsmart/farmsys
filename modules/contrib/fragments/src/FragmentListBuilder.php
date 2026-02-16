<?php

namespace Drupal\fragments;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Fragment entities.
 *
 * @ingroup fragments
 */
class FragmentListBuilder extends EntityListBuilder {

  /**
   * The date formatter interface.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Inject the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service to set.
   */
  public function setDateFormatter(DateFormatterInterface $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\fragments\FragmentListBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->setDateFormatter($container->get('date.formatter'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['author'] = $this->t('Author');
    $header['updated'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\fragments\Entity\Fragment $entity */

    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.fragment.edit_form',
      ['fragment' => $entity->id()]
    );
    $row['author'] = $entity->getOwner()->toLink();
    $row['updated'] = $this->dateFormatter->format($entity->getChangedTime());
    return $row + parent::buildRow($entity);
  }

}
