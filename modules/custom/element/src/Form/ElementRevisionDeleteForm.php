<?php

namespace Drupal\element\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a element revision.
 *
 * @ingroup element
 */
class ElementRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The element revision.
   *
   * @var \Drupal\element\Entity\ElementInterface
   */
  protected $revision;

  /**
   * The element storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ElementStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Dater formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ElementRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $dateFormatter) {
    $this->ElementStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $container->get('database');
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');

    return new static(
      $entityTypeManager->getStorage('element'),
      $connection,
      $dateFormatter
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'element_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the revision from %revision-date?',
      [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.element.version_history', ['element' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $element_revision = NULL) {
    $this->revision = $this->ElementStorage->loadRevision($element_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ElementStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Element: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);

    $replacements = [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ];
    $message = $this->t('Revision from %revision-date of element %title has been deleted.', $replacements);
    $this->messenger()->addMessage($message);
    $form_state->setRedirect(
      'entity.element.canonical',
       ['element' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {element_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.element.version_history',
         ['element' => $this->revision->id()]
      );
    }
  }

}
