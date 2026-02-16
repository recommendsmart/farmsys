<?php

namespace Drupal\flow_action\Plugin\flow\Task;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\flow\FlowTaskQueue;
use Drupal\flow\Helpers\EntityFromStackTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\SingleTaskOperationTrait;
use Drupal\flow\Helpers\TokenTrait;
use Drupal\flow\Plugin\FlowTaskBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task for executing an action on content.
 *
 * @FlowTask(
 *   id = "action",
 *   label = @Translation("Execute action on content"),
 *   deriver = "Drupal\flow_action\Plugin\flow\Derivative\Task\ActionDeriver"
 * )
 */
class Action extends FlowTaskBase implements PluginFormInterface {

  use EntityFromStackTrait;
  use ModuleHandlerTrait;
  use StringTranslationTrait;
  use SingleTaskOperationTrait;
  use TokenTrait;

  /**
   * A plugin instance of the action to be used.
   *
   * @var \Drupal\Core\Action\ActionInterface
   */
  protected ActionInterface $action;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    [, $action_plugin_id] = explode('::', $plugin_id);

    /** @var \Drupal\flow_action\Plugin\flow\Task\Action $instance */
    $instance = parent::create($container, ['action_plugin_id' => $action_plugin_id] + $configuration, $plugin_id, $plugin_definition);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setToken($container->get(self::$tokenServiceName));
    $instance->initEntityFromStack();

