<?php

namespace Drupal\custom_pub\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Sets the custom publishing option on a node to a given value.
 *
 * @Action(
 *   id = "set_custom_publishing_option_value",
 *   label = @Translation("Set a custom publish option value on a node"),
 *   type = "node"
 * )
 */
class SetCustomPublishOptionValue extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    $option = $this->configuration['option'];
    $value = $this->configuration['value'];

    $node->{$option} = (bool) $value;
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'option' => NULL,
      'value' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = ['' => ' - Select -'];
    $entities = \Drupal::entityTypeManager()->getStorage('custom_publishing_option')->loadMultiple();

    foreach ($entities as $option) {
      $options[$option->id()] = $option->label();
    }

    $form['option'] = [
      '#title' => $this->t('Custom Publishing Options'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('The custom publishing option to use.'),
      '#default_value' => $this->configuration['option'],
    ];

    $form['value'] = [
      '#title' => $this->t('Leave unchecked for FALSE'),
      '#type' => 'checkbox',
      '#description' => $this->t('The value you want to set the option to.'),
      '#default_value' => $this->configuration['value'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['option'] = $form_state->getValue('option');
    $this->configuration['value'] = (bool) $form_state->getValue('value');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}