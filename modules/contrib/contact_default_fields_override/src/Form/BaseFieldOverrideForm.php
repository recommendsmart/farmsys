<?php

namespace Drupal\contact_default_fields_override\Form;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BaseFieldOverrideForm.
 *
 * The contact_default_fields_override form.
 *
 * @package Drupal\contact_default_fields_override\Form
 */
class BaseFieldOverrideForm extends FormBase {

  /**
   * The Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current contact_form entity we're editing.
   *
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected $contactForm;

  /**
   * The base field definition we're editing.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $baseFieldDefinition;

  /**
   * The field definition we're editing.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Contact form entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactFormStorage;

  /**
   * BaseFieldOverrideForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   */
  public function __construct(ContainerInterface $container) {
    $this->messenger = $container->get('messenger');
    $routeMatch = $this->getRouteMatch();

    $field_name = $routeMatch->getParameter('field_name');

    if (empty($field_name)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = $container->get('entity_field.manager');

    $baseFieldDefinitions = $entityFieldManager->getBaseFieldDefinitions('contact_message');
    $this->baseFieldDefinition = $baseFieldDefinitions[$field_name];

    $this->contactFormStorage = $container->get('entity_type.manager')
      ->getStorage('contact_form');

    $this->contactForm = $this->contactFormStorage->load($routeMatch->getParameter('contact_form'));

    if (!$this->contactForm) {
      throw new NotFoundHttpException();
    }

    $fieldDefinitions = $entityFieldManager->getFieldDefinitions('contact_message', $this->contactForm->id());
    $this->fieldDefinition = $fieldDefinitions[$field_name];

    $this->languageManager = $container->get('language_manager');
  }

  /**
   * Get the title for this form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   */
  public function getTitle() {
    return $this->t('%field settings for %bundle', [
      '%field' => $this->fieldDefinition->getLabel(),
      '%bundle' => $this->contactForm->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_default_fields_override_basefieldoverride_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->fieldDefinition->isRequired(),
      '#weight' => -5,
    ];

    foreach ($this->languageManager->getLanguages() as $language) {

      $label = $this->contactForm->getThirdPartySetting('contact_default_fields_override', $field_name . '_label_' . $language->getId());
      $description = $this->contactForm->getThirdPartySetting('contact_default_fields_override', $field_name . '_description_' . $language->getId());
      ;

      if ($field_name === 'name' && empty($label)) {
        // Not overidden yet. Use default values from
        // \Drupal\contact\MessageForm.
        $label = $this->t('Your name');
        $description = '';
      }

      if ($field_name === 'mail' && empty($label)) {
        // Not overidden yet. Use default values from
        // \Drupal\contact\MessageForm.
        $label = $this->t('Your email address');
        $description = '';
      }

      $form['language_' . $language->getId()] = [
        '#type' => 'fieldset',
        '#title' => $language->getName(),
      ];

      $form['language_' . $language->getId()]['label_' . $language->getId()] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $label,
        '#required' => TRUE,
        '#weight' => -20,
      ];

      $form['language_' . $language->getId()]['description_' . $language->getId()] = [
        '#type' => 'textarea',
        '#title' => $this->t('Help text'),
        '#default_value' => $description,
        '#rows' => 5,
        '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
        '#weight' => -10,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger->addMessage($this->t('Saved %label configuration.', ['%label' => $this->fieldDefinition->getLabel()]));

    $field_name = $this->fieldDefinition->getName();

    $required = 1;
    if ($form_state->getValue('required') != 1) {
      $required = 0;
    }

    $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_required', $required);

    foreach ($this->languageManager->getLanguages() as $language) {
      $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_label_' . $language->getId(), $form_state->getValue('label_' . $language->getId()));
      $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_description_' . $language->getId(), $form_state->getValue('description_' . $language->getId()));
    }

    $this->contactForm->save();

    $url = Url::fromRoute('entity.contact_message.field_ui_fields', ['contact_form' => $this->contactForm->id()]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       $container
    );
  }

}
