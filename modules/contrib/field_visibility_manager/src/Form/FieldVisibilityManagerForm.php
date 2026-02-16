<?php

namespace Drupal\field_visibility_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * FieldRoleRestriction Form.
 */
class FieldVisibilityManagerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'field_visibility_manager.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_visibility_manager_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = NodeType::loadMultiple();
    // List out all roles.
    $roles = Role::loadMultiple();
    $role_list = [];
    foreach ($roles as $role => $rolesObj) {
      $role_list[] = $role;
    }
    // List of all fileds in system.
    $values = [];
    foreach ($node_types as $node_type) {
      $values[$node_type->id()] = $node_type->label();
      $entity_type_id = 'node';
      $bundle = $node_type->id();
      $definitions = (\Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle));
      foreach ($definitions as $key => $val) {
        if (strpos($key, 'field_') !== FALSE) {
          $fieldNames[ucwords($bundle) . " - " .$val->getLabel()] = $key;
        }
      }
    }

    $bundleFields = $fieldNames;
    $config = $this->config('field_visibility_manager.adminsettings');
    // The permissions table.
    // For the initial loading.
    if (!$config->get('permissions')) {
      $form['permissions'] = [
        '#type' => 'table',
        '#header' => [$this->t('Field Label'), $this->t('Fields')],
        '#id' => 'permissions',
        '#attributes' => ['class' => ['permissions', 'js-permissions']],
        '#sticky' => TRUE,
      ];
      // Table columns.
      foreach ($role_list as $role) {
        $form['permissions']['#header'][] = [
          'data' => $role,
        ];
      }
      $form['permissions']['#header'][] = [
          'data' => "",
      ];
      // Table row values.
      foreach ($bundleFields as $key => $field) {
        foreach ($role_list as $role) {
           $form['permissions'][$key]['field_label'] = [
            '#type' => 'label',
            '#title' => $key,
          ];
          $form['permissions'][$key]['Field'] = [
            '#type' => 'textfield',
            '#default_value' => $field,
            '#attributes' => ['readonly' => 'readonly'],
          ];
          $form['permissions'][$key][$role] = [
            '#type' => 'checkbox',
            'data' => $field,
          ];
        }
        $form['permissions'][$key]['field_name'] = array(
            '#type' => 'value',
            '#value' => $key ,
        );
      }
    }
    $existingarray = [];
    $value = $config->get('permissions');
    foreach ($value as $field) {
      $existingarray[] = $field['Field'];
    }
    foreach ($definitions as $key => $val) {
      if (strpos($key, 'field_') !== FALSE) {
        $fieldNames[$val->getLabel()] = $key;
      }
    }
    // Newly added fields after configuration save.
    $result = array_diff($bundleFields, $existingarray);
    // Removed fields after configuration save.
    $oldresult = array_diff($existingarray, $bundleFields);
    // Removing the removed fields from listing.
    foreach ($value as $subKey => $subArray) {
      foreach ($oldresult as $exvalue) {
        if ($subArray['Field'] == $exvalue) {
          unset($value[$subKey]);
        }
      }
    }
    // Including the newly added fields for listing.
    foreach ($result as $label => $field) {
      $value[$label] = ["Field" => $field, "field_name" => $label];
    }
    // To bind saved values from config.
    if ($config->get('permissions')) {
      $form['permissions'] = [
        '#type' => 'table',
        '#header' => [$this->t('Field Label'), $this->t('Fields')],
        '#id' => 'permissions',
        '#attributes' => ['class' => ['permissions', 'js-permissions']],
        '#sticky' => TRUE,
      ];
      // Table columns.
      foreach ($role_list as $role) {
        $form['permissions']['#header'][] = [
          'data' => $role,
        ];
      }
       $form['permissions']['#header'][] = [
          'data' => "",
        ];
      // Table row values.
      foreach ($value as $key => $field) {
        foreach ($role_list as $role) {
           $form['permissions'][$key]['field_label'] = [
            '#type' => 'label',
            '#title' => $field['field_name'],
          ];
          $form['permissions'][$key]['Field'] = [
            '#type' => 'textfield',
            '#default_value' => $field['Field'],
            '#attributes' => ['readonly' => 'readonly'],
            'class' => ['checkbox'],
          ];
          $form['permissions'][$key][$role] = [
            '#type' => 'checkbox',
            'class' => ['checkbox'],
            '#default_value' => $field[$role],
          ];        
        }
          $form['permissions'][$key]['field_name'] = [
            '#type' => 'hidden',
            '#value' => $field['field_name'] ,
          ];
      }
    }
    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('field_visibility_manager.adminsettings')
      ->set('permissions', $form_state->getValue('permissions'))
      ->save();
    parent::submitForm($form, $form_state);

  }

}
