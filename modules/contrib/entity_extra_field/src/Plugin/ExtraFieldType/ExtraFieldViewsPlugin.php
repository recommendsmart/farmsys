<?php

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\views\ViewEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;

/**
 * Define extra field views plugin.
 *
 * @ExtraFieldType(
 *   id = "views",
 *   label = @Translation("Views")
 * )
 */
class ExtraFieldViewsPlugin extends ExtraFieldTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'display' => NULL,
      'view_name' => NULL,
      'arguments' => [],
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

    $view_name = $this->getPluginFormStateValue('view_name', $form_state);

    $form['view_name'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#required' => TRUE,
      '#options' => $this->getViewOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $view_name,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
      ] + $this->extraFieldPluginAjax(),
    ];

    if (isset($view_name) && !empty($view_name)) {
      /** @var \Drupal\views\Entity\View $instance */
      $view = $this->loadView($view_name);
      $display = $this->getPluginFormStateValue('display', $form_state);

      $form['display'] = [
        '#type' => 'select',
        '#title' => $this->t('Display'),
        '#required' => TRUE,
        '#options' => $this->getViewDisplayOptions($view),
        '#default_value' => $display,
      ];
      $form['arguments'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Arguments'),
        '#description' => $this->t('Input the views display arguments. If there
          are multiple, use a comma delimiter. <br/> <strong>Note:</strong>
          Tokens are supported.'),
        '#default_value' => $this->getPluginFormStateValue('arguments', $form_state),
      ];

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
    return $this->renderView($entity);
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies(): array {
    if ($view = $this->getView()) {
      $this->addDependencies($view->getDependencies());
    }

    return parent::calculateDependencies();
  }

  /**
   * Render the view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The view entity instance.
   *
   * @return array|null
   *   An renderable array of the view.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function renderView(EntityInterface $entity): ?array {
    $view_name = $this->getViewName();

    if (!isset($view_name)) {
      return [];
    }
    $view_arguments = $this->getViewArguments($entity);

    return views_embed_view(
      $view_name,
      $this->getViewDisplay(),
      ...$view_arguments
    );
  }

  /**
   * Get the view instance.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   The view instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getView(): ViewEntityInterface {
    return $this->loadView($this->getViewName());
  }

  /**
   * Get the view name.
   *
   * @return string|null
   *   The view name; otherwise NULL.
   */
  protected function getViewName(): ?string {
    return $this->getConfiguration()['view_name'] ?? NULL;
  }

  /**
   * Get the view display.
   *
   * @return string
   *   The view display name; otherwise default.
   */
  protected function getViewDisplay(): string {
    return $this->getConfiguration()['display'] ?? 'default';
  }

  /**
   * Get the view arguments.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity instance.
   *
   * @return array
   *   An array of view arguments.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getViewArguments(EntityInterface $entity): array {
    $configuration = $this->getConfiguration();

    if (
      !isset($configuration['arguments'])
      || empty($configuration['arguments'])
    ) {
      return [];
    }
    $arguments = array_filter(explode(',', $configuration['arguments']));

    foreach ($arguments as &$argument) {
      $argument = $this->processEntityToken($argument, $entity);
    }

    return $arguments;
  }

  /**
   * Get view options.
   *
   * @return array
   *   An array of view options.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getViewOptions(): array {
    $options = [];

    /** @var \Drupal\views\Entity\View $view */
    foreach ($this->getActiveViewList() as $view_id => $view) {
      $options[$view_id] = $view->label();
    }

    return $options;
  }

  /**
   * Get view display options.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view instance.
   *
   * @return array
   *   An array of view display options.
   *
   * @throws \Exception
   */
  protected function getViewDisplayOptions(ViewEntityInterface $view): array {
    $options = [];

    $exec = $view->getExecutable();
    $exec->initHandlers();

    /** @var \Drupal\views\Plugin\views\display\DisplayPluginInterface $display */
    foreach ($exec->displayHandlers->getIterator() as $display_id => $display) {
      if (!isset($display->display['display_title'])) {
        continue;
      }
      $options[$display_id] = $display->display['display_title'];
    }

    return $options;
  }

  /**
   * Load view instance.
   *
   * @param string $view_name
   *   The view name.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   The view instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadView(string $view_name): ViewEntityInterface {
    return $this->getViewStorage()->load($view_name);
  }

  /**
   * Get active view list.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of active views.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getActiveViewList(): array {
    return $this
      ->getViewStorage()
      ->loadByProperties(['status' => TRUE]);
  }

  /**
   * Get view storage instance.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The view storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getViewStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('view');
  }

}
