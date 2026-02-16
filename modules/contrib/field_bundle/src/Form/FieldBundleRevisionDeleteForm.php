<?php

namespace Drupal\field_bundle\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a field bundle revision.
 *
 * @internal
 */
class FieldBundleRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The field bundle revision.
   *
   * @var \Drupal\field_bundle\FieldBundleInterface
   */
  protected $revision;

  /**
   * The field bundle storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $fieldBundleStorage;

  /**
   * The field bundle config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldBundleConfigStorage;

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
   * Constructs a new FieldBundleRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $field_bundle_storage
   *   The field bundle storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $field_bundle_config_storage
   *   The field bundle config storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $field_bundle_storage, EntityStorageInterface $field_bundle_config_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->fieldBundleStorage = $field_bundle_storage;
    $this->fieldBundleConfigStorage = $field_bundle_config_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('field_bundle'),
      $entity_type_manager->getStorage('field_bundle_config'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_bundle_revision_delete_confirm';
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
    return new Url('entity.field_bundle.version_history', ['field_bundle' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $field_bundle_revision = NULL) {
    $this->revision = $this->fieldBundleStorage->loadRevision($field_bundle_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->fieldBundleStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $config_id = $this->fieldBundleConfigStorage->load($this->revision->bundle())->label();
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of @type %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '@type' => $config_id,
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      'entity.field_bundle.version_history',
      ['field_bundle' => $this->revision->id()]
    );
  }

}
