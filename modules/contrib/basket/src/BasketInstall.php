<?php

namespace Drupal\basket;

use Drupal\Component\Serialization\Yaml;

/**
 * {@inheritdoc}
 */
class BasketInstall {

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Create default statuses.
    $this->createList('basket.status.terms.yml', 'status');
    // Create default fin statuses.
    $this->createList('basket.fin_status.terms.yml', 'fin_status');
    // Create base currency.
    $this->createList('basket.currency.yml', 'currency');
  }

  /**
   * {@inheritdoc}
   */
  private function createList($file_name, $type) {
    $file_path = drupal_get_path('module', 'basket') . '/config/basket_install/' . $file_name;
    if (file_exists($file_path)) {
      $yml = file_get_contents($file_path);
      if (!empty($yml)) {
        $ymldata = Yaml::decode($yml);
        if (!empty($ymldata)) {
          switch ($type) {
            case'status':
            case'fin_status':
              foreach ($ymldata as $name => $row) {
                \Drupal::database()->merge('basket_terms')
                  ->key([
                    'type'      => $type,
                    'name'      => trim($name),
                  ])
                  ->fields([
                    'type'      => $type,
                    'name'      => $name,
                    'color'     => $row['color'],
                    'default'   => !empty($row['default']) ? 1 : NULL,
                    'weight'    => $row['weight'],
                  ])
                  ->execute();
              }
              break;

            case'currency':
              foreach ($ymldata as $name => $row) {
                \Drupal::database()->merge('basket_currency')
                  ->key([
                    'iso'      => $row['iso'],
                  ])
                  ->fields([
                    'name'      => $name,
                    'iso'       => $row['iso'],
                    'rate'      => $row['rate'],
                    'name_prefix' => $row['prefix'],
                    'locked'    => !empty($row['locked']) ? 1 : NULL,
                    'default'   => !empty($row['default']) ? 1 : NULL,
                    'weight'    => 0,
                  ])
                  ->execute();
              }
              break;
          }
        }
      }
    }
  }

}
