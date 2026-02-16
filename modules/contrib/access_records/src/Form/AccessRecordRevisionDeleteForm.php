<?php

namespace Drupal\access_records\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a access record revision.
 *
 * @internal
 */
class AccessRecordRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The access record revision.
   *
   * @var \Drupal\access_records\AccessRecordInterface
   */
  protected $revision;

  /**
   * The access record storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $accessRecordStorage;

  /**
   * The access record type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $accessRecordTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new AccessRecordRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $access_record_storage
   *   The access record storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $access_record_type_storage
   *   The access record type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $access_record_storage, EntityStorageInterface $access_record_type_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->accessRecordStorage = $access_record_storage;
    $this->accessRecordTypeStorage = $access_record_type_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('access_record'),
      $entity_type_manager->getStorage('access_record_type'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_record_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.access_record.version_history', ['access_record' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $access_record_revision = NULL) {
    $this->revision = $this->accessRecordStorage->loadRevision($access_record_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->accessRecordStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $type_id = $this->accessRecordTypeStorage->load($this->revision->bundle())->label();
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of @type %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '@type' => $type_id,
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      'entity.access_record.version_history',
      ['access_record' => $this->revision->id()]
    );
  }

}
