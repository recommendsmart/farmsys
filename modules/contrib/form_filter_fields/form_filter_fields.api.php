<?php

/**
 * @file
 * Hooks related to form filter fields module.
 */

/**
 * @addtogroup hooks
 * @{
 */

// -------------------------------------------------------------------

/**
 * Anything that needs to stick upon form submission or entity edit.
 *
 * @param array &$form
 *   The form array.
 * @param FormState &$form_state
 *   The form_state object.
 */
function hook_form_filter_fields_load(&$form, &$form_state) {
	// do anything with the form or form_state

	// you can figure out what content type it is with the following:
	dpm($form_state->getFormObject()->getEntity()->bundle());
	dpm($form["#entity_type"]);
}

// -------------------------------------------------------------------

/**
 * Ability to modify other aspects of the form upon form change.
 *
 * @param array &$form
 *   The form array.
 * @param FormState &$form_state
 *   The form_state object.
 */
function hook_form_filter_fields_callback_alter(&$form, &$form_state) {
	// do anything with the form or form_state

	// you can figure out what content type it is with the following:
	dpm($form_state->getFormObject()->getEntity()->bundle());
	dpm($form["#entity_type"]);
}

// -------------------------------------------------------------------

/**
 * @} End of "addtogroup hooks".
 */