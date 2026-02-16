<?php

namespace Drupal\feeds_as_config\Form;

use Drupal\content_as_config\Form\ExportBase;

/**
 * Exports feed content to configuration.
 */
class FeedsExportForm extends ExportBase {
  use FeedsImportExportTrait;

}
