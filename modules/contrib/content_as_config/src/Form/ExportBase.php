<?php

namespace Drupal\content_as_config\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for exporting entities.
 */
abstract class ExportBase extends FormBase implements ContentImportExportInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The dependency-injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * ExportBase constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->entityTypeManager = $container->get('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_as_config_export_' . $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_label = $this->entityTypeManager->getDefinition($this->getEntityType())->getPluralLabel();
    $entity_list = $this->getListElements();

    if (empty($entity_list)) {
      $form['no_items_found'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No %et were found.', ['%et' => $entity_type_label]),
      ];
      return $form;
    }

    $form['export_list'] = [
      '#title' => $this->t('Export these @type:', ['@type' => $entity_type_label]),
      '#type' => 'checkboxes',
      '#options' => $entity_list,
      '#default_value' => array_keys($entity_list),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getController($this->container)->export($form, $form_state);
  }

  /**
   * Returns a keyed list of items to be shown on the export-form.
   *
   * @return array
   *   An array whose keys are unique identifiers, and whose values are
   *   human-readable strings.
   */
  protected function getListElements(): array {
    $export_list = [];
    $entities = $this->entityTypeManager->getStorage($this->getEntityType())
      ->loadMultiple();
    foreach ($entities as $entity) {
      $export_list[$entity->uuid()] = $entity->label();
    }
    return $export_list;
  }

}
