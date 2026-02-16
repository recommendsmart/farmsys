<?php

namespace Drupal\content_as_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for exporting entities.
 */
abstract class ImportBase extends FormBase implements ContentImportExportInterface {

  /**
   * The DI container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * ExportBase constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
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
    return 'content_as_config_import_' . $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_label = $this->container->get('entity_type.manager')->getDefinition($this->getEntityType())->getPluralLabel();
    $item_list = $this->config('content_as_config.' . $this->getEntityType())->get();
    if (empty($item_list)) {
      $form['no_items_found'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No exported %et were found.', ['%et' => $entity_type_label]),
      ];
      return $form;
    }
    $options = [];
    foreach ($item_list as $item) {
      $options[$item['uuid']] = $this->getLabel($item);
    }

    $form['import_list'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => array_keys($options),
      '#title' => $this->t('Select the @type you would like to import:', ['@type' => $entity_type_label]),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['instructions'] = [
      '#type' => 'markup',
      '#markup' => $this->t(
        '<p>A <em>safe</em> import will create any new @type without updating or deleting anything.<br/>A <em>full</em> import will create/update @type present in configuration, and delete those not present.<br/>A <em>forced</em> import will first delete <strong>all</strong> @type, then perform an import.</p>',
        ['@type' => $entity_type_label]),
    ];

    $form['actions']['import_safe'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import (safe)'),
      '#button_type' => 'primary',
      '#name' => 'safe',
    ];
    $form['actions']['import_full'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import (full)'),
      '#name' => 'full',
    ];
    $form['actions']['import_force'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import (forced)'),
      '#name' => 'force',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $form['style'] = $trigger['#name'];
    $this->getController($this->container)->import($form, $form_state);
  }

}
