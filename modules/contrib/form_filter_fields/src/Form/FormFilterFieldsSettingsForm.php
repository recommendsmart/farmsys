<?php

/**
 * @file
 * Contains \Drupal\form_filter_fields\Form\FormFilterFieldsSettingsForm
 */

namespace Drupal\form_filter_fields\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\views\Views;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;

class FormFilterFieldsSettingsForm extends ConfigFormBase
{

	// -------------------------------------------------------------------

	public function getFormId()
	{
		return "form_filter_fields_settings";
	}

	// -------------------------------------------------------------------
	
	protected function getEditableConfigNames()
	{
		return ["form_filter_fields.settings"];
	}

	// -------------------------------------------------------------------

	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$form["#tree"] = TRUE;

		// we want to build out a form with three things on it:
		// content type, taxonomy term field (parent), dependent taxonomy term field (child), view to filter child based off of parent

		// get all the different content types in this drupal 8 installation
		$content_types = \Drupal::entityTypeManager()->getStorage("node_type")->loadMultiple();

		// all the content types we don't wish to output
		$excluded_content_types = array("openlayers_example_content", "feed", "site", "site_feed", "webform");

		$content_type_taxonomy_field_options = array();
		$content_type_field_vocabularies = array();

		// loop through each content type
		foreach($content_types as $ct) {
			// see if it is not in the content types we do not wish to search in
			if (!in_array($ct->id(), $excluded_content_types)) {
				// get the fields of the content type we want to search for & loop through the fields
				foreach (\Drupal::service("entity_field.manager")->getFieldDefinitions("node", $ct->id()) as $field_name => $field_definition) {

					// get what kind of field it is
					$field_type = $field_definition->getType();

					// we only want entity references
					if ($field_type == "entity_reference") {
						// get the field settings
						$field_settings = $field_definition->getSettings();

						// only get taxonomy terms & if its target bundles are set (what vocabulary the field has)
						if (($field_settings["target_type"] == "taxonomy_term") && (isset($field_settings["handler_settings"]["target_bundles"]))) {
							// this will be the option for the select menus for the parent and child
							$option_key = "content|" . $ct->id() . "|" . $field_name;

							$content_type_taxonomy_field_options[$option_key] = $ct->id() . " - " . $field_definition->getLabel();

							// this array makes the table more readable for relationships
							$content_type_field_vocabularies[$ct->id()][$field_name] = $field_definition->getLabel() . " <br />(vocabularies: " . implode(", ", $field_settings["handler_settings"]["target_bundles"]) . ")";
						}
					}
				}
			}
		}

		$media_exists = false;
		$media_type_taxonomy_field_options = array();
		$media_type_field_vocabularies = array();

		// check to see if media exists because some sites might not have it enabled
		$moduleHandler = \Drupal::service("module_handler");
		if ($moduleHandler->moduleExists("media")) {
			$media_exists = true;

			// get all the different media types in this drupal 8 installation
			$media_types = \Drupal::entityTypeManager()->getStorage("media_type")->loadMultiple();

			// loop through each media type
			foreach($media_types as $mt) {
				// get the fields of the media type we want to search for & loop through the fields
				foreach (\Drupal::service("entity_field.manager")->getFieldDefinitions("media", $mt->id()) as $field_name => $field_definition) {

					// get what kind of field it is
					$field_type = $field_definition->getType();

					// we only want entity references
					if ($field_type == "entity_reference") {
						// get the field settings
						$field_settings = $field_definition->getSettings();

						// only get taxonomy terms & if its target bundles are set (what vocabulary the field has)
						if (($field_settings["target_type"] == "taxonomy_term") && (isset($field_settings["handler_settings"]["target_bundles"]))) {
							// this will be the option for the select menus for the parent and child
							$option_key = "media|" . $mt->id() . "|" . $field_name;

							$media_type_taxonomy_field_options[$option_key] = $mt->id() . " - " . $field_definition->getLabel();

							// this array makes the table more readable for relationships
							$media_type_field_vocabularies[$mt->id()][$field_name] = $field_definition->getLabel() . " <br />(vocabularies: " . implode(", ", $field_settings["handler_settings"]["target_bundles"]) . ")";
						}
					}
				}
			}
		}

