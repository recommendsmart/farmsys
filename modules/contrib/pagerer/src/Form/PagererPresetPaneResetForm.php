<?php

namespace Drupal\pagerer\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\pagerer\Plugin\PagererStyleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Pagerer presets' panes.
 */
class PagererPresetPaneResetForm extends EntityConfirmFormBase {

  /**
   * Pagerer pane label literals.
   *
   * @var array
   */
  protected $paneLabels;

  /**
   * Pagerer pane being edited.
   *
   * @var string
   */
  protected $pane;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The plugin manager for Pagerer style plugins.
   *
   * @var \Drupal\pagerer\Plugin\PagererStyleManager
   */
  protected $styleManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the form object.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   * @param \Drupal\pagerer\Plugin\PagererStyleManager $style_manager
   *   The plugin manager for Pagerer style plugins.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PagerManagerInterface $pager_manager, PagererStyleManager $style_manager, MessengerInterface $messenger) {
    $this->pagerManager = $pager_manager;
    $this->styleManager = $style_manager;
    $this->messenger = $messenger;
    $this->paneLabels = [
      'left' => $this->t('left'),
      'center' => $this->t('center'),
      'right' => $this->t('right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pager.manager'),
      $container->get('pagerer.style.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pane = NULL) {
    $this->pane = $pane;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      "Reset @pane pane configuration?",
      [
        '@pane' => $this->paneLabels[$this->pane],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $style = $this->entity->getPaneData($this->pane, 'style');
    $plugin_definition = $this->styleManager->getDefinition($style);
    return $this->t(
      "The %pane pane of pager %preset_name will be reset to %style style default configuration.",
      [
        '%preset_name' => $this->entity->label(),
        '%pane' => $this->paneLabels[$this->pane],
        '%style' => !empty($plugin_definition) ? $plugin_definition['short_title'] : NULL,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $style = $this->entity->getPaneData($this->pane, 'style');
    $plugin_definition = $this->styleManager->getDefinition($style);
    $this->entity->setPaneData($this->pane, 'config', []);
    $this->entity->save();
    $this->messenger->addMessage(
      $this->t(
        'The %pane pane configuration has been reset to %style style default configuration.',
        [
          '%style' => !empty($plugin_definition) ? $plugin_definition['short_title'] : NULL,
          '%pane' => $this->paneLabels[$this->pane],
        ]
      ),
      'status'
    );
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
