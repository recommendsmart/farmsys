<?php

namespace Drupal\config_terms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\config_terms\Entity\VocabInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides terms overview form for a config terms vocab.
 */
class OverviewTerms extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The term storage handler.
   *
   * @var \Drupal\config_terms\TermStorageInterface
   */
  protected $storageController;

  /**
   * Constructs an OverviewTerms object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->storageController = $entity_type_manager->getStorage('config_terms_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_terms_overview_terms';
  }

  /**
   * Form constructor.
   *
   * Display a tree of all the terms in a vocab, with options to edit
   * each one. The form is made drag and drop by the theme function.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\config_terms\Entity\VocabInterface $config_terms_vocab
   *   The vocab to display the overview form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabInterface $config_terms_vocab = NULL) {
    // @todo Remove global vars when https://www.drupal.org/node/2044435 is in.
    global $pager_page_array, $pager_total, $pager_total_items;

    $form_state->set(['config_terms', 'vocab'], $config_terms_vocab);
    $parent_fields = FALSE;

    $page = $this->getRequest()->query->get('page') ?: 0;
    // Number of terms per page.
    $page_increment = $this->config('config_terms.settings')->get('terms_per_page_admin');
    // Elements shown on this page.
    $page_entries = 0;
    // Elements at the root level before this page.
    $before_entries = 0;
    // Elements at the root level after this page.
    $after_entries = 0;
    // Elements at the root level on this page.
    $root_entries = 0;

    // Terms from previous and next pages are shown if the term tree would have
    // been cut in the middle. Keep track of how many extra terms we show on
    // each page of terms.
    $back_step = NULL;
    $forward_step = 0;

    // An array of the terms to be displayed on this page.
    $current_page = [];

    $delta = 0;
    $term_deltas = [];
    $tree = $this->storageController->loadTree($config_terms_vocab->id());
    $tree_index = 0;
    $complete_tree = NULL;

    do {
      // In case this tree is completely empty.
      if (empty($tree[$tree_index])) {
        break;
      }
      $delta++;
      // Count entries before the current page.
      if ($page && ($page * $page_increment) > $before_entries && !isset($back_step)) {
        $before_entries++;
        continue;
      }
      // Count entries after the current page.
      elseif ($page_entries > $page_increment && isset($complete_tree)) {
        $after_entries++;
        continue;
      }

      // Do not let a term start the page that is not at the root.
      $term = $tree[$tree_index];
      if ($term->getDepth() > 0 && !isset($back_step)) {
        $back_step = 0;
        while ($pterm = $tree[--$tree_index]) {
          $before_entries--;
          $back_step++;
          if ($pterm->getDepth() == 0) {
            $tree_index--;
            // Jump back to the start of the root level parent.
            continue 2;
          }
        }
      }
      $back_step = isset($back_step) ? $back_step : 0;

      // Continue rendering the tree until we reach the a new root item.
      if ($page_entries >= $page_increment + $back_step + 1 && $term->getDepth() == 0 && $root_entries > 1) {
        $complete_tree = TRUE;
        // This new item at the root level is the first item on the next page.
        $after_entries++;
        continue;
      }
      if ($page_entries >= $page_increment + $back_step) {
        $forward_step++;
      }

      // Finally, if we've gotten down this far, we're rendering a term on this
      // page.
      $page_entries++;
      $term_deltas[$term->id()] = isset($term_deltas[$term->id()]) ? $term_deltas[$term->id()] + 1 : 0;
      $key = 'tid:' . $term->id() . ':' . $term_deltas[$term->id()];

      // Keep track of the first term displayed on this page.
      if ($page_entries == 1) {
        $form['#first_tid'] = $term->id();
      }
      // Keep a variable to make sure at least 2 root elements are displayed.
      $parents = $term->getParents();
      if ($parents['0'] == 0) {
        $root_entries++;
      }
      $current_page[$key] = $term;
    } while (isset($tree[++$tree_index]));

    // Because we didn't use a pager query, set the necessary pager variables.
    $total_entries = $before_entries + $page_entries + $after_entries;
    $pager_total_items[0] = $total_entries;
    $pager_page_array[0] = $page;
    $pager_total[0] = ceil($total_entries / $page_increment);

    // If this form was already submitted once, it's probably hit a validation
    // error. Ensure the form is rebuilt in the same order as the user
    // submitted.
    $user_input = $form_state->getUserInput();
    if (!empty($user_input)) {
      // Update our form with the new order.
      foreach ($current_page as $key => $term) {
        // Verify this is a term for the current page and set at the current
        // depth.
        if (isset($user_input['terms'][$key]['term']['tid']) && is_numeric($user_input['terms'][$key]['term']['tid'])) {
          $current_page[$key]->setDepth($user_input['terms'][$key]['term']['depth']);
        }
        else {
          unset($current_page[$key]);
        }
      }
    }

    $errors = $form_state->getErrors();
    $destination = $this->getDestinationArray();
    $row_position = 0;
    // Build the actual form.
    $form['terms'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No terms available. <a href=":link">Add term</a>.', [
        ':link' => Url::fromRoute('entity.config_terms_term.add_form', [
          'config_terms_vocab' => $config_terms_vocab->id(),
        ])->toString(),
      ]),
      '#attributes' => [
        'id' => 'config_terms',
      ],
    ];
    foreach ($tree as $key => $term) {
      /* @var $term \Drupal\Core\Entity\EntityInterface */
      $form['terms'][$key]['#term'] = $term;
      $indentation = [];
      if ($term->getDepth() > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $term->getDepth(),
        ];
      }
      $form['terms'][$key]['term'] = [
        '#prefix' => !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '',
        '#type' => 'link',
        '#title' => $term->getName(),
        '#url' => $term->toUrl(),
      ];
      if (count($tree) > 1) {
        $parent_fields = TRUE;
        $form['terms'][$key]['term']['tid'] = [
          '#type' => 'hidden',
          '#value' => $term->id(),
          '#attributes' => [
            'class' => ['term-id'],
          ],
        ];
        $parents = $term->getParents();
        $form['terms'][$key]['term']['parent'] = [
          '#type' => 'hidden',
          // Yes, default_value on a hidden. It needs to be changeable by the
          // javascript.
          '#default_value' => $parents['0'],
          '#attributes' => [
            'class' => ['term-parent'],
          ],
        ];
        $form['terms'][$key]['term']['depth'] = [
          '#type' => 'hidden',
          // Same as above, the depth is modified by javascript, so it's a
          // default_value.
          '#default_value' => $term->getDepth(),
          '#attributes' => [
            'class' => ['term-depth'],
          ],
        ];
      }
      $form['terms'][$key]['weight'] = [
        '#type' => 'weight',
        '#delta' => $delta,
        '#title' => $this->t('Weight for added term'),
        '#title_display' => 'invisible',
        '#default_value' => $term->getWeight(),
        '#attributes' => [
          'class' => ['term-weight'],
        ],
      ];
      $operations = [
        'edit' => [
          'title' => $this->t('Edit'),
          'query' => $destination,
          'url' => $term->toUrl('edit-form'),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'query' => $destination,
          'url' => $term->toUrl('delete-form'),
        ],
      ];
      $form['terms'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $form['terms'][$key]['#attributes']['class'] = [];
      if ($parent_fields) {
        $form['terms'][$key]['#attributes']['class'][] = 'draggable';
      }

      // Add classes that mark which terms belong to previous and next pages.
      if ($row_position < $back_step || $row_position >= $page_entries - $forward_step) {
        $form['terms'][$key]['#attributes']['class'][] = 'config-terms-term-preview';
      }

      if ($row_position !== 0 && $row_position !== count($tree) - 1) {
        if ($row_position == $back_step - 1 || $row_position == $page_entries - $forward_step - 1) {
          $form['terms'][$key]['#attributes']['class'][] = 'config-terms-term-divider-top';
        }
        elseif ($row_position == $back_step || $row_position == $page_entries - $forward_step) {
          $form['terms'][$key]['#attributes']['class'][] = 'config-terms-term-divider-bottom';
        }
      }

      // Add an error class if this row contains a form error.
      foreach (array_keys($errors) as $error_key) {
        if (strpos($error_key, $key) === 0) {
          $form['terms'][$key]['#attributes']['class'][] = 'error';
        }
      }
      $row_position++;
    }

    if ($parent_fields) {
      $form['terms']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'term-parent',
        'subgroup' => 'term-parent',
        'source' => 'term-id',
        'hidden' => FALSE,
      ];
      $form['terms']['#tabledrag'][] = [
        'action' => 'depth',
        'relationship' => 'group',
        'group' => 'term-depth',
        'hidden' => FALSE,
      ];
      $form['terms']['#attached']['library'][] = 'config_terms/drupal.config_terms';
      $form['terms']['#attached']['drupalSettings']['config_terms'] = [
        'backStep' => $back_step,
        'forwardStep' => $forward_step,
      ];
    }
    $form['terms']['#tabledrag'][] = [
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'term-weight',
    ];

    if (count($tree) > 1) {
      $form['actions'] = ['#type' => 'actions', '#tree' => FALSE];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
      $form['actions']['reset_alphabetical'] = [
        '#type' => 'submit',
        '#submit' => ['::submitReset'],
        '#value' => $this->t('Reset to alphabetical'),
      ];
    }

    $form['pager_pager'] = ['#type' => 'pager'];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * Rather than using a textfield or weight field, this form depends entirely
   * upon the order of form elements on the page to determine new weights.
   *
   * Because there might be hundreds or thousands of config_terms terms that
   * need to be ordered, terms are weighted from 0 to the number of terms in the
   * vocab, rather than the standard -10 to 10 scale. Numbers are sorted
   * lowest to highest, but are not necessarily sequential. Numbers may be
   * skipped when a term has children so that reordering is minimal when a child
   * is added or removed from a term.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Sort term order based on weight.
    uasort($form_state->getValue('terms'), ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    $vocab = $form_state->get(['config_terms', 'vocab']);

    // Update the current hierarchy type as we go.
    $hierarchy = VocabInterface::HIERARCHY_DISABLED;

    $changed_terms = [];
    $tree = $this->storageController->loadTree($vocab->id());

    if (empty($tree)) {
      return;
    }

    // Build a list of all terms that need to be updated on previous pages.
    $weight = 0;
    $term = $tree[0];
    while ($term->id() != $form['#first_tid']) {
      $parents = $term->getParents();
      if ($parents['0'] == 0 && $term->getWeight() != $weight) {
        $term->setWeight($weight);
        $changed_terms[$term->id()] = $term;
      }
      $weight++;
      $hierarchy = $parents['0'] != 0 ? VocabInterface::HIERARCHY_SINGLE : $hierarchy;
      $term = $tree[$weight];
    }

    // Renumber the current page weights and assign any new parents.
    $level_weights = [];
    foreach ($form_state->getValue('terms') as $tid => $values) {
      if (isset($form['terms'][$tid]['#term'])) {
        $term = $form['terms'][$tid]['#term'];
        // Give terms at the root level a weight in sequence with terms on
        // previous pages.
        if ($values['term']['parent'] == '0' && $term->getWeight() != $weight) {
          $term->setWeight($weight);
          $changed_terms[$term->id()] = $term;
        }
        // Terms not at the root level can safely start from 0 because they're
        // all on this page.
        elseif ($values['term']['parent'] > 0) {
          $level_weights[$values['term']['parent']] = isset($level_weights[$values['term']['parent']]) ? $level_weights[$values['term']['parent']] + 1 : 0;
          if ($level_weights[$values['term']['parent']] != $term->getWeight()) {
            $term->setWeight($level_weights[$values['term']['parent']]);
            $changed_terms[$term->id()] = $term;
          }
        }
        // Update any changed parents.
        $parents = $term->getParents();
        if ($values['term']['parent'] != $parents['0']) {
          $term->setParents([$values['term']['parent']]);
          $changed_terms[$term->id()] = $term;
        }
        $hierarchy = $parents['0'] != 0 ? VocabInterface::HIERARCHY_SINGLE : $hierarchy;
        $weight++;
      }
    }

    // Build a list of all terms that need to be updated on following pages.
    $weight_max = count($tree);
    while ($weight < $weight_max) {
      $term = $tree[$weight];
      $parents = $term->getParents();
      if ($parents['0'] == 0 && $term->getWeight() != $weight) {
        $term->setParents([$parents['0']]);
        $term->setWeight($weight);
        $changed_terms[$term->id()] = $term;
      }
      $hierarchy = $parents['0'] != 0 ? VocabInterface::HIERARCHY_SINGLE : $hierarchy;
      $weight++;
    }

    // Save all updated terms.
    foreach ($changed_terms as $term) {
      $term->save();
    }

    // Update the vocab hierarchy to flat or single hierarchy.
    if ($vocab->getHierarchy() != $hierarchy) {
      $vocab->setHierarchy($hierarchy);
      $vocab->save();
    }
    $this->messenger()->addMessage(
      $this->t('The configuration options have been saved.')
    );
  }

  /**
   * Redirects to confirmation form for the reset action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitReset(array &$form, FormStateInterface $form_state) {
    /* @var $vocab \Drupal\config_terms\Entity\VocabInterface */
    $vocab = $form_state->get(['config_terms', 'vocab']);
    $form_state->setRedirectUrl($vocab->toUrl('reset-form'));
  }

}
