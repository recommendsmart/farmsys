
## SUMMARY

Banana Dashboard is a developer tool to create a page with bunch of 
icons with links.

There is no admin UI or feature exports. The configuration is stored 
in a yaml file.

## CONFIGURATION

* As the Banana Dashboard uses YamlDiscovery which allows plugins to be 
defined in yaml files. In your custom module, add a file with name 
custom_module_name.banana_dashboard.yml at the root 
of your module folder, where 'custom_module_name' is the name of the 
custom module.

* Following keys are supported. 
Refer to example banana_dashboard.banana_dashboard.yml 
file included in module for more details about configuration.

  - dashboard : Dashboard menu item definition.
  - dashboard_menu_groups : Group headings for individual links in Dashboard.
  - dashboard_menu : Individual links to be exposed on dashboard.

## TROUBLESHOOTING

* If no icons shows up in Dashboard:

  - Check if Fontawesome library is installed properly.
