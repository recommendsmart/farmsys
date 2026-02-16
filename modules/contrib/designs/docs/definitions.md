# Definitions

Designs can be defined in modules or themes similar to layouts.
Theme designs override module defined designs of the same id.

There are three main ways to define designs:

* Single template
* Combined module templates
* Plugins

Each design can define its own libraries when can be
used elsewhere using the library name
`designs/<design-id>.<library-name>`.

## Single template

A single template can be used for defining designs within the
`designs` directory of a module or theme.

```
├── designs
│  └── atom
│      ├── button
│      │   ├── button.yml
│      │   └── button.html.twig
│      ├── media
│      │   ├── media.yml
│      │   └── media.html.twig
...
│      └────── jumbotron.html.twig
├── designs_test_theme.info.yml
└── designs_test_theme.ui_patterns.yml
```

The content of the single template definition:

```yaml
# The design identifier, this is required and must be a machine name.
id: module_a_provided_design
# The translatable label used to select the design.
label: 1 column design
# The translatable category used to select the design.
category: 'Columns: 1'
# A translatable short description of the design.
description: 'A module provided design'
# The optional relative path based on the YAML location.
path: templates
# The name of the template file used for the design.
template: onecol.html.twig
# Libraries that are considered dependencies for the design.
libraries:
  # Library dependency from another source.
  - theme_a/onecol
  # Library that is design specific, this uses the standard
  # MODULE_NAME.libraries.yml schema. Files that are relative
  # (i.e not prefixed by / or http(s)://) are relative to the
  # template file.
  - test:
      css:
        component:
          css/onecol.css: {}
          /core/misc/print.css: {}
      js:
        /core/misc/form.js: {}
        js/onecol.js: {}
        http://example.com/test.js: { type: external }
      dependencies:
        - core/jquery
# Settings that are targeted toward element attributes and twig
# template logic variables. These are pre-rendered before being
# used in the template, so that empty or boolean values are easy
# to reference.
settings:
  attributes:
    # The required design setting plugin identifier.
    type: attributes
    # A translatable label, overrides the design setting plugin
    # label.
    label: Attributes
    # A translatable description, usage is dependent on the
    # design setting plugin.
    description: Attributes to add to the element.
    # A default value for the design setting, usage is dependent
    # on the design setting plugin.
    default_value: 'id="banner"'
    # A default value for a design setting plugin configuration
    # option.
    existing: FALSE
# Regions are areas in the template allowing content sources displayed.
regions:
  top:
    # A required translatable label defining the region.
    label: Top region
  bottom:
    label: Bottom region
```

## Combined module templates

The combined module templates follow the layouts definition using
`MODULE_NAME.designs.yml` as the discovery mechanism. These
differ from the single templates only in that the id becomes
the key within the file.

```yaml
button:
  label: Button
  category: Atom
media:
  label: Media
  category: Atom
```

## Plugins

Plugins use standard annotations and mimic the definition layout
of the YAML-based single templates.

The javascript and css files are located relative to the location
of the template file, which is the module location plus the path.

```php
/**
 * @Design(
 *   id = "plugin_provided_design",
 *   label = @Translation("Design plugin"),
 *   category = @Translation("Columns: 1"),
 *   description = @Translation("Test design"),
 *   path = "templates",
 *   template = "plugin-provided-design.html.twig",
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region", context = "design_region")
 *     }
 *   }
 * )
 */
```
