Form Fields Filter for Drupal 8
===============================

//-------------------------------------------------------------------------

Description:
A module for managing field dependencies. This module extends the functionality of
Business Rules dependent fields.

//-------------------------------------------------------------------------

Features:

//-------------------------------------------------------------------------

Requirements:
* Drupal 8.x

//-------------------------------------------------------------------------

Installation:
1. Copy the entire webform directory the Drupal /modules/ directory.
2. Login as an administrator. Enable the module in the "Manage" -> "Extend".
3. Establish relationships in taxonomy terms by creating a field on the dependent 
vocabulary. Add dependencies on terms.
4. Create a view which gets a term ID from the control vocabulary (contextual 
filter). Rewrite the results so it's in this kind of format:
	tid|Name
 ex. 45|Ball
5. Go to /admin/config/content/form_filter_fields to add the relationship to your
two fields.
6. Test and profit!

//-------------------------------------------------------------------------

Improvement Ideas:
- An "Add" button on the configuration page so the user doesn't submit the entire form.

//-------------------------------------------------------------------------

Support:
Developer email: niles38@yahoo.com

//-------------------------------------------------------------------------

Thank you!
- Janis M