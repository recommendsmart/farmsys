<?php

namespace Drupal\aggrid\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\aggrid\Utils\HelperFunctions;

/**
 * Class AggridForm.
 */
class AggridForm extends EntityForm {
  
  /**
   * Helper functions
   *
   * @var Drupal\aggrid\Utils\HelperFunctions
   */
  protected $HelperFunctions;
  
  /**
   * AggridForm constructor.
   */
  public function __construct()
  {
    $this->HelperFunctions = new HelperFunctions();
  }
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $aggrid_entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $aggrid_entity->label(),
      '#description' => $this->t("Label for the ag-Grid Entity."),
      '#required' => TRUE,
    ];

    $form['aggridDefault'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ag-Grid Default JSON'),
      '#attributes' => [
        'class' => ['aggrid-json-widget'],
      ],
      '#attached' => [
        'library' => [
          'aggrid/aggrid.json.widget',
        ],
      ],
      '#default_value' => $aggrid_entity->get('aggridDefault'),
      '#description' => $this->t('columnDefs used throughout life but rowData is only for initial create. Please limit to 3 header rows for diff and only provide field names necessary for data items.'),
      '#required' => TRUE,
    ];

    $form['addOptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ag-Grid additional options'),
      '#attributes' => [
        'class' => ['aggrid-json-widget'],
      ],
      '#attached' => [
        'library' => [
          'aggrid/aggrid.json.widget',
        ],
      ],
      '#default_value' => $aggrid_entity->get('addOptions'),
      '#description' => $this->t('Will always be used for view/edit.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $aggrid_entity->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => '\Drupal\aggrid\Entity\aggrid::load',
      ],
      '#disabled' => !$aggrid_entity->isNew(),
    ];

    return $form;
  }
  
  /**
   * Validate aggrid form
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // Check if items are actually JSON.
    $jc_aggriddefault = $this->HelperFunctions->json_validate($form_state->getValue('aggridDefault'));
    $jc_addoptions = $this->HelperFunctions->json_validate($form_state->getValue('addOptions'));
    
    if (!empty($jc_aggriddefault)) {
      $form_state->setErrorByName('aggridDefault', $jc_aggriddefault);
    }
    if (!empty($jc_addoptions)) {
      $form_state->setErrorByName('addOptions', $jc_addoptions);
    }
    
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $aggrid = $this->entity;
    $status = $aggrid->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label ag-Grid Entity.',
            [
              '%label' => $aggrid->label(),
            ]
          ));
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label ag-Grid Entity.',
            [
              '%label' => $aggrid->label(),
            ]
          ));
    }
    $form_state->setRedirectUrl($aggrid->toUrl('collection'));
  }

}
