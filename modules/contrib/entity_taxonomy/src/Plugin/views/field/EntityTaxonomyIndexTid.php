<?php

namespace Drupal\entity_taxonomy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_taxonomy\VocabularyStorageInterface;

/**
 * Field handler to display all entity_taxonomy terms of a node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_taxonomy_index_tid")
 */
class EntityTaxonomyIndexTid extends PrerenderList {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\entity_taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Constructs a EntityTaxonomyIndexTid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('entity_taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // @todo: Wouldn't it be possible to use $this->base_table and no if here?
    $this->additional_fields['entity_id'] = [
      'table' => $view->storage->get('base_table'),
      'field' => $view->storage->get('base_field')
    ];
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_entity_taxonomy'] = ['default' => TRUE];
    $options['limit'] = ['default' => FALSE];
    $options['vids'] = ['default' => []];

    return $options;
  }

  /**
   * Provide "link to term" option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity_taxonomy'] = [
      '#title' => $this->t('Link this field to its term page'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity_taxonomy']),
    ];

    $entity_list_def = \Drupal::entityTypeManager()->getDefinitions();
    $entity_list = array_keys($entity_list_def);
    $not_categorized = [
      'entity_taxonomy', 'taxonomy', 'block_content_type', 'comment_type', 'editor', 'entity_taxonomy_vocabulary', 'field_config', 'field_storage_config', 'filter_format', 'image_style', 'menu_link_content', 'node_type', 'path_alias', 'rdf_mapping', 'search_page', 'shortcut_set', 'shortcut', 'menu', 'action', 'tour', 'user_role', 'view', 'entity_view_mode', 'entity_view_display', 'entity_form_mode', 'entity_form_display', 'base_field_override', 'date_format',
    ];
    $entities = array_diff($entity_list, $not_categorized);

    $form['entity'] = [
      '#type' => 'entity',
      '#title' => $this->t('Select entityType'),
      '#options' => $entities,
      '#default_value' => $this->options['entity'],
    ];

    $form['limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
    ];

    $options = [];
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vids'],
      '#states' => [
        'visible' => [
          ':input[name="options[limit]"]' => ['checked' => TRUE],
        ],
      ],

    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Add this term to the query.
   */
  public function query() {
    $this->addAdditionalFields();
  }

  public function preRender(&$values) {
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    $this->field_alias = $this->aliases['entity_id'];
    $entity_ids = [];
    foreach ($values as $result) {
      if (!empty($result->{$this->aliases['entity_id']})) {
        $entity_ids[] = $result->{$this->aliases['entity_id']};
      }
    }

    if ($entity_ids) {
      $vocabs = array_filter($this->options['vids']);
      if (empty($this->options['limit'])) {
        $vocabs = [];
      }
      $result = \Drupal::entityTypeManager()->getStorage('entity_taxonomy_term')->entity_type_id($this->options['entity'],$entity_ids, $vocabs);

      foreach ($result as $node_nid => $data) {
        foreach ($data as $tid => $term) {
          $this->items[$node_nid][$tid]['name'] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
          $this->items[$node_nid][$tid]['tid'] = $tid;
          $this->items[$node_nid][$tid]['vocabulary_vid'] = $term->bundle();
          $this->items[$node_nid][$tid]['vocabulary'] = $vocabularies[$term->bundle()]->label();

          if (!empty($this->options['link_to_entity_taxonomy'])) {
            $this->items[$node_nid][$tid]['make_link'] = TRUE;
            $this->items[$node_nid][$tid]['path'] = 'entity_taxonomy/term/' . $tid;
          }
        }
      }
    }
  }

  public function render_item($count, $item) {
    return $item['name'];
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['{{ ' . $this->options['id'] . '__tid' . ' }}'] = $this->t('The entity_taxonomy term ID for the term.');
    $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $this->t('The entity_taxonomy term name for the term.');
    $tokens['{{ ' . $this->options['id'] . '__vocabulary_vid' . ' }}'] = $this->t('The machine name for the vocabulary the term belongs to.');
    $tokens['{{ ' . $this->options['id'] . '__vocabulary' . ' }}'] = $this->t('The name for the vocabulary the term belongs to.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    foreach (['tid', 'name', 'vocabulary_vid', 'vocabulary'] as $token) {
      $tokens['{{ ' . $this->options['id'] . '__' . $token . ' }}'] = isset($item[$token]) ? $item[$token] : '';
    }
  }

}
