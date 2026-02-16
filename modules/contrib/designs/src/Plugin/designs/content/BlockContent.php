<?php

namespace Drupal\designs\Plugin\designs\content;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\PreviewFallbackInterface;
use Drupal\designs\DesignContentBase;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The block content plugin.
 *
 * @DesignContent(
 *   id = "block",
 *   label = @Translation("Block"),
 *   settings = FALSE
 * )
 */
class BlockContent extends DesignContentBase implements ContainerFactoryPluginInterface {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * BlockContent constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, BlockManagerInterface $blockManager, PluginFormFactoryInterface $pluginFormFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockManager = $blockManager;
    $this->pluginFormFactory = $pluginFormFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'plugin' => '',
      'configuration' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $contexts = $form['#design_contexts'];
    foreach ($contexts as $name => $definition) {
      $contexts[$name] = new Context($definition);
    }

    $allowable = $this->blockManager->getDefinitionsForContexts($contexts);
    $options = ['' => $this->t('- Select block -')];
    foreach ($this->blockManager->getGroupedDefinitions() as $category => $blocks) {
      foreach ($blocks as $plugin_id => $block) {
        if (isset($allowable[$plugin_id])) {
          $options[$category][$plugin_id] = $block['admin_label'];
        }
      }
    }

    $plugin = $this->configuration['plugin'];

    $form['plugin'] = [
      '#type' => 'select',
      '#op' => 'select_plugin',
      '#title' => $this->t('Block'),
      '#options' => $options,
      '#default_value' => $plugin,
      '#wrapper_id' => $form['#wrapper_id'],
      '#wrapper_parents' => $form['#parents'],
      '#ajax' => [
        'callback' => [static::class, 'multistepAjax'],
        'wrapper' => $form['#wrapper_id'],
        'effect' => 'fade',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#op' => 'select_plugin',
      '#name' => self::getElementId($form['#parents'], '-block'),
      '#value' => $this->t('Change block'),
      '#attributes' => ['class' => ['js-hide']],
      '#wrapper_id' => $form['#wrapper_id'],
      '#wrapper_parents' => $form['#parents'],
      '#submit' => [[static::class, 'multistepSubmit']],
    ];

    $form['configuration'] = [];

    if ($plugin) {
      $plugin = $this->blockManager->createInstance($plugin, $this->configuration['configuration']);
      if ($plugin instanceof ContextAwarePluginInterface) {
        foreach ($contexts as $name => $context) {
          $plugin->setContext($name, $context);
        }
      }

      $subform_state = SubformState::createForSubform($form['configuration'], $form, $form_state);
      $form['configuration'] = $this->getPluginForm($plugin)
        ->buildConfigurationForm($form['configuration'], $subform_state);

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['update'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update settings'),
        '#op' => 'select_plugin',
        '#name' => self::getElementId($form['#parents'], '-update'),
        '#submit' => [[static::class, 'multistepSubmit']],
        '#wrapper_id' => $form['#wrapper_id'],
        '#wrapper_parents' => $form['#parents'],
        '#ajax' => [
          'callback' => [static::class, 'multistepAjax'],
          'wrapper' => $form['#wrapper_id'],
          'effect' => 'fade',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    unset($values['submit']);
    unset($values['actions']);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    if (empty($this->configuration['plugin'])) {
      return [];
    }

    try {
      /** @var BlockPluginInterface $plugin */
      $plugin = $this->blockManager->createInstance($this->configuration['plugin'], $this->configuration['configuration']);
      if (!$plugin) {
        return [];
      }
    }
    catch (PluginNotFoundException $e) {
      return [];
    }

    if (!$plugin->access(\Drupal::currentUser())) {
      return [];
    }

    $content = $plugin->build();

    $build = [
      '#theme' => 'block',
      '#configuration' => $plugin->getConfiguration(),
      '#plugin_id' => $plugin->getPluginId(),
      '#base_plugin_id' => $plugin->getBaseId(),
      '#derivative_plugin_id' => $plugin->getDerivativeId(),
    ];

    if (isset($content['#attributes'])) {
      $build['#attributes'] = $content['#attributes'];
      unset($content['#attributes']);
    }
    $build['content'] = $content;

    return $build;
  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the block.
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'configure');
    }
    return $block;
  }

}
