<?php

namespace Drupal\flow\Plugin\flow\Qualifier;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Helpers\EntityFieldManagerTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\TokenTrait;
use Drupal\flow\Plugin\FlowQualifierBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Qualifies an entity when matching token replacement values.
 *
 * @FlowQualifier(
 *   id = "token",
 *   label = @Translation("Token matching content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Qualifier\TokenDeriver"
 * )
 */
class Token extends FlowQualifierBase implements PluginFormInterface {

  use EntityFieldManagerTrait;
  use EntityTypeManagerTrait;
  use ModuleHandlerTrait;
  use StringTranslationTrait;
  use TokenTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Qualifier\Token $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    $instance->setEntityFieldManager($container->get(self::$entityFieldManagerServiceName));
    $instance->setToken($container->get(self::$tokenServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function qualifies(ContentEntityInterface $entity): bool {
    $token_data = [
      $this->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];
    $left = $this->settings['left'] ?? '';
    if ($left !== '') {
      $left = (string) $this->tokenReplace($left, $token_data);
    }
    $right = $this->settings['right'] ?? '';
    if ($right !== '') {
      $right = (string) $this->tokenReplace($right, $token_data);
    }
    $comparison = explode(':', ($this->settings['comparison'] ?? 'i_equals'));

    if (trim($right === '') && empty(array_intersect(['empty', 'equals', 'i_equals'], $comparison))) {
      return FALSE;
    }

    foreach ($comparison as $method) {
      switch ($method) {

        case 'empty':
          $result = empty($left);
          break;

        case 'equals':
          $result = (strnatcmp($left, $right) === 0);
          break;

        case 'i_equals':
          $result = (strnatcasecmp($left, $right) === 0);
          break;

        case 'contains':
          $result = (mb_strpos($left, $right) !== FALSE);
          break;

        case 'i_contains':
          $result = (mb_strpos(mb_strtolower($left), mb_strtolower($right)) !== FALSE);
          break;

        case 'greater':
          $result = (strnatcasecmp($left, $right) > 0);
          break;

        case 'less':
          $result = (strnatcasecmp($left, $right) < 0);
          break;

        case 'least':
          $result = (strnatcasecmp($left, $right) >= 0);
          break;

        case 'most':
          $result = (strnatcasecmp($left, $right) <= 0);
          break;

        case 'begins':
          $result = (mb_strpos($left, $right) === 0);
          break;

        case 'i_begins':
          $result = (mb_strpos(mb_strtolower($left), mb_strtolower($right)) === 0);
          break;

        case 'ends':
          $result = (mb_substr($left, -mb_strlen($right)) === $right);
          break;

        case 'i_ends':
          $result = (mb_substr(mb_strtolower($left), -mb_strlen(mb_strtolower($right))) === mb_strtolower($right));
          break;

        case 'length_equals':
          if (!ctype_digit(trim($right))) {
            return FALSE;
          }
          $result = (mb_strlen($left) === ((int) trim($right)));
          break;

        case 'length_greater':
          if (!ctype_digit(trim($right))) {
            return FALSE;
          }
          $result = (mb_strlen($left) > ((int) trim($right)));
          break;

        case 'length_less':
          if (!ctype_digit(trim($right))) {
            return FALSE;
          }
          $result = (mb_strlen($left) < ((int) trim($right)));
          break;

        case 'length_most':
          if (!ctype_digit(trim($right))) {
            return FALSE;
          }
          $result = (mb_strlen($left) <= ((int) trim($right)));
          break;

        case 'length_least':
          if (!ctype_digit(trim($right))) {
            return FALSE;
          }
          $result = (mb_strlen($left) >= ((int) trim($right)));
          break;

      }
    }

    if (!isset($result)) {
      return FALSE;
    }

    return in_array('not', $comparison, TRUE) ? !$result : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token_info'] = [
      '#type' => 'container',
      'allowed_text' => [
        '#markup' => $this->t('Tokens are allowed.') . '&nbsp;',
        '#weight' => 10,
      ],
      '#weight' => -100,
    ];
    $entity_type_id = $this->getEntityTypeId();
    if (isset($entity_type_id) && $this->moduleHandler->moduleExists('token')) {
      $form['token_info']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->getTokenTypeForEntityType($entity_type_id)],
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
    $form['token_table'] = [
      '#attributes' => ['id' => Html::getUniqueId('flow-token-table')],
      '#type' => 'table',
      '#header' => [
        $this->t('Left value'),
        $this->t('Comparison'),
        $this->t('Right value'),
      ],
      '#weight' => 10,
    ];
    $form['token_table'][0]['left'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Left value'),
      '#title_display' => 'invisible',
      '#default_value' => $this->settings['left'] ?? '',
      '#weight' => 10,
    ];
    $form['token_table'][0]['comparison'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison'),
      '#title_display' => 'invisible',
      '#default_value' => $this->settings['comparison'] ?? 'i_equals',
      '#options' => [
        'empty' => $this->t('Is empty'),
        'not:empty' => $this->t('Is not empty'),
        'equals' => $this->t('Equals (case-sensitive)'),
        'not:equals' => $this->t('Does not equal (case-sensitive)'),
        'i_equals' => $this->t('Equals (not case-sensitive)'),
        'not:i_equals' => $this->t('Does not equal (not case-sensitive)'),
        'contains' => $this->t('Contains (case-sensitive)'),
        'not:contains' => $this->t('Does not contain (case-sensitive)'),
        'i_contains' => $this->t('Contains (not case-sensitive)'),
        'not:i_contains' => $this->t('Does not contain (not case-sensitive)'),
        'greater' => $this->t('Greater than'),
        'less' => $this->t('Less than'),
        'least' => $this->t('At least'),
        'most' => $this->t('At most'),
        'begins' => $this->t('Begins with (case-sensitive)'),
        'not:begins' => $this->t('Does not begin with (case-sensitive)'),
        'i_begins' => $this->t('Begins with (not case-sensitive)'),
        'not:i_begins' => $this->t('Does not begin with (not case-sensitive)'),
        'ends' => $this->t('Ends with (case-sensitive)'),
        'not:ends' => $this->t('Does not end with (case-sensitive)'),
        'i_ends' => $this->t('Ends with (not case-sensitive)'),
        'not:i_ends' => $this->t('Does not end with (not case-sensitive)'),
        'length_equals' => $this->t('Length (no. characters) equals'),
        'not:length_equals' => $this->t('Length (no. characters) does not equal'),
        'length_greater' => $this->t('Length (no. characters) greater than'),
        'length_less' => $this->t('Length (no. characters) less than'),
        'length_most' => $this->t('Length (no. characters) at most'),
        'length_least' => $this->t('Length (no. characters) at least'),
      ],
      '#required' => TRUE,
      '#weight' => 20,
    ];
    $form['token_table'][0]['right'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Right value'),
      '#title_display' => 'invisible',
      '#default_value' => $this->settings['right'] ?? '',
      '#weight' => 30,
      '#states' => [
        'disabled' => [
          ['select[name="qualifier[settings][token_table][0][comparison]"]' => ['value' => 'empty']],
          ['select[name="qualifier[settings][token_table][0][comparison]"]' => ['value' => 'not:empty']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->settings['left'] = $form_state->getValue(['token_table', 0, 'left'], '');
    $this->settings['right'] = $form_state->getValue(['token_table', 0, 'right'], '');
    $this->settings['comparison'] = $form_state->getValue(['token_table', 0, 'comparison'], 'i_equals');
    if (trim($this->settings['left']) === '') {
      $this->settings['left'] = '';
    }
    if ((trim($this->settings['right']) === '') || in_array($this->settings['comparison'], ['empty', 'not:empty'], TRUE)) {
      $this->settings['right'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $left = $this->settings['left'] ?? '';
    $right = $this->settings['right'] ?? '';
    $field_config_storage = $this->getEntityTypeManager()->getStorage('field_config');
    $entity_type_id = $this->getEntityTypeId();
    $bundle = $this->getEntityBundle();
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $definition) {
      if (!strpos($left, $field_name) && !strpos($right, $field_name)) {
        continue;
      }
      if ($field_config = $field_config_storage->load($entity_type_id . '.' . $bundle . '.' . $field_name)) {
        $dependencies[$field_config->getConfigDependencyKey()][] = $field_config->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

}
