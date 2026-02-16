<?php

namespace Drupal\friggeri_cv;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the profile entity type.
 */
class ProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 5;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new ProfileListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total profiles: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      [
        "data" => $this->t('ID'),
        "field" => "id",
        "specifier" => "id",
      ],
      "picture" => $this->t("Picture"),
      [
        "data" => $this->t('Name'),
        "field" => "name",
        "specifier" => "name",
      ],
      [
        "data" => $this->t('Title'),
        "field" => "title",
        "specifier" => "title",
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $headers = $this->buildHeader();
    $query = $this->getStorage()->getQuery()
      ->tableSort($headers);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['picture'] = $entity->getPicture();
    $row['name'] = $entity->toLink($entity->getName(), 'canonical', ['attributes' => ["target" => "_blank"]]);
    $row['title'] = $entity->getTitle();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    $operations['view'] = [
      'title' => $this->t('View'),
      'weight' => 10,
      'url' => Url::fromRoute(
        "entity.profile.canonical",
        ['profile' => $entity->id()],
        ['attributes' => ['target' => '_blank']]
      ),
    ];
    $operations['pdf'] = [
      'title' => $this->t('PDF'),
      'weight' => 10,
      'url' => Url::fromRoute(
        "entity.profile.pdf",
        ['profile' => $entity->id()],
        ['attributes' => ['target' => '_blank']]
      ),
    ];

    return $operations;
  }

}
