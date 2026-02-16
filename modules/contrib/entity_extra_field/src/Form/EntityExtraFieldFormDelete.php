<?php

namespace Drupal\entity_extra_field\Form;

use Drupal\Core\Url;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity extra form delete.
 */
class EntityExtraFieldFormDelete extends EntityConfirmFormBase {

  /**
   * The cache discovery backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * Entity extra field form delete constructor.
   */
  public function __construct(CacheBackendInterface $cache_discovery) {
    $this->cacheDiscovery = $cache_discovery;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::submitForm($form, $form_state);
    $this->entity->delete();
    $this->cacheDiscovery->invalidateAll();
  }

}