		// get a list of views the user can choose from; this will be the view that does the filtering
		$views_on_site = Views::getViewsAsOptions();

		// *************************************
		// output table
		// *************************************

		// create table headings for the content portion
		$content_output_table_header = array(t("Content Type Machine Name"), t("Control Field"), t("Target Field"), t("View"), t("Operations"));

		// output the contents of all the content type field dependencies
		$form["form_filter_fields_table"]["intro_content"] = array(
			"#type" => "container",
			"#markup" => "<h2>Content Type Form Filter Field Dependencies</h2>"
		);

		$form["form_filter_fields_table"]["table_content"] = array(
			"#type" => "table",
			"#header" => $content_output_table_header,
			"#rows" => $this->print_table($content_type_field_vocabularies, "content"),
			"#empty" => t("No dependencies found.")
		);

		if ($media_exists) {
			// create table headings for the media portion
			$media_output_table_header = array(t("Media Type Machine Name"), t("Control Field"), t("Target Field"), t("View"), t("Operations"));

			// output the contents of all the content type field dependencies
			$form["form_filter_fields_table"]["intro_media"] = array(
				"#type" => "container",
				"#markup" => "<br /><br /><h2>Media Type Form Filter Field Dependencies</h2>"
			);

			$form["form_filter_fields_table"]["table_media"] = array(
				"#type" => "table",
				"#header" => $media_output_table_header,
				"#rows" => $this->print_table($media_type_field_vocabularies, "media"),
				"#empty" => t("No dependencies found.")
			);
		}

		// *************************************
		// form
		// *************************************

		$form["form_filter_fields_settings"]["intro"] = array(
			"#type" => "container",
			"#markup" => "<br /><br /><h2>Instructions</h2>" .
				"<p>Configure taxonomy relationships by their fields inside each content type. Choose a Data Type then choose a Control Field (the field that controls the target) and a Target Field (the dependent field). Also select which view is used to filter this relationship.</p>" .
				"<p>Hit the <strong>Save Configuration</strong> when you're done selecting.</p>"
		);

		$field_options = array("content" => $content_type_taxonomy_field_options);

		if ($media_exists) {
			$field_options["media"] = $media_type_taxonomy_field_options;
		}

		$form["form_filter_fields_settings"]["add_relationship_form"]["control_field"] = array(
			"#title" => t("Control Field"),
			"#type" => "select",
			"#required" => true,
			"#description" => t("The field that controls the Target Field."),
			"#options" => $field_options
		);

		$form["form_filter_fields_settings"]["add_relationship_form"]["target_field"] = array(
			"#title" => t("Target Field"),
			"#type" => "select",
			"#required" => true,
			"#description" => t("The field which is targeted. This field will be the one that filters down based off of the Control Field."),
			"#options" => $field_options
		);

		$form["form_filter_fields_settings"]["add_relationship_form"]["filtering_view"] = array(
			"#title" => t("Filtering View"),
			"#type" => "select",
			"#required" => true,
			"#description" => t("Select the view that will filter the Target Field based off the value in the Control Field."),
			"#options" => $views_on_site
		);

		return parent::buildForm($form, $form_state);

