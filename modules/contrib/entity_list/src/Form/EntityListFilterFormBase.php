<?php

namespace Drupal\entity_list\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class EntityListFilterFormBase.
 */
class EntityListFilterFormBase extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_list_filter_form_base';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state){
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $params = $this->getRequest()->query->all();

    $excluded = [
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];

    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array($key, $excluded)) {
        $params[$key] = $value;
      }
    }

    $this->unsetEmptyValues($params);

    if (!empty($params['page'])) {
      unset($params['page']);
    }

    $url = Url::fromRoute('<current>', $params);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Avoid long urls by removing any empty parameter.
   *
   * @param array $values
   *   The values to filter.
   */
  private function unsetEmptyValues(array &$values): void {
    foreach ($values as $key => &$value) {
      if (is_array($value)) {
        $this->unsetEmptyValues($value);
      }
      if (empty($value) || $value === '0') {
        unset($values[$key]);
      }
    }
  }
}
