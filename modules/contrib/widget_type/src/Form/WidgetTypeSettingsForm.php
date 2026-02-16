<?php

namespace Drupal\widget_type\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\widget_type\Entity\WidgetType;
use function array_filter;
use function array_reduce;
use function is_string;
use function reset;

/**
 * Settings to customize the widget types.
 */
class WidgetTypeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['widget_type.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'widget_type.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['#attached'] = array_merge(
      $form['#attached'] ?? [],
      ['library' => ['widget_type/widget_type.admin']]
    );
    $entity_type_manager = \Drupal::entityTypeManager();
    $form['download_assets'] = [
      '#type' => 'details',
      '#title' => $this->t('Download Assets'),
      '#tree' => TRUE,
      '#open' => TRUE,
      '#attributes' => ['class' => ['widget-types-dl-assets']],
    ];
    $form['download_assets']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Widgets put JS, CSS, images, SVGs, etc. on the page. The default behavior is to load the widgets from an external source, but by configuring them you can download the assets and have Drupal serve them.'),
    ];
    $all_widget_types = $entity_type_manager
      ->getStorage('widget_type')
      ->loadMultiple();
    $options = array_reduce($all_widget_types, static function (array $carry, WidgetType $widget_type) {
      return array_merge($carry, [$widget_type->getRemoteId() => $widget_type->getName()]);
    }, []);
    natcasesort($options);
    $configuration = $this->config('widget_type.settings');
    $allowed = $configuration->get('download_assets.allowed_list');
    $disallowed = $configuration->get('download_assets.disallowed_list');
    if (empty($allowed) && empty($disallowed)) {
      $summary = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('None of the widget definitions will have their assets downloaded and served by Drupal.'),
      ];
    }
    elseif (!empty($allowed)) {
      $summary = [
        '#theme' => 'item_list',
        '#items' => array_intersect_key($options, array_flip($allowed)),
        '#title' => $this->t('Drupal is serving the assets of the widgets of these types:'),
      ];
    }
    else {
      $summary = [
        '#theme' => 'item_list',
        '#items' => array_intersect_key($options, array_flip($disallowed)),
        '#title' => $this->t('Drupal is serving all the assets except for the widgets of these types:'),
      ];
    }
    $form['download_assets']['summary'] = $summary;
    $form['download_assets']['allowed_list'] = [
      '#title' => $this->t('Allowed list'),
      '#description' => $this->t('Select all the widget types that should be enabled.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $allowed,
    ];
    $form['download_assets']['disallowed_list'] = [
      '#title' => $this->t('Disallowed list'),
      '#description' => $this->t('Select all the widget types that should never be enabled.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $disallowed,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the configuration.
    $name = $this->getEditableConfigNames();
    $config_name = reset($name);
    $config = $this->configFactory()->getEditable($config_name);
    $values = $form_state->getValue('download_assets');
    $values['allowed_list'] = array_filter($values['allowed_list']);
    $values['disallowed_list'] = array_filter($values['disallowed_list']);
    $config->set('download_assets', $values);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValue('download_assets');
    if (!empty(array_filter($values['allowed_list'])) && !empty(array_filter($values['disallowed_list']))) {
      $form_state->setError($form['download_assets']['allowed_list'], $this->t('You cannot select widget types both in the allowed and disallowed lists.'));
      $form_state->setError($form['download_assets']['disallowed_list'], $this->t('You cannot select widget types both in the allowed and disallowed lists.'));
    }
    $all_strings = static function (array $values): bool {
      return array_reduce(array_filter($values), static function (bool $carry, $val) {
        return $carry && is_string($val);
      }, TRUE);
    };
    if (!$all_strings($values['allowed_list'])) {
      $form_state->setError($form['download_assets']['allowed_list'], $this->t('Widget type names should be strings.'));
    }
    if (!$all_strings($values['disallowed_list'])) {
      $form_state->setError($form['download_assets']['disallowed_list'], $this->t('Widget type names should be strings.'));
    }
  }

}
