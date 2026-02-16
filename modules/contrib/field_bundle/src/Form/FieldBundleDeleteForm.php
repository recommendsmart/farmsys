<?php

namespace Drupal\field_bundle\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Field bundle delete form.
 *
 * This class exists solely to make it work with field bundles, because they
 * do not provide a canonical link by default. The generic class for entity
 * deletion does not cover that.
 *
 * @internal
 *
 * @todo Re-evaluate and streamline the entity deletion form class hierarchy in
 *   https://www.drupal.org/node/2491057.
 */
class FieldBundleDeleteForm extends ContentEntityConfirmFormBase {

  use EntityDeleteFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\field_bundle\FieldBundleInterface $entity */
    $entity = $this->getEntity();
    if ($entity->isDefaultTranslation()) {
      if (count($entity->getTranslationLanguages()) > 1) {
        $languages = [];
        foreach ($entity->getTranslationLanguages() as $language) {
          $languages[] = $language->getName();
        }

        $form['deleted_translations'] = [
          '#theme' => 'item_list',
          '#title' => $this->t('The following @entity-type translations will be deleted:', [
            '@entity-type' => $entity->getEntityType()->getSingularLabel(),
          ]),
          '#items' => $languages,
        ];

        $form['actions']['submit']['#value'] = $this->t('Delete all translations');
      }
    }
    else {
      $form['actions']['submit']['#value'] = $this->t('Delete @language translation', ['@language' => $entity->language()->getName()]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->getDeletionMessage();

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirectUrl($untranslated_entity->toUrl('drupal:content-translation-overview'));
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl($this->getRedirectUrl());
    }

    $this->messenger()->addStatus($message);
    $this->logDeletionMessage($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    if ($entity->isDefaultTranslation()) {
      if ($entity->hasLinkTemplate('collection')) {
        // If available, return the collection URL.
        return $entity->toUrl('collection');
      }
      else {
        // Otherwise fall back to the default link template.
        return $entity->toUrl();
      }
    }
    return $entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('The @entity-type %label @language translation with ID %id has been deleted.', [
        '@entity-type' => $entity->getEntityType()->getSingularLabel(),
        '%label' => $entity->label(),
        '%id' => $entity->id(),
        '@language' => $entity->language()->getName(),
      ]);
    }

    return $this->t('The @entity-type %label with ID %id has been deleted.', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label(),
      '%id' => $entity->id(),
    ]);
  }

  /**
   * Logs the deletion message.
   *
   * @param string $message
   *   The deletion message.
   */
  protected function logDeletionMessage($message) {
    if ($message instanceof TranslatableMarkup) {
      $message = new TranslatableMarkup($message->getUntranslatedString(), $message->getArguments(), ['langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId()] + $message->getOptions());
    }
    $this->logger('field_bundle')->notice((string) $message);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('Are you sure you want to delete the @language translation of the @entity-type %label?', [
        '@language' => $entity->language()->getName(),
        '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
        '%label' => $this->getEntity()->label(),
      ]);
    }

    return $this->t('Are you sure you want to delete the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

}
