<?php

namespace Drupal\storage\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Storage revision.
 *
 * @ingroup storage
 */
class StorageRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Storage revision.
   *
   * @var \Drupal\storage\Entity\StorageInterface
   */
  protected $revision;

  /**
   * The Storage storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->storageStorage = $container->get('entity_type.manager')->getStorage('storage');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'storage_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.storage.version_history', ['storage' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $storage_revision = NULL) {
    $this->revision = $this->storageStorage->loadRevision($storage_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->storageStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Storage: deleted %name revision %revision.', ['%name' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Storage %name has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%name' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.storage.canonical',
       ['storage' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {storage_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.storage.version_history',
         ['storage' => $this->revision->id()]
      );
    }
  }

}
