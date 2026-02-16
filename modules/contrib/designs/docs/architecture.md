# Architecture

The architecture of the designs module is a plugin-based system for
providing content within templates.

The plugins for the designs module are:

* Design settings
* Design content
* Design sources
* Designs

The render element provides the functionality for displaying
the design pattern.

## Design settings

Design settings convert user configured content into a form more
appropriate for the template, usually as attributes or template
logic variables.

## Design content

Design content plugin take user configuration and provide it as content.

## Design sources

Design source plugins take a render array from a particular source such as
field formatter and provide it in as content.

## Design

These are the plugin form of the [design definitions](definitions.md).
