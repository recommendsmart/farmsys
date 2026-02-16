<?php

/**
 * @file
 * Contains \Drupal\form_filter_fields\Form\FormFilterFieldsDelete
 */

namespace Drupal\form_filter_fields\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormFilterFieldsDelete extends ConfirmFormBase {

	protected $data_type;
	protected $content_type_machine_name;
	protected $control_field;
	protected $target_field;

	// -------------------------------------------------------------------
  
	public function getFormId() {
		return "form_filter_fields_delete_form";
	}

	// -------------------------------------------------------------------
  
	public function getQuestion() {
		return t("Are you sure you want to delete the " . $this->target_field . " dependency?");
	}

	// -------------------------------------------------------------------
  
	public function getConfirmText() {
		return t("Delete");
	}

	// -------------------------------------------------------------------
  
	public function getCancelUrl() {
		return new Url("form_filter_fields.settings");
	}

	// -------------------------------------------------------------------
  
	public function buildForm(array $form, FormStateInterface $form_state, $data_type = "", $content_type_machine_name = "", $control_field = "", $target_field = "") {
		
		// data comes in like {content_type_machine_name}/{control_field}/{target_field}

		if (!isset($data_type) || !isset($content_type_machine_name) || !isset($control_field) || !isset($target_field)) {
			throw new NotFoundHttpException();
		}

		$this->data_type = $data_type;
		$this->content_type_machine_name = $content_type_machine_name;
		$this->control_field = $control_field;
		$this->target_field = $target_field;

	  	return parent::buildForm($form, $form_state);
	}

	// -------------------------------------------------------------------

	public function submitForm(array &$form, FormStateInterface $form_state) {
		// for some reason deleting a specific variable from our configuration does not work
		// what we do here is get all the configuration, then delete it all, unset what the user deleted, then save it all again

		$config = $this->config("form_filter_fields.settings");
		$all_form_filter_field_dependencies = $config->get("form_filter_fields_settings");

		// make sure what they want to delete is real
		if (isset($all_form_filter_field_dependencies[$this->data_type][$this->content_type_machine_name][$this->control_field][$this->target_field])) {
			unset($all_form_filter_field_dependencies[$this->data_type][$this->content_type_machine_name][$this->control_field][$this->target_field]);
			
			// now delete all the config from this
			\Drupal::configFactory()->getEditable("form_filter_fields.settings")->delete();

			// then loop through the array and create it again
			foreach ($all_form_filter_field_dependencies as $data_type => $form_filter_field_dependenceies) {
				foreach ($form_filter_field_dependenceies as $content_type_machine_name => $dependent_field_info) {
					// now loop through all the depend fields of that content type
					foreach ($dependent_field_info as $control_field => $target_fields) {
						// since a control field can have many target fields, loop through all the target fields
						foreach ($target_fields as $target_field => $view_id) {
							// recreate the configuration
							\Drupal::configFactory()->getEditable("form_filter_fields.settings")
							->set("form_filter_fields_settings." . $data_type . "." . $content_type_machine_name . "." . $control_field . "." . $target_field, $view_id)
							->save();
						}
					}
				}
			}
		}

		// forward them back to the settings form
		$this->messenger()->addStatus($this->t("Dependency deleted."));
    	$form_state->setRedirectUrl($this->getCancelUrl());
	}

	// -------------------------------------------------------------------
  
}









	



	// -------------------------------------------------------------------

