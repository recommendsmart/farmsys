<?php

namespace Drupal\commerceg_profile\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content enabler for profiles.
 *
 * @GroupContentEnabler(
 *   id = "commerceg_profile",
 *   label = @Translation("Group profile"),
 *   description = @Translation("Adds profiles to groups."),
 *   entity_type_id = "profile",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the profile to add to the group"),
 *   deriver = "Drupal\commerceg_profile\Plugin\GroupContentEnabler\ProfileDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\group\Plugin\GroupContentPermissionProvider"
 *   }
 * )
 *
 * @I Consider providing a plugin that permits all profile bundles
 *    type     : feature
 *    priority : normal
 *    labels   : content-enabler, profile
 */
class Profile extends GroupContentEnablerBase implements
  ContainerFactoryPluginInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The profile type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $profileTypeStorage;

  /**
   * Constructs a new Profile object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $profile_type_storage
   *   The profile type storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $string_translation,
    AccountInterface $account,
    ConfigEntityStorageInterface $profile_type_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->stringTranslation = $string_translation;
    $this->account = $account;
    $this->profileTypeStorage = $profile_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $profile_type_storage = $container
      ->get('entity_type.manager')
      ->getStorage('profile_type');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('current_user')->getAccount(),
      $profile_type_storage
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $plugin_id = $this->getPluginId();

    if (!$group->hasPermission("create $plugin_id entity", $this->account)) {
      return [];
    }

    $type = $this->getEntityBundle();
    $route_params = [
      'group' => $group->id(),
      'plugin_id' => $plugin_id,
    ];

    return [
      "commerceg-profile-create-$type" => [
        'title' => $this->t(
          'Create @type',
          ['@type' => $this->getProfileType()->label()]
        ),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $this->t(
      "This field has been disabled by the plugin to guarantee the functionality
      that's expected of it."
    ) . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'profile.type.' . $this->getEntityBundle();

    return $dependencies;
  }

  /**
   * Retrieves the profile type this plugin supports.
   *
   * @return \Drupal\commerce_profile\Entity\ProfileTypeInterface
   *   The profile type this plugin supports.
   */
  protected function getProfileType() {
    return $this->profileTypeStorage->load($this->getEntityBundle());
  }

}