		// end of buildForm
	}

	// -------------------------------------------------------------------

	// validate the results
	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		// we need to see if the control field and the target field are using the same content type and that they are not the same field

		$values = $form_state->getValues();

		// explode the inputs
		$control_field_tokens = explode("|", $values["form_filter_fields_settings"]["add_relationship_form"]["control_field"]);
		$target_field_tokens = explode("|", $values["form_filter_fields_settings"]["add_relationship_form"]["target_field"]);

		if ($control_field_tokens[0] != $target_field_tokens[0]) {
			// not the same data type so throw an error
			$form_state->setErrorByName("control_field", $this->t("The Control Field and the Target Field must use the same data type."));
		}

		if ($control_field_tokens[1] != $target_field_tokens[1]) {
			// not the same content type so throw an error
			$form_state->setErrorByName("control_field", $this->t("The Control Field and the Target Field must use the same content/media type."));
		}

		if ($control_field_tokens[2] == $target_field_tokens[2]) {
			// they are using the same field so throw an error
			$form_state->setErrorByName("control_field", $this->t("The Control Field and the Target Field must be different fields."));
		}
	}

	// -------------------------------------------------------------------
	
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$values = $form_state->getValues();

		// explode the inputs
		$control_field_tokens = explode("|", $values["form_filter_fields_settings"]["add_relationship_form"]["control_field"]);
		$target_field_tokens = explode("|", $values["form_filter_fields_settings"]["add_relationship_form"]["target_field"]);

		// data is like: "media|audio|field_audiences"

		// the first two indexes are the data type and the content type
		$data_type = $control_field_tokens[0];
		$content_type = $control_field_tokens[1];

		// the third index is what fields we're controlling
		$control_field = $control_field_tokens[2];
		$target_field = $target_field_tokens[2];

		// save the configuration
		\Drupal::configFactory()->getEditable("form_filter_fields.settings")
			->set("form_filter_fields_settings." . $data_type . "." . $content_type . "." . $control_field . "." . $target_field, $values["form_filter_fields_settings"]["add_relationship_form"]["filtering_view"])
			->save();

		parent::submitForm($form, $form_state);
	}

	// -------------------------------------------------------------------

	// returns the rows of an output table
	protected function print_table($field_vocabularies, $type) {
		// get the configuration
		$config = $this->config("form_filter_fields.settings");
		$all_form_filter_field_dependencies = $config->get("form_filter_fields_settings");

		// a row array to return
		$output_table_rows = array();

		if (!empty($all_form_filter_field_dependencies) && isset($all_form_filter_field_dependencies[$type])) {

			// sort by content type to be nice
			ksort($all_form_filter_field_dependencies[$type]);

			foreach ($all_form_filter_field_dependencies[$type] as $type_machine_name => $dependent_field_info) {

				// sort by dependent field so it's more organized
				ksort($dependent_field_info);

				// now loop through all the depend fields of that content type
				foreach ($dependent_field_info as $control_field => $target_fields) {

					// sort by the target field so it's more organized
					ksort($target_fields);

					// since a control field can have many target fields, loop through all the target fields
					foreach ($target_fields as $target_field => $view_id) {

						// create the row with the information, use a format that's friendly to HTML so we can use the breaks
						$row = array();
						$row[] = $type_machine_name;
						$row[] = new FormattableMarkup($field_vocabularies[$type_machine_name][$control_field], array());
						$row[] = new FormattableMarkup($field_vocabularies[$type_machine_name][$target_field], array());
						$row[] = $view_id;

						// create a delete link
						// the delete is /admin/config/content/form_filter_fields/delete/{data_type}/{type_machine_name}/{control_field}/{target_field}
						$delete_link = array();
						$delete_link["delete"] = array(
							"title" => t("Delete"),
							"url" => Url::fromRoute(
								"form_filter_fields.delete",
								array(
									"data_type" => $type,
									"content_type_machine_name" => $type_machine_name,
									"control_field" => $control_field,
									"target_field" => $target_field
								)
							)
						);
						$row[] = array(
							"data" => array(
								"#type" => "operations",
								"#links" => $delete_link,
							)
						);

						$output_table_rows[] = $row;
					}
				}
			}
		}

		return $output_table_rows;

		// end of print_table
	}

	// -------------------------------------------------------------------

}