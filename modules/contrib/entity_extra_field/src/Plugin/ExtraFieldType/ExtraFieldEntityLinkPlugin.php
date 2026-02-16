<?php

declare(strict_types=1);

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\Core\Link;
use Drupal\Core\Utility\Token;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the extra field entity link type.
 *
 * @ExtraFieldType(
 *   id = "entity_link",
 *   label = @Translation("Entity link")
 * )
 */
class ExtraFieldEntityLinkPlugin extends ExtraFieldTypePluginBase {

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Extra field type view constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    Token $token,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $current_route_match,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    AccessManagerInterface $access_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $module_handler,
      $current_route_match,
      $entity_type_manager,
      $entity_field_manager
    );
    $this->accessManager = $access_manager;
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('access_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'link_text' => NULL,
      'link_template' => NULL,
      'link_target' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    $form['link_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Template'),
      '#require' => TRUE,
      '#options' => $this->getEntityLinkTemplateOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#default_value' => $configuration['link_template'],
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $configuration['link_text'],
      '#size' => 25,
      '#required' => TRUE,
    ];
    $form['link_target'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Target'),
      '#options' => [
        '_blank',
      ],
      '#empty_option' => $this->t('- Default -'),
      '#default_value' => $configuration['link_target'],
    ];

    return $form;
  }

  /**
   * Build the render array of the extra field type contents.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type the extra field is being attached too.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display the extra field is apart of.
   *
   * @return array
   *   The extra field renderable array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function build(
    EntityInterface $entity,
    EntityDisplayInterface $display
  ): array {
    $link = $this->buildEntityLink($entity);

    // Link and Url seem not to have convenience methods for access including
    // cacheability. So inlining a variant of \Drupal\Core\Url::access.
    $accessResult = $this->urlAccessResult($link->getUrl());
    $build = $accessResult->isAllowed() ? $link->toRenderable() : [];
    BubbleableMetadata::createFromObject($accessResult)->applyTo($build);
    return $build;
  }

  /**
   * A copy of \Drupal\Core\Url::access that returns cacheability.
   *
   * @param \Drupal\Core\Url $url
   *   The url.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns url access result object.
   */
  public function urlAccessResult(
    Url $url,
    AccountInterface $account = NULL
  ): AccessResultInterface {
    if ($url->isRouted()) {
      return $this->accessManager->checkNamedRoute(
        $url->getRouteName(),
        $url->getRouteParameters(),
        $account,
        TRUE
      );
    }

    return AccessResult::allowed();
  }

  /**
   * Build the entity link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity instance.
   *
   * @return \Drupal\Core\Link
   *   The entity link instance.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildEntityLink(EntityInterface $entity): Link {
    $configuration = $this->getConfiguration();

    return $entity->toLink(
      $configuration['link_text'],
      $configuration['link_template'],
      $this->getEntityLinkOptions()
    );
  }

  /**
   * Get the entity link options.
   *
   * @return array
   *   An array of the link options.
   */
  protected function getEntityLinkOptions(): array {
    $options = [];
    $configuration = $this->getConfiguration();

    if ($target = $configuration['link_target']) {
      $options['attributes']['target'] = $target;
    }

    return $options;
  }

  /**
   * Get entity link template options.
   *
   * @return array
   *   An array of the entity template options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityLinkTemplateOptions(): array {
    $templates = array_keys(
      $this->getTargetEntityTypeDefinition()->getLinkTemplates()
    );

    return array_combine($templates, $templates);
  }

}
