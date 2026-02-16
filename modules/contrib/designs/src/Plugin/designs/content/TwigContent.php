<?php

namespace Drupal\designs\Plugin\designs\content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\designs\DesignContentBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The twig content plugin.
 *
 * @DesignContent(
 *   id = "twig",
 *   label = @Translation("Twig")
 * )
 */
class TwigContent extends DesignContentBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * TwigContent constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
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
    $contexts = [];
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    foreach ($form['#design_contexts'] as $id => $context) {
      $contexts[] = $this->t('@label: %id', [
        '@label' => $context->getLabel() ?: $id,
        '%id' => $id,
      ]);
    }
    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template'),
      '#default_value' => $this->configuration['value'],
    ];
    if ($contexts) {
      $form['value']['#description'] = $this->t('Contexts:') . ' ' .
        implode(', ', $contexts) . '.';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    try {
      $markup = [
        '#type' => 'inline_template',
        '#template' => $this->configuration['value'],
        '#context' => $element['#context'] ?? [],
      ];
      $this->renderer->render($markup);
    }
    catch (\Exception $e) {
      $form_state->setError($form['value'], $this->t('The template contains rendering errors.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    try {
      $markup = [
        '#type' => 'inline_template',
        '#template' => $this->configuration['value'],
        '#context' => $element['#context'] ?? [],
      ];
      return [
        '#markup' => Markup::create((string) $this->renderer->render($markup)),
      ];
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
