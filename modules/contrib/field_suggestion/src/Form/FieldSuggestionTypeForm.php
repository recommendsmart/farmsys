<?php

namespace Drupal\field_suggestion\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for field suggestion type forms.
 */
class FieldSuggestionTypeForm extends BundleEntityFormBase {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $instance->entityFieldManager = $container->get('entity_field.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field_type_options = [];

    $grouped_field_types = $this->fieldTypePluginManager->getGroupedDefinitions(
      $this->fieldTypePluginManager->getUiDefinitions()
    );

    $names = $this->entityTypeManager->getStorage('field_suggestion_type')
      ->getQuery()
      ->execute();

    foreach ($grouped_field_types as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        if (!in_array($name, $names)) {
          $field_type_options[$category][$name] = $field_type['label'];
        }
      }
    }

    $form['field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Field type'),
      '#options' => $field_type_options,
      '#empty_option' => $this->t('- Select a field type -'),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\field_suggestion\FieldSuggestionTypeInterface $type */
    $type = $this->entity;

    $type->set('id', $form_state->getValue('field_type'));

    $status = parent::save($form, $form_state);

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus(
        $this->t('The field suggestion type %name has been updated.', $t_args)
      );
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus(
        $this->t('The field suggestion type %name has been added.', $t_args)
      );

      $t_args['link'] = $type->toLink($this->t('View'), 'collection')
        ->toString();

      $this->logger('field_suggestion')
        ->notice('Added field suggestion type %name.', $t_args);
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();

    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