    // Initialize and set the action plugin instance.
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $container->get('plugin.manager.action');
    $settings = $instance->getSettings();
    $action_configuration = $settings['action'] ?? [];
    $instance->setAction($action_manager->createInstance($action_plugin_id, $action_configuration));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = [
      'settings' => [
        'replace_tokens' => FALSE,
        'access_check' => 'check_access',
        'action' => [],
      ],
    ];
    if ($this->action instanceof ConfigurableInterface) {
      $default['settings']['action'] = $this->action->defaultConfiguration();
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $action = $this->getAction();
    if ($action instanceof ConfigurableInterface) {
      $form['replace_tokens'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Replace tokens before executing the action'),
        '#weight' => -200,
        '#default_value' => $this->settings['replace_tokens'] ?? FALSE,
      ];
    }
    $access_options = [
      '_none' => $this->t('- Select -'),
      'check_access' => $this->t('Always check access, only execute when granted'),
      'bypass_access' => $this->t('No access check, always execute'),
    ];
    $form['access_check'] = [
      '#type' => 'select',
      '#title' => $this->t('Access check'),
      '#description' => $this->t('Select if before execution it should be checked, whether the current user has the permission to execute this action.'),
      '#required' => TRUE,
      '#options' => $access_options,
      '#default_value' => $this->settings['access_check'] ?? '_none',
      '#empty_value' => '_none',
      '#weight' => 10,
    ];
    $form['action'] = ['#weight' => 20];
    if ($action instanceof PluginFormInterface) {
      $form['token_info'] = [
        '#type' => 'container',
        'allowed_text' => [
          '#markup' => $this->t('Tokens are allowed.') . '&nbsp;',
          '#weight' => 10,
        ],
        '#weight' => -100,
        '#states' => [
          'visible' => [
            ':input[name="task[settings][replace_tokens]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      if (isset($this->configuration['entity_type_id']) && $this->moduleHandler->moduleExists('token')) {
        $form['token_info']['browser'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => [$this->getTokenTypeForEntityType($this->configuration['entity_type_id'])],
          '#dialog' => TRUE,
          '#weight' => 10,
        ];
      }
      else {
        $form['token_info']['no_browser'] = [
          '#markup' => $this->t('To get a list of available tokens, install the <a target="_blank" rel="noreferrer noopener" href=":drupal-token" target="blank">contrib Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']),
          '#weight' => 10,
        ];
      }
      $action_form_state = SubformState::createForSubform($form['action'], $form, $form_state);
      $form['action'] = $action->buildConfigurationForm($form['action'], $action_form_state);
    }
    else {
      $form['action']['no_form'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('This action provides no configuration.') . '</p>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $action = $this->getAction();
    if ($action instanceof PluginFormInterface) {
      $action_form_state = SubformState::createForSubform($form['action'], $form, $form_state);
      $action->validateConfigurationForm($form['action'], $action_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $action = $this->getAction();
    if ($action instanceof PluginFormInterface) {
      $action_form_state = SubformState::createForSubform($form['action'], $form, $form_state);
      $action->submitConfigurationForm($form['action'], $action_form_state);
    }
    $this->settings['replace_tokens'] = (bool) $form_state->getValue('replace_tokens', FALSE);
    $this->settings['access_check'] = $form_state->getValue('access_check');
    $this->settings['action'] = $action instanceof ConfigurableInterface ? $action->getConfiguration() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function doOperate(ContentEntityInterface $entity): void {
    $action = $this->getAction();
    $access_check = $this->settings['access_check'] ?? 'check_access';

    if (($access_check === 'bypass_access') || $action->access($entity)) {
      // Pass through token data, and replace tokens in the configuration
      // beforehand when configured accordingly.
      if ($action instanceof ConfigurableInterface) {
        $original_configuration = $action->getConfiguration();
        $overridden_configuration = $original_configuration;

        $token_data = [$this->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity];
        if ($this->entityFromStack && ($this->entityFromStack->getEntityTypeId() !== $entity->getEntityTypeId())) {
          $token_data[$this->getTokenTypeForEntityType($this->entityFromStack->getEntityTypeId())] = $this->entityFromStack;
        }

        // Gracefully override configuration values with token data.
        foreach ($token_data as $type => $data) {
          if (!isset($overridden_configuration[$type]) || is_object($overridden_configuration[$type])) {
            $overridden_configuration[$type] = $data;
          }
        }

        if ($this->shouldReplaceTokens($action)) {
          array_walk_recursive($overridden_configuration, function (&$value) use (&$token_data) {
            if (is_string($value) && !empty($value)) {
              $value = $this->tokenReplace($value, $token_data);
            }
          });
        }

        $action->setConfiguration($overridden_configuration);
      }

      // Some actions want to unconditionally save the according entity and thus
      // would lead to a task recursion. As the saving step would most usually
      // happen as a very last step of the action, we may disable the error
      // logging of detected task recursion at this point.
      $log_task_recursion = FlowTaskQueue::$logTaskRecursion;
      FlowTaskQueue::$logTaskRecursion = FALSE;
      try {
        $action->executeMultiple([$entity]);
      }
      finally {
        FlowTaskQueue::$logTaskRecursion = $log_task_recursion;
        if ($action instanceof ConfigurableInterface) {
          $action->setConfiguration($original_configuration);
        }
      }
    }
  }

  /**
   * Get the action plugin instance.
   *
   * @return \Drupal\Core\Action\ActionInterface
   *   The action plugin instance.
   */
  public function getAction(): ActionInterface {
    return $this->action;
  }

  /**
   * Set the action plugin instance.
   *
   * @param \Drupal\Core\Action\ActionInterface $action
   *   The action plugin instance.
   */
  public function setAction(ActionInterface $action): void {
    $this->action = $action;
  }

  /**
   * Determines whether tokens should be replaced before executing the action.
   *
   * @param \Drupal\Core\Action\ActionInterface $action
   *   The action plugin instance.
   *
   * @return bool
   *   Returns TRUE if tokens should be replaced, FALSE otherwise.
   */
  protected function shouldReplaceTokens(ActionInterface $action): bool {
    if (TRUE === ($this->settings['replace_tokens'] ?? FALSE)) {
      // Do a sensitive check whether it really makes sense to replace tokens.
      // It does not make sense when the action itself is subject to replace
      // tokens on its own.
      return \Closure::fromCallable(function () {
        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
          $property_name = $property->getName();
          if (isset($this->$property_name) && ($this->$property_name instanceof Token)) {
            return FALSE;
          }
        }
        return TRUE;
      })->call($action);
    }
    return FALSE;
  }

}
