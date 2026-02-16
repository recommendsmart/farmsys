# LRN : Login Registration Name
This module allows to set rules to generate user name from firstname/middlename/lastname at user creation.

Important: when this module is installed the 'name' field in user creation is hidden.

# Configuration
Configuration is available in Configuration > System > Login realname (/admin/config/system/lrn-config).

The first part of the configuration is the same 3 times, one for each name part (first, last, middlename):
 * first/last/middlename field: the technical field name that contains the name part. Must be a textfield. Note: if empty this name part is ignored (treated as empty)
 * transliterate: if set the name part will be transliterated to ASCII before usage
 * lower/uppercase rule: how to change name part case. Possibilities: do nothing ; all uppercase ; all lowercase ; first letter uppercase
 * rewrite rule: how to modify name part content. Possibilites: do nothing ; remove all non-alphanumeric characters ; keep only first letter ; keep first letter of each words (i.e. "Jean-Pierre" becomes "JP")
 * max length: after previous actions if name part is longer than this value it is truncated
After these elements:
 * build string: the string that describes how to compound name parts to create user name. This string is used as user name, with "{FN}" (resp. {LN} and {MN}) replaced with the resulting firstname (resp. lastname and middlename)
 * final max length: if the generated user name is longer than this value it is truncated
 * allow numbering: the module checks if user name still exists. If allow numbering is not checked an existing user name leads to an error (account is not created). If checked the module will try to add "2" at the end of the user name (and "3" if the name with "2" also exists, and so on)
 * max numbering: if "allow numbering" is checked, the module will stop to increase numbers (see previous) when reaching this value

# Behavior
When validating, the module gets the firstname field content (resp. middlename / lastname), applies transliteration to ASCII (if requested), applies case changes, applies selected rewrite rule, and troncates if too long.
Then the module replaces in the "build string" the "{FN}" (resp. {MN} / {LN}) by the previously computed string.
At last it troncates the final result if too long.
Verification stage: the module checks if a user still exists with this name. If yes the module tries the same
name with a "2" at the end, and then a "3"… until it find a free name or max numbering is reached.

# Examples
With first name set to "transliterate" + "all lowercase" + "keep first letter of each words", and last name set to "transliterate" + "all lowercase" + "remove all non-alphanumeric", and a build string "{FN}{LN}":
Firstname: Jean-Pierre
Lastname: Luminet
Generated username: jpluminet

Firstname: Jean-Pioche
Lastname: Luminet
Generated username: jpluminet
-> still exists
Generated username: jpluminet2


Firstname : Jean-Baptiste Alphonse
Lastname: Dechauffour de Boisduval
Generated username: jbadechauffourdeboisduval


