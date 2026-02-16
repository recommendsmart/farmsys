<?php

namespace Drupal\yasm_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yasm\Services\YasmBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract yasm blocks base class.
 */
abstract class YasmBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The yasm builder service.
   *
   * @var \Drupal\yasm\Services\YasmBuilderInterface
   */
  protected $yasmBuilder;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['block_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Block style'),
      '#default_value' => isset($config['block_style']) ? $config['block_style'] : 'item_list',
      '#required' => TRUE,
      '#options' => [
        'item_list' => $this->t('List'),
        'cards'     => $this->t('Cards'),
        'counters'  => $this->t('Animated counters'),
      ],
    ];
    $form['with_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefix rows withs icons'),
      '#default_value' => isset($config['with_icons']) ? $config['with_icons'] : TRUE,
    ];
    $form['attach_fontawesome'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attach fontawesome library'),
      '#description' => $this->t("Only needed if you prefix rows with icons and your theme doesn't include fontawesome library. Check if you doesn't see the icons."),
      '#default_value' => isset($config['attach_fontawesome']) ? $config['attach_fontawesome'] : FALSE,
      '#states' => [
        'visible' => [
          ':input[name="settings[with_icons]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['block_style']        = $values['block_style'];
    $this->configuration['with_icons']         = $values['with_icons'];
    $this->configuration['attach_fontawesome'] = $values['attach_fontawesome'];
  }

  /**
   * Bluid block card markup output.
   *
   * @param string $label
   *   The count label.
   * @param int $count
   *   The value count.
   * @param string $picto
   *   The fontawesome pictor class property used.
   * @param bool $list
   *   Render as a list style or not.
   *
   * @return array
   *   The theme render array.
   */
  public function buildBlockItem(string $label, int $count, string $picto = '', $list = FALSE) {
    return [
      '#theme' => $list ? 'yasm_item' : 'yasm_card',
      '#icon'  => $picto,
      '#label' => $label,
      '#count' => $count,
    ];
  }

  /**
   * Bluid block card markup output.
   *
   * @param array $cards
   *   The render array cards to add.
   *
   * @return array
   *   The theme render array.
   */
  public function buildBlockColumns(array $cards) {
    return [
      '#theme' => 'yasm_columns',
      '#items' => $cards,
      '#attributes' => [
        'class' => ['yasm-cards'],
      ],
      '#content_attributes' => [
        'class' => $this->yasmBuilder->getColumnClass(count($cards)),
      ],
      '#attached' => ['library' => ['yasm/global']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, YasmBuilderInterface $yasm_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->yasmBuilder = $yasm_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('yasm.builder')
    );
  }

}
