<?php

namespace Drupal\entity_inherit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_inherit\EntityInherit;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The admin form for Entity Inherit.
 */
class EntityInheritAdminForm extends FormBase {

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Class constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The EntityInherit singleton (service).
   */
  final public function __construct(EntityInherit $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_inherit')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fields = $this->app->configGetFields();

    // ::buildForm() can be called twice. Only display feedback when the form
    // is being built to be displayed, not during validation or submission.
    if (!$form_state->getUserInput()) {
      $this->app->displayParentEntityFieldsValidityFeedback();
    }

    $form['fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Parent entity fields'),
      '#description' => $this->t("Entities (children) which reference other entities (parents) via these fields will inherit parents' field values. Be sure to prefix the field names with the entity type ('node.', for example). To set these programmatically, you can use <code>entity_inherit()->setParentEntityFields(['node.field_example_one', 'node.field_example_two'])</code>."),
      '#default_value' => implode(PHP_EOL, $fields),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_inherit';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $app = $this->app;

    $app->setParentEntityFields(explode(PHP_EOL, $form_state->getValue('fields')));
  }

}
