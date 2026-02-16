<?php

namespace Drupal\designs\Plugin\designs\content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\designs\DesignContentBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;

/**
 * The token content plugin.
 *
 * @DesignContent(
 *   id = "token",
 *   label = @Translation("Token")
 * )
 */
class TokenContent extends DesignContentBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Token constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $token_types = [];
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    foreach ($form['#design_contexts'] as $type => $context) {
      if ("entity:{$type}" === $context->getDataType()) {
        $token_types[] = $type;
      }
    }

    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Token'),
      '#default_value' => $this->configuration['value'],
    ];

    $form['help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#global_types' => TRUE,
      '#dialog' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    // Tokens can generate any exception due to invalid setup, so just make
    // the content nothing.
    try {
      $value = $this->token->replace(
        $this->configuration['value'],
        $element['#context'] ?? []
      );
    }
    catch (\Exception $e) {
      $value = '';
    }

    return [
      '#markup' => Markup::create($value),
    ];
  }

}
