<?php

/**
 * @file
 * Contains \Drupal\resource\Form\DeleteMultiple.
 */

namespace Drupal\resource\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\resource\ResourceTypeInterface;
use Drupal\resource\Entity\ResourceType;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a resource deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of resources to delete.
   *
   * @var string[][]
   */
  protected $resourceInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The resource storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('resource');
  }

  /**
   * Checks access to the form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    /** @var ResourceTypeInterface $type */
    foreach (ResourceType::loadMultiple() as $type) {
      // If the user has either access to deleting own resource entities, or access
      // to deleting all entities in at least one type, they should be able to
      // access the bulk confirm form. If they for some reason try to go there
      // to delete one they don't have access to, the entity access will forbid
      // it anyway.
      if ($account->hasPermission('delete own ' . $type->id() . ' resource entities') || $account->hasPermission('delete any ' . $type->id() . ' resource entities')) {
        return AccessResult::allowed();
      }
    }
    // In addition we grant access if the user can administer resource entities.
    if ($account->hasPermission('administer resources')) {
      return AccessResult::allowed();
    }
    // If none of the above, the user is not allowed access.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resource_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->resourceInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    // Return the URL of the front page.
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->resourceInfo = $this->tempStoreFactory->get('resource_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->resourceInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\resource\ResourceInterface[] $resources */
    $resources = $this->storage->loadMultiple(array_keys($this->resourceInfo));

    $items = [];
    foreach ($this->resourceInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $resource = $resources[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $resource->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $resource->getTranslationLanguages();
        if (count($languages) > 1 && $resource->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following content translations will be deleted:</em>', ['@label' => $resource->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $resource->label();
        }
      }
    }

    $form['resources'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->resourceInfo)) {
      $total_count = 0;
      $delete_resources = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\resource\ResourceInterface[] $resources */
      $resources = $this->storage->loadMultiple(array_keys($this->resourceInfo));

      foreach ($this->resourceInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $resource = $resources[$id]->getTranslation($langcode);
          if ($resource->isDefaultTranslation()) {
            $delete_resources[$id] = $resource;
            unset($delete_translations[$id]);
            $total_count += count($resource->getTranslationLanguages());
          }
          elseif (!isset($delete_resources[$id])) {
            $delete_translations[$id][] = $resource;
          }
        }
      }

      if ($delete_resources) {
        $this->storage->delete($delete_resources);
        $this->logger('resource')->notice('Deleted @count posts.', array('@count' => count($delete_resources)));
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $resource = $resources[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $resource->removeTranslation($translation->language()->getId());
          }
          $resource->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('resource')->notice('Deleted @count content translations.', array('@count' => $count));
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 post.', 'Deleted @count posts.'));
      }

      $this->tempStoreFactory->get('resource_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
