<?php

namespace Drupal\subgroup\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the Subgroup module.
 */
class SubgroupSettingsForm extends FormBase {

  /**
   * A list of group type IDs that do not belong to a tree.
   *
   * @var string[]
   */
  protected $nonLeafGroupTypeIds;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SubgroupSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subgroup_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // We have a lot of nested forms so this is important.
    $form['#tree'] = TRUE;

    $form['information-tree'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Information about tree structures'),
    ];
    $form['information-tree']['introduction']['#markup'] = $this->t('<p>In order to be able to add groups as subgroups to other groups, you first need to set up a group type tree. This will tell Subgroup which group types will act as ancestors or descendants of other group types. All groups of said group types will then follow this tree structure.</p>');
    $form['information-tree']['limitations'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('A group type may only be part of one tree.'),
        $this->t('Once a group type is part of a tree, it cannot be removed from said tree unless it has no groups.'),
        $this->t('Only extremities may be removed from a tree, i.e.: You cannot remove a child if there is still a grandchild.'),
        $this->t('Removing the last leaf of a tree will also remove the root and therefore the tree.'),
      ],
      '#prefix' => $this->t('<p>To ensure system stability, there are some limitations:</p>'),
    ];

    $form['information-inheritance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Information about role inheritance'),
    ];
    $form['information-inheritance']['introduction']['#markup'] = $this->t('<p>Once you have set up a tree structure, you can start setting up role inheritances. These are the heart and soul of the Subgroup module and define which target role someone inherits in certain groups when they have the required source role in either an ancestor or descendant of said group.</p>');
    $form['information-inheritance']['limitations'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Only member roles are supported; Outsider and Anonymous cannot be used to set up an inheritance.'),
        $this->t('An inheritance link is between ancestors or descendants only; siblings, cousins and the like are excluded.'),
        $this->t('Inheritances do not chain: If "A - Member" grants "B - Member" and "B - Member" grants "C - Member", then a member of A will not automatically become a member of C, unless they are an actual member of B.'),
        $this->t('Because of the above, circular references are allowed: "A - Member" may grant "B - Member" and vice versa.'),
      ],
      '#prefix' => $this->t('<p>Just like tree structures, inheritances follow a few rules:</p>'),
    ];

    $form['create_tree'] = $this->buildTreeCreateForm();

    $storage = $this->entityTypeManager->getStorage('group_type');
    $root_group_type_ids = $storage
      ->getQuery()
      ->condition('third_party_settings.subgroup.' . SUBGROUP_DEPTH_SETTING, 0)
      ->accessCheck(FALSE)
      ->execute();

    if ($root_group_type_ids) {
      foreach ($storage->loadMultiple($root_group_type_ids) as $group_type_id => $group_type) {
        $form["tree_$group_type_id"] = $this->buildTreeOverview($group_type);
      }
    }

    return $form;
  }

  /**
   * Builds the tree creation form.
   *
   * @return array
   *   The form structure.
   */
  protected function buildTreeCreateForm() {
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Create a new tree'),
      '#process' => ['::processSubform'],
    ];

    $group_type_ids = $this->getNonLeafGroupTypeIds();
    if (count($group_type_ids) < 2) {
      $form['not_available']['#markup'] = $this->t('<p>You cannot create a new tree because there are fewer than 2 group types available that are not already part of a tree.</p>');
    }
    else {
      $storage = $this->entityTypeManager->getStorage('group_type');
      foreach ($storage->loadMultiple($group_type_ids) as $group_type_id => $group_type) {
        $options[$group_type_id] = $group_type->label();
      }

      $base = ['#type' => 'select', '#options' => $options, '#required' => TRUE];
      $form['parent'] = $base + ['#title' => $this->t('Parent')];
      $form['child'] = $base + ['#title' => $this->t('Child')];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create tree'),
        '#button_type' => 'primary',
        '#validate' => ['::validateCreateTree'],
        '#submit' => ['::submitCreateTree'],
      ];
    }

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCreateTree(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_parent = array_merge($parents, ['parent']);
    $parents_child = array_merge($parents, ['child']);

    $parent = $form_state->getValue($parents_parent);
    $child = $form_state->getValue($parents_child);
    if (empty($parent) && empty($child)) {
      return;
    }

    if ($parent === $child) {
      $form_state->setErrorByName(implode('][', $parents), $this->t('Parent and child may not be the same.'));
    }

    $child_count = $this->entityTypeManager
      ->getStorage('group')
      ->getQuery()
      ->condition('type', $child)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    if ($child_count) {
      $form_state->setErrorByName(implode('][', $parents_child), $this->t('The child group type already has groups.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCreateTree(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_parent = array_merge($parents, ['parent']);
    $parents_child = array_merge($parents, ['child']);

    $storage = $this->entityTypeManager->getStorage('group_type');
    $parent = $storage->load($form_state->getValue($parents_parent));
    $child = $storage->load($form_state->getValue($parents_child));

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $subgroup_handler->initTree($parent);
    $subgroup_handler->addLeaf($parent, $child);
  }

  /**
   * Builds the tree overview form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The root group type.
   *
   * @return array
   *   The form structure.
   */
  protected function buildTreeOverview(GroupTypeInterface $group_type) {
    $root_id = $group_type->id();
    $root_label = $group_type->label();

    $form = [
      '#type' => 'details',
      '#title' => $this->t('Tree overview for @group_type', ['@group_type' => $root_label]),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Ancestry'),
        ['data' => $this->t('Operations'), 'colspan' => 2],
      ],
    ];
    $form['table'][$root_id]['ancestry']['#markup'] = $this->t('<strong>@group_type</strong>', ['@group_type' => $root_label]);

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');

    $current_parents = $parent_options = [$root_id => $root_label];
    foreach ($subgroup_handler->getDescendants($group_type) as $descendant) {
      $leaf = $subgroup_handler->wrapLeaf($descendant);
      $depth = $leaf->getDepth();
      $current_parents = array_slice($current_parents, 0, $depth, TRUE);

      $id = $descendant->id();
      $label = $descendant->label();

      $form['table'][$id]['ancestry']['#markup'] = $this->t('@ancestors<strong>@group_type</strong>', [
        '@group_type' => $label,
        '@ancestors' => implode(' > ', $current_parents) . ' > ',
      ]);

      $current_parents[$id] = $label;
      $parent_options[$id] = $label;

      $parent_id = array_keys($current_parents)[count($current_parents) - 2];
      $group_content_type_ids = $this->entityTypeManager
        ->getStorage('group_content_type')
        ->getQuery()
        ->condition('group_type', $parent_id)
        ->condition('content_plugin', "subgroup:$id")
        ->execute();

      $form['table'][$id]['configure'] = [
        '#type' => 'link',
        '#title' => $this->t('Configure plugin'),
        '#url' => Url::fromRoute(
          'entity.group_content_type.edit_form',
          ['group_content_type' => reset($group_content_type_ids)],
          ['query' => ['destination' => Url::fromRoute('subgroup.settings')->toString()]]
        ),
        '#attributes' => ['class' => ['button']],
      ];

      if (!$subgroup_handler->hasDescendants($descendant)) {
        $form['table'][$id]['#process'] = ['::processSubform'];
        $form['table'][$id]['remove'] = [
          '#type' => 'submit',
          '#name' => $id . '_remove',
          '#value' => $this->t('Remove'),
          '#validate' => ['::validateRemoveLeaf'],
          '#submit' => ['::submitRemoveLeaf'],
        ];
      }
    }

    $form['add_leaf'] = $this->buildAddLeafForm($parent_options);
    $form['inheritance'] = $this->buildInheritanceOverview($group_type);

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateRemoveLeaf(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $count = $this->entityTypeManager
      ->getStorage('group')
      ->getQuery()
      ->condition('type', end($parents))
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    if ($count) {
      $form_state->setErrorByName(implode('][', $parents), $this->t('The group type still has groups.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRemoveLeaf(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $group_type = $this->entityTypeManager->getStorage('group_type')->load(end($parents));

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $subgroup_handler->removeLeaf($group_type);
  }

  /**
   * Builds the add leaf form.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $parent_options
   *   A list of options for the parent selector.
   *
   * @return array
   *   The form structure.
   */
  protected function buildAddLeafForm(array $parent_options) {
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add another group type'),
      '#process' => ['::processSubform'],
    ];

    if ($group_type_ids = $this->getNonLeafGroupTypeIds()) {
      $form['parent'] = [
        '#type' => 'select',
        '#title' => $this->t('Parent'),
        '#options' => $parent_options,
        '#required' => TRUE,
      ];

      $storage = $this->entityTypeManager->getStorage('group_type');
      foreach ($storage->loadMultiple($group_type_ids) as $group_type_id => $group_type) {
        $options[$group_type_id] = $group_type->label();
      }
      $form['child'] = [
        '#type' => 'select',
        '#title' => $this->t('Child'),
        '#options' => $options,
        '#required' => TRUE,
      ];

      $root_id = array_keys($parent_options)[0];
      $form['submit'] = [
        '#type' => 'submit',
        '#name' => $root_id . '_add_leaf',
        '#value' => $this->t('Add to tree'),
        '#button_type' => 'primary',
        '#validate' => ['::validateAddLeaf'],
        '#submit' => ['::submitAddLeaf'],
      ];
    }
    else {
      $form['not_available']['#markup'] = $this->t('<p>There are no group types available that are not already part of a tree.</p>');
    }

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateAddLeaf(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_child = array_merge($parents, ['child']);

    if (!$child = $form_state->getValue($parents_child)) {
      return;
    }

    $child_count = $this->entityTypeManager
      ->getStorage('group')
      ->getQuery()
      ->condition('type', $child)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    if ($child_count) {
      $form_state->setErrorByName(implode('][', $parents_child), $this->t('The child group type already has groups.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitAddLeaf(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_parent = array_merge($parents, ['parent']);
    $parents_child = array_merge($parents, ['child']);

    $storage = $this->entityTypeManager->getStorage('group_type');
    $parent = $storage->load($form_state->getValue($parents_parent));
    $child = $storage->load($form_state->getValue($parents_child));

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $subgroup_handler->addLeaf($parent, $child);
  }

  /**
   * Builds the inheritance overview form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The root group type.
   *
   * @return array
   *   The form structure.
   */
  protected function buildInheritanceOverview(GroupTypeInterface $group_type) {
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Inheritance overview for @group_type', ['@group_type' => $group_type->label()]),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Details'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No inheritances set up yet'),
    ];

    $inheritances = $this->entityTypeManager
      ->getStorage('subgroup_role_inheritance')
      ->loadByProperties(['tree' => $group_type->id()]);

    foreach ($inheritances as $id => $inheritance) {
      $form['table'][$id]['#process'] = ['::processSubform'];
      $form['table'][$id]['details']['#markup'] = $inheritance->label();
      $form['table'][$id]['remove'] = [
        '#type' => 'submit',
        '#name' => $id . '_remove',
        '#value' => $this->t('Remove'),
        '#submit' => ['::submitRemoveInheritance'],
      ];
    }

    $form['add_inheritance'] = $this->buildAddInheritanceForm($group_type);

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRemoveInheritance(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->delete([$storage->load(end($parents))]);
  }

  /**
   * Builds the add inheritance form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The root group type.
   *
   * @return array
   *   The form structure.
   */
  protected function buildAddInheritanceForm(GroupTypeInterface $group_type) {
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Set up a new inheritance'),
      '#process' => ['::processSubform'],
    ];

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_ids = array_merge([$group_type->id()], array_keys($subgroup_handler->getDescendants($group_type)));

    $group_roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadByProperties([
        'group_type' => $group_type_ids,
        'audience' => 'member',
      ]);

    foreach ($group_roles as $group_role_id => $group_role) {
      $options[$group_role_id] = $group_role->getGroupType()->label() . ' - ' . $group_role->label();
    }

    $base = ['#type' => 'select', '#options' => $options, '#required' => TRUE];
    $form['source'] = $base + ['#title' => $this->t('People with this role &hellip;')];
    $form['target'] = $base + ['#title' => $this->t('&hellip; will inherit the following role')];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => $group_type->id() . '_add_inheritance',
      '#value' => $this->t('Add inheritance'),
      '#button_type' => 'primary',
      '#validate' => ['::validateCreateInheritance'],
      '#submit' => ['::submitCreateInheritance'],
    ];

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCreateInheritance(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_source = array_merge($parents, ['source']);
    $parents_target = array_merge($parents, ['target']);

    $source = $form_state->getValue($parents_source);
    $target = $form_state->getValue($parents_target);
    if (empty($source) && empty($target)) {
      return;
    }

    if ($source === $target) {
      $form_state->setErrorByName(implode('][', $parents), $this->t('Source and target may not be the same.'));
    }

    $exists = $this->entityTypeManager
      ->getStorage('subgroup_role_inheritance')
      ->getQuery()
      ->condition('source', $source)
      ->condition('target', $target)
      ->count()
      ->execute();
    if ($exists) {
      $form_state->setErrorByName(implode('][', $parents), $this->t('This inheritance combination exists already.'));
    }

    $storage = $this->entityTypeManager->getStorage('group_role');
    $source_group_role = $storage->load($source);
    $target_group_role = $storage->load($target);

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    if (!$subgroup_handler->areVerticallyRelated($source_group_role->getGroupType(), $target_group_role->getGroupType())) {
      $form_state->setErrorByName(implode('][', $parents), $this->t('Source and target are not ancestors or descendants of one another.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCreateInheritance(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $parents_source = array_merge($parents, ['source']);
    $parents_target = array_merge($parents, ['target']);

    $source = $form_state->getValue($parents_source);
    $target = $form_state->getValue($parents_target);

    // Choose a hashed ID if the readable ID would exceed the maximum length.
    $preferred_id = $source . '-' . $target;
    if (strlen($preferred_id) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      $hashed_id = 'subgroup_role_inheritance_' . md5($preferred_id);
      $preferred_id = substr($hashed_id, 0, EntityTypeInterface::BUNDLE_MAX_LENGTH);
    }

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $preferred_id,
      'source' => $source,
      'target' => $target,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Processes a subform to add limited validation.
   */
  public function processSubform(&$element, FormStateInterface $form_state, &$complete_form) {
    foreach (Element::getVisibleChildren($element) as $child_key) {
      $child = &$element[$child_key];
      if (!empty($child['#type']) && $child['#type'] === 'submit') {
        $child['#limit_validation_errors'] = [$element['#parents']];
      }
    }
    return $element;
  }

  /**
   * Returns a list of group type IDs that do not belong to a tree yet.
   *
   * @return string[]
   *   The group type IDs.
   */
  protected function getNonLeafGroupTypeIds() {
    if (!isset($this->nonLeafGroupTypeIds)) {
      // @todo Run the simplified code below when the core bug re notExists() is
      // fixed in https://www.drupal.org/project/drupal/issues/3154858.
      $storage = $this->entityTypeManager->getStorage('group_type');
      if (FALSE) {
        $this->nonLeafGroupTypeIds = $storage->getQuery()
          ->notExists('third_party_settings.subgroup')
          ->accessCheck(FALSE)
          ->execute();
      }
      else {
        $tree_group_type_ids = $storage->getQuery()
          ->exists('third_party_settings.subgroup')
          ->accessCheck(FALSE)
          ->execute();

        $group_type_ids_query = $storage->getQuery()->accessCheck(FALSE);
        if (!empty($tree_group_type_ids)) {
          $group_type_ids_query->condition('id', $tree_group_type_ids, 'NOT IN');
        }
        $this->nonLeafGroupTypeIds = $group_type_ids_query->execute();
      }
    }

    return $this->nonLeafGroupTypeIds;
  }

}
