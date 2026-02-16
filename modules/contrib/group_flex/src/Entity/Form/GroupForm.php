<?php

namespace Drupal\group_flex\Entity\Form;

use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\group\Entity\Form\GroupForm as GroupFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for group forms.
 */
class GroupForm extends GroupFormBase {

  /**
   * The group flex settings array.
   *
   * @var array
   */
  private $groupFlexSettings;

  /**
   * The group type flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  private $groupTypeFlex;

  /**
   * The group flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  private $groupFlex;

  /**
   * The group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  private $visibilityManager;

  /**
   * The flex group type saver.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  private $groupFlexSaver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);
    $form->groupTypeFlex = $container->get('group_flex.group_type');
    $form->groupFlex = $container->get('group_flex.group');
    $form->visibilityManager = $container->get('plugin.manager.group_visibility');
    $form->groupFlexSaver = $container->get('group_flex.group_saver');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->getEntity();

    /** @var \Drupal\group\Entity\GroupTypeInterface $groupType */
    $groupType = $this->getEntity()->getGroupType();

    // The group flex logic is enabled for this group type.
    if ($this->groupTypeFlex->hasFlexEnabled($groupType)) {
      $visibilityPlugins = $this->visibilityManager->getAllAsArrayForGroup();
      $groupVisibility = $this->groupTypeFlex->getGroupTypeVisibility($groupType);
      $form['footer']['group_visibility'] = [
        '#title' => $this->t('Visibility'),
        '#type' => 'item',
        '#weight' => isset($form['actions']['#weight']) ? ($form['actions']['#weight'] - 1) : -1,
      ];

      // The group visibility is flexible on a group level.
      if ($this->groupTypeFlex->hasFlexibleGroupTypeVisibility($groupType)) {
        $visibilityOptions = [];
        foreach ($visibilityPlugins as $id => $pluginInstance) {
          $visibilityOptions[$id] = $pluginInstance->getGroupLabel($groupType);
        }
        if (!empty($visibilityOptions)) {
          $form['footer']['group_visibility']['#required'] = TRUE;
          $form['footer']['group_visibility']['#type'] = 'radios';
          $form['footer']['group_visibility']['#options'] = $visibilityOptions;
          try {
            $default = $this->groupFlex->getGroupVisibility($group);
          }
          catch (MissingDataException $e) {
            $default = GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC;
          }
          $form['footer']['group_visibility']['#default_value'] = $default;
        }
      }

      // The group type visibility cannot be changed on group level.
      if (array_key_exists($groupVisibility, $visibilityPlugins) && $this->groupTypeFlex->hasFlexibleGroupTypeVisibility($groupType) === FALSE) {
        $pluginInstance = $visibilityPlugins[$groupVisibility];

        $visExplanation = $pluginInstance->getValueDescription($groupType);
        $visDescription = $this->t('The @group_type_name visibility is @visibility_value', [
          '@group_type_name' => $groupType->label(),
          '@visibility_value' => $pluginInstance->getLabel(),
        ]);
        if ($visDescription && $visExplanation) {
          $form['footer']['group_visibility']['#markup'] = '<p>' . $visDescription . ' (' . $visExplanation . ')' . '</p>';
        }
      }

      // The group joining method can be changed on group level.
      if ($this->groupTypeFlex->canOverrideJoiningMethod($groupType)) {
        $enabledMethods = $this->groupTypeFlex->getEnabledJoiningMethodPlugins($groupType);
        $methodOptions = [];
        foreach ($enabledMethods as $id => $pluginInstance) {
          $methodOptions[$id] = $pluginInstance->getLabel();
        }
        $form['footer']['group_joining_methods'] = [
          '#title' => $this->t('Joining methods'),
          '#type' => 'checkboxes',
          '#options' => $methodOptions,
          '#weight' => $form['footer']['group_visibility']['#weight'] + 1,
        ];
        try {
          $defaultOptions = $this->groupFlex->getDefaultJoiningMethods($group);
        }
        catch (MissingDataException $e) {
          $defaultOptions = [];
        }

        // If this is a new group we enable all methods by default.
        if (empty($defaultOptions) && empty($group->id())) {
          $defaultOptions = array_keys($methodOptions);
        }

        $form['footer']['group_joining_methods']['#default_value'] = $defaultOptions;

        // Availability of join method depends on the group visibility.
        if (isset($visibilityOptions)) {
          /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $joiningMethod */
          foreach ($enabledMethods as $id => $joiningMethod) {
            $allowedVisOptions = $joiningMethod->getVisibilityOptions();
            if (!empty($allowedVisOptions)) {
              foreach ($visibilityOptions as $visibilityOptionId => $unusedLabel) {
                if (!in_array($visibilityOptionId, $allowedVisOptions, TRUE)) {
                  $form['footer']['group_joining_methods'][$id]['#states']['disabled'][][':input[name="group_visibility"]'] = ['value' => $visibilityOptionId];
                }
              }
            }
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Create an array of group flex settings.
    $groupFlexSettings = [
      'visibility' => $form_state->getValue('group_visibility'),
      'joining_methods' => $form_state->getValue('group_joining_methods'),
    ];
    $this->groupFlexSettings = $groupFlexSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $return = parent::save($form, $form_state);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->entity;
    $groupType = $group->getGroupType();

    // Create an array of group flex settings.
    $groupFlexSettings = [
      'visibility' => $form_state->getValue('group_visibility'),
      'joining_methods' => $form_state->getValue('group_joining_methods'),
    ];
    if (!$this->groupTypeFlex->hasFlexibleGroupTypeVisibility($groupType)) {
      unset($groupFlexSettings['visibility']);
    }
    if (!$this->groupTypeFlex->canOverrideJoiningMethod($groupType)) {
      unset($groupFlexSettings['joining_methods']);
    }

    if (empty($groupFlexSettings)) {
      return $return;
    }

    foreach ($groupFlexSettings as $key => $value) {
      if ($group && $group->id()) {
        switch ($key) {
          case 'visibility':
            if ($value !== NULL) {
              $this->groupFlexSaver->saveGroupVisibility($group, $value);
            }
            break;

          case 'joining_methods':
            // Because we can change the group visibility to private of existing
            // group causing the joining method not to be disabled after this.
            if ($value === NULL) {
              $value = [];
            }
            // This is needed to support the use of radios.
            if (is_string($value)) {
              $value = [$value => $value];
            }
            $this->groupFlexSaver->saveGroupJoiningMethods($group, $value);
            break;
        }
      }

    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function store(array &$form, FormStateInterface $form_state) {
    parent::store($form, $form_state);
    $store = $this->privateTempStoreFactory->get('group_creator_flex');
    $storeId = $form_state->get('store_id');

    foreach ($this->groupFlexSettings as $key => $value) {
      if ($value !== NULL) {
        try {
          $store->set("$storeId:$key", $value);
        }
        catch (TempStoreException $exception) {
          return;
        }
      }
    }
  }

}
