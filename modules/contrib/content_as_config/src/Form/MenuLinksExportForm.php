<?php

namespace Drupal\content_as_config\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Exports menu link content to configuration.
 */
class MenuLinksExportForm extends ExportBase {

  use MenuLinksImportExportTrait;

  /**
   * {@inheritdoc}
   */
  protected function getListElements(): array {
    $export_list = [];
    $entities = $this->entityTypeManager->getStorage('menu')
      ->loadMultiple();
    foreach ($entities as $entity) {
      $export_list[$entity->id()] = $entity->label();
    }
    return $export_list;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['export_list']['#title'] = $this->t('Export terms from these menus:');
    return $form;
  }

}
