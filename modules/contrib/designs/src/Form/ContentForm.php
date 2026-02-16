<?php

namespace Drupal\designs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentInterface;

/**
 * Provides the content plugin form.
 */
class ContentForm extends FormBase {

  /**
   * The design content.
   *
   * @var \Drupal\designs\DesignContentInterface|null
   */
  protected $content = NULL;

  /**
   * Get the design content.
   *
   * @return \Drupal\designs\DesignContentInterface|null
   *   The design content.
   */
  public function getContent(): ?DesignContentInterface {
    return $this->content;
  }

  /**
   * Set the design content.
   *
   * @param \Drupal\designs\DesignContentInterface|null $content
   *   The design content.
   *
   * @return $this
   *   The object instance.
   */
  public function setContent(?DesignContentInterface $content) {
    $this->content = $content;
    return $this;
  }

  /**
   * Get the content definitions restricted by content.
   *
   * @return array[]
   *   The definitions.
   */
  protected function getContents() {
    return $this->contentManager->getSourceDefinitions(
      'content',
      $this->design->getSourcePlugin()->getPluginId()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->content ? $this->content->label() : 'Content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Create the content listing.
    $contents = [];
    foreach ($this->getContents() as $plugin_id => $definition) {
      $contents[$plugin_id] = $definition['label'];
    }

    // Create the plugin form for the content.
    $form = $this->buildPluginForm($contents, $this->content, $form, $form_state);

    // Add the label modification to the configuration.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->content ? $this->content->label() : '',
      '#weight' => -10,
      '#parents' => array_merge($form['#parents'], ['config', 'label']),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if ($this->content && $this->content->getPluginId() === $values['plugin']) {
      $this->content->validateConfigurationForm($form['config'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    // Create the new version of plugin/configuration.
    if ($values && $values['plugin']) {
      $plugin = $this->contentManager->createInstance(
        $values['plugin'],
        $values['config'] ?? [],
      );
      if ($plugin) {
        $values['config'] = $plugin->submitConfigurationForm($form['config'], $form_state);
        $form_state->setValue($form['#parents'], $values);
      }
    }
    return parent::submitForm($form, $form_state);
  }

}
