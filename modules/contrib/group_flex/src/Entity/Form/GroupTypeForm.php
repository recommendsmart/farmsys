<?php

namespace Drupal\group_flex\Entity\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Form\GroupTypeForm as GroupTypeFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\GroupFlexGroupType;
use Drupal\group_flex\GroupFlexGroupTypeSaver;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for group type forms.
 */
class GroupTypeForm extends GroupTypeFormBase {

  /**
   * The Group Type Saver service to save the form state.
   *
   * @var \Drupal\group_flex\GroupFlexGroupTypeSaver
   */
  private $groupTypeSaver;

  /**
   * The Group Type service to retrieve the values.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  private $flexGroupType;

  /**
   * Constructs a new GroupTypeForm.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\group_flex\GroupFlexGroupTypeSaver $groupTypeSaver
   *   The group type saver service.
   * @param \Drupal\group_flex\GroupFlexGroupType $flexGroupType
   *   The group type service.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, GroupFlexGroupTypeSaver $groupTypeSaver, GroupFlexGroupType $flexGroupType) {
    parent::__construct($entityFieldManager);
    $this->groupTypeSaver = $groupTypeSaver;
    $this->flexGroupType = $flexGroupType;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('group_flex.group_type_saver'),
      $container->get('group_flex.group_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    $form['group_flex_enabler'] = [
      '#title' => $this->t('Enable group flex'),
      '#type' => 'checkbox',
      '#default_value' => $this->flexGroupType->hasFlexEnabled($type),
      '#description' => $this->t('This will enable the group flex functionality for this group type.'),
    ];
    $form['group_flex'] = [
      '#type' => 'details',
      '#title' => $this->t('Group flex settings'),
      '#open' => TRUE,
      '#description' => $this->t('<strong>Warning:</strong> Changing the flex settings for existing group types may result in undesired behaviour when Groups of that type are created.'),
      '#states' => [
        'visible' => [
          ':input[name="group_flex_enabler"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $visibilityOptions = $this->getOptionsFromPlugin('group_type_visibility');
    $defaultVisibility = $this->flexGroupType->getGroupTypeVisibility($type);
    $form['group_flex']['group_type_visibility'] = [
      '#title' => $this->t('Group type visibility'),
      '#type' => 'radios',
      '#options' => $visibilityOptions,
      '#description' => $this->t('When the visibility is "public", roles that can actually view "Groups" of this type can be defined through the group type permissions page.'),
      '#default_value' => $defaultVisibility ?? GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC,
    ];

    $joiningMethods = $this->getOptionsFromPlugin('group_type_joining_method');
    $defaultMethods = $this->flexGroupType->getJoiningMethods($type);
    $form['group_flex']['group_type_joining_method'] = [
      '#title' => t('Group joining methods'),
      '#type' => 'checkboxes',
      '#options' => $joiningMethods,
      '#description' => $this->t('Set who can use the different methods through the group type permissions page and site <a href="@site_permissions_link">permissions</a> ("call to action" (join button) only).', [
        '@site_permissions_link' => Url::fromUserInput('/admin/people/permissions')->toString(),
      ]),
      '#default_value' => $defaultMethods,
      '#disabled' => FALSE,
    ];
    // Availability of join method may depend on value of the group visibility.
    /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $joiningMethod */
    foreach ($this->groupTypeSaver->getAllJoiningMethods() as $id => $joiningMethod) {
      $enabledVisOptions = $joiningMethod->getVisibilityOptions();
      if (!empty($enabledVisOptions)) {
        foreach ($visibilityOptions as $visibilityOptionId => $unusedLabel) {
          if (!in_array($visibilityOptionId, $enabledVisOptions, TRUE)) {
            $form['group_flex']['group_type_joining_method'][$id]['#states']['disabled'][][':input[name="group_type_visibility"]'] = ['value' => $visibilityOptionId];
            $form['group_flex']['group_type_joining_method'][$id]['#states']['unchecked'][][':input[name="group_type_visibility"]'] = ['value' => $visibilityOptionId];
            $form['group_flex']['group_type_joining_method'][$id]['#states']['unchecked'][][':input[name="group_type_joining_method[' . $id . ']"]'] = ['checked' => FALSE];
          }
        }
      }
    }

    $form['group_flex']['group_type_joining_method_override'] = [
      '#title' => $this->t('Group owner can select the joining method(s) for their group'),
      '#type' => 'checkbox',
      '#default_value' => $this->flexGroupType->canOverrideJoiningMethod($type),
      '#description' => $this->t('When this is enabled the group owner can select one or multiple of the enabled joining methods for their group.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    parent::save($form, $form_state);
    $type = $this->entity;

    // Only act when the group type is saved correctly.
    if ($type && $type instanceof GroupTypeInterface) {
      $type->setThirdPartySetting('group_flex', 'group_flex_enabler', $form_state->getValue('group_flex_enabler'));
      $type->setThirdPartySetting('group_flex', 'group_type_visibility', $form_state->getValue('group_type_visibility'));
      $type->setThirdPartySetting('group_flex', 'group_type_joining_method', $form_state->getValue('group_type_joining_method'));
      $type->setThirdPartySetting('group_flex', 'group_type_joining_method_override', $form_state->getValue('group_type_joining_method_override'));

      $type->save();

      // Note: we are saving this but when permissions change later this might
      // not match anymore or when converting group type from flexible to other.
      // This is the responsibility of the site administrator.
      if ($form_state->getValue('group_flex_enabler')) {
        $this->groupTypeSaver->save($type);
      }
    }

  }

  /**
   * Get options from the plugin to be used in form field.
   *
   * @param string $fieldId
   *   The field id to retrieve the options for.
   *
   * @return array
   *   The array of options keyed by the id of the plugin.
   */
  public function getOptionsFromPlugin(string $fieldId): array {
    $options = [];
    switch ($fieldId) {
      case 'group_type_visibility':
        $optionProvider = $this->groupTypeSaver->getAllGroupVisibility();
        break;

      case 'group_type_joining_method':
        $optionProvider = $this->groupTypeSaver->getAllJoiningMethods();
        break;

      default:
        return $options;

    }

    /** @var \Drupal\group_flex\Plugin\GroupVisibilityBase|\Drupal\group_flex\Plugin\GroupJoiningMethodBase $option */
    foreach ($optionProvider as $id => $option) {
      $label = $option->getLabel();
      $options[$id] = $label;
    }
    return $options;
  }

}
