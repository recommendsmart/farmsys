<?php

namespace Drupal\grequest\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides request membership link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_request_membership")
 */
final class RequestMembership extends FieldPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * RequestMembership constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally override query to do nothing.
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\group\Entity\Group $group */
    $group = $values->_entity;
    $build = NULL;
    $membership_requests = $group->getContentByEntityId('group_membership_request', $this->currentUser->id());
    if (!empty($group->getMember($this->currentUser))) {
      $build['#markup'] = $this->t('Already member');
    }
    elseif (empty($membership_requests)
      && $group->getGroupType()->hasContentPlugin('group_membership_request')) {
      $build = $group->toLink($this->t('Request Membership'), 'group-request-membership')
        ->toString();
    }
    else {
      $membership_request = reset($membership_requests);
      if ($membership_request->grequest_status->value == 0) {
        $build['#markup'] = $this->t('Pending membership request');
      }
      else {
        $build['#markup'] = $this->t('Rejected membership request');
      }
    }

    return $build;
  }

}
