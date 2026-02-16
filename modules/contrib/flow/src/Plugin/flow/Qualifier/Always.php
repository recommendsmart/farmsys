<?php

namespace Drupal\flow\Plugin\flow\Qualifier;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Plugin\FlowQualifierBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Always qualifies an entity.
 *
 * @FlowQualifier(
 *   id = "always",
 *   label = @Translation("Always qualified content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Qualifier\AlwaysDeriver"
 * )
 */
class Always extends FlowQualifierBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Qualifier\Always $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function qualifies(ContentEntityInterface $entity): bool {
    $definition = $this->getPluginDefinition();
    if (($definition['entity_type'] !== $entity->getEntityTypeId()) || ($definition['bundle'] !== $entity->bundle())) {
      return FALSE;
    }
    return ($this->settings['qualified'] ?? NULL) === 'always';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['qualified'] = [
      '#type' => 'select',
      '#title' => $this->t('Qualified'),
      '#default_value' => $this->settings['qualified'] ?? 'always',
      '#options' => [
        'always' => $this->t('Always'),
        'never' => $this->t('Never'),
      ],
      '#required' => TRUE,
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->settings['qualified'] = $form_state->getValue(['qualified'], 'always');
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
