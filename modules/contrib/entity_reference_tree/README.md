# Drupal Entity Reference Tree

A Tree widget of entity reference field  for Drupal

- [Entity Reference Tree]
  * [Overview]
  * [Requirements](#requirements)
  * [Installation](#installation)
  * [Settings](#settings)
  * [Features]
    
## Overview

Drupal entity reference field, such as taxonomy term reference or content reference might have very complex
hierarchy. The autocomplete widget or drop down select box widget implemented by Drupal core doesn't present the relationship between those entities. This module provide a combination of an autocomplete textfield and a tree view for reference field as one widget. This module use JsTree JavaScript library to render a hierarchy tree of those reference entities. 

### Requirements

- Drupal 8.5 or greater
- PHP 7.0 or greater
- JsTree 3.3.7 or greater

### Installation
- Install this module using the normal Drupal module installation process.
- The JsTree JavaScrip library has already been included in module, you don't need to install it separately.

## Settings
 
- The field widget only work with reference field, such as taxonomy term or contents. The widget settings is
  located in the fields form display page (/admin/structure/types/manage/[Content Type]/form-display)  

- Select the 'Entity reference tree widget' for the reference field that you want to.

