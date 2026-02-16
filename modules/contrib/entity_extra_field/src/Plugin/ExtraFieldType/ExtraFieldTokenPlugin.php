<?php

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;

/**
 * Define extra field token plugin.
 *
 * @ExtraFieldType(
 *   id = "token",
 *   label = @Translation("Token")
 * )
 */
class ExtraFieldTokenPlugin extends ExtraFieldTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'type' => NULL,
      'token' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $type = $this->getPluginFormStateValue('type', $form_state);

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#options' => [
        'textfield' => $this->t('Text Field'),
        'text_format' => $this->t('Text Format'),
      ],
      '#empty_empty' => $this->t('- Select -'),
      '#default_value' => $type,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
      ] + $this->extraFieldPluginAjax(),
    ];

    if (isset($type) && !empty($type)) {
      $configuration = $this->getConfiguration();

      $form['token'] = [
        '#type' => $type,
        '#title' => $this->t('Token Value'),
        '#default_value' => is_array($configuration['token'])
        ? $configuration['token']['value']
        : $configuration['token'],
      ];

      if ($type === 'text_format'
        && isset($configuration['token']['format'])) {
        $form['token']['#format'] = $configuration['token']['format'];
      }

      if ($this->moduleHandler->moduleExists('token')) {
        $form['token_replacements'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => $this->getEntityTokenTypes(
            $this->getTargetEntityTypeDefinition(),
            $this->getTargetEntityTypeBundle()->id()
          ),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(
    EntityInterface $entity,
    EntityDisplayInterface $display
  ): array {
    $build = [];

    if ($entity instanceof ContentEntityInterface) {
      $token_value = $this->getProcessedTokenValue($entity);

      switch ($this->getTokenTextType()) {
        case 'textfield':
          $build = [
            '#plain_text' => $token_value,
          ];
          break;

        case 'text_format':
          $build = [
            '#type' => 'processed_text',
            '#text' => $token_value,
            '#format' => $this->getTokenTextFormat(),
          ];
          break;
      }
    }

    return $build;
  }

  /**
   * Get token text type.
   *
   * @return string
   *   The token text type.
   */
  protected function getTokenTextType(): ?string {
    return $this->getConfiguration()['type'] ?? NULL;
  }

  /**
   * Get token text format.
   *
   * @return string
   *   The token text format.
   */
  protected function getTokenTextFormat(): ?string {
    return $this->getConfiguration()['token']['format'] ?? NULL;
  }

  /**
   * Get processed token value token.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return string
   *   The process token value.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getProcessedTokenValue(
    ContentEntityInterface $entity
  ): string {
    $configuration = $this->getConfiguration();

    $token_value = is_array($configuration['token'])
      ? $configuration['token']['value']
      : $configuration['token'];

    return $this->processEntityToken($token_value, $entity);
  }

}
