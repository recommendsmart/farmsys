<?php

namespace Drupal\basket\Query;

use Drupal\Core\Database\Connection;
use Drupal\basket\Basket;
use Drupal\views\Views;

/**
 * Class BasketQuery.
 */
class BasketQuery {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Drupal\basket\Basket definition.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;
  
  /**
   * @var array
   */
  protected $getQuery;
  
  /**
   * @var array
   */
  protected $nodeTypeFields;
  
  /**
   * {@inheritdoc}
   */
  const EMPTY_PRICE_FIELD = 'EMPTY_PRICE_FIELD';

  /**
   * Constructs a new BasketQuery object.
   */
  public function __construct(Connection $database, Basket $Basket) {
    $this->db = $database;
    $this->basket = $Basket;
    
    $this->nodeTypeFields = [];
    $resultNodeTypes = $this->db->select('basket_node_types', 'n')
        ->fields('n')
        ->execute()->fetchAll();
    if(!empty($resultNodeTypes)) {
      foreach ($resultNodeTypes as $result) {
        if(!isset($result->price_field)) {
					$result->price_field = $this::EMPTY_PRICE_FIELD;
				}
        if(!empty($result->count_field))      $this->nodeTypeFields['qty'][$result->count_field][$result->type] = $result->type;
        if(!empty($result->price_field))      $this->nodeTypeFields['price'][$result->price_field][$result->type] = $result->type;
        if(!empty($result->image_field))      $this->nodeTypeFields['image'][$result->image_field][$result->type] = $result->type;
        if(!empty($result->image_field))      $this->nodeTypeFields['imageDef'][$result->type] = $result->image_field;
      }
    }
  }
  
  /**
   * @param null $entityId
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getQtyQuery($entityId = NULL) {
    $cKey = 'qty_' . ($entityId ?? 'all');
    if (!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = NULL;
      if (!empty($this->nodeTypeFields['qty'])) {
        $queries = [];
        foreach ($this->nodeTypeFields['qty'] as $cF => $keyNodeTypes) {
          $cFs = explode('->', $cF);
          switch (count($cFs)) {
            case 1:
              $cFs = reset($cFs);
              if ($this->tableExists('node__' . $cFs)) {
                $queries[$cF] = $this->db->select('node__' . $cFs, $cFs);
                $queries[$cF]->addExpression($cFs . '.entity_id', 'nid');
                $queries[$cF]->addExpression('SUM(' . $cFs . '.' . $cFs . '_value)', 'count');
                $queries[$cF]->groupBy($cFs . '.entity_id');
                if(!is_null($entityId)) {
                  $queries[$cF]->condition($cFs . '.entity_id', $entityId);
                }
              }
              break;
            case 2:
              if ($this->tableExists("node__$cFs[0]") && $this->tableExists("paragraph__$cFs[1]")) {
                $queries[$cF] = $this->db->select("node__$cFs[0]", $cFs[0]);
                $queries[$cF]->addExpression("$cFs[0].entity_id", 'nid');
                $queries[$cF]->leftJoin("paragraph__$cFs[1]", $cFs[1], "$cFs[1].entity_id = $cFs[0].$cFs[0]_target_id");
                $queries[$cF]->addExpression("SUM(IF($cFs[1].$cFs[1]_value > 0, $cFs[1].$cFs[1]_value, 0))", 'count');
                $queries[$cF]->groupBy("$cFs[0].entity_id");
                if(!is_null($entityId)) {
									$queries[$cF]->condition($cFs[0] . '.entity_id', $entityId);
								}
              }
              break;
          }
        }
        if (!empty($queries)) {
          $getQuery = NULL;
          foreach ($queries as $subQuery) {
            if (is_null($getQuery)) {
              $getQuery = $subQuery;
            }
            else {
              $getQuery->union($subQuery, 'ALL');
            }
          }
          $this->getQuery[$cKey] = $getQuery;
        }
      }
    }
    return $this->getQuery[$cKey];
  }
  
  /**
   * @param $view
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function qtyViewsJoin(&$view) {
    if (empty($view->query->relationships[$view->field . '_getCountsQuery'])) {
      if (!empty($subQueryCount = $this->getQtyQuery())) {
        // ---
        $subQuery = $this->db->select('node_field_data', 'n');
        $subQuery->leftJoin($subQueryCount, 'getCountQuery', 'getCountQuery.nid = n.nid');
        $subQuery->addExpression('n.nid', 'nid');
        $subQuery->addExpression("COALESCE(getCountQuery.count, 0)", 'count');
        // ---
        $join = Views::pluginManager('join')->createInstance('standard', [
          'type'          => 'LEFT',
          'table'         => $subQuery,
          'field'         => 'nid',
          'left_table'    => 'node_field_data',
          'left_field'    => 'nid',
          'operator'      => '=',
        ]);
        $view->query->addRelationship($view->field . '_getCountsQuery', $join, 'node_field_data');
        $view->query->addField($view->field . '_getCountsQuery', 'count', 'basket_node_counts');
      }
    }
  }
  
  /**
   * @param $view
   * @param $order
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function qtyViewsJoinSort(&$view, $order) {
    $this->qtyViewsJoin($view);
    if (!empty($view->query->relationships[$view->field . '_getCountsQuery'])) {
      $view->query->addOrderBy(NULL, $view->field . '_getCountsQuery.count', $order, '_getCountsSort');
    }
  }
  
  
  
  public function getPriceQuery($keyPriceField = 'MIN', $entityId = NULL) {
    $cKey = 'price_' . $keyPriceField . '_' . ($entityId ?? 'all');
    if (!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = NULL;
      if (!empty($this->nodeTypeFields['price'])) {
        $queries = [];
        foreach ($this->nodeTypeFields['price'] as $pF => $keyNodeTypes) {
          $queries[$pF] = NULL;
          if ($pF == $this::EMPTY_PRICE_FIELD) {
            // Alter.
            \Drupal::moduleHandler()->alter('basketNodeGetPriceField', $queries[$pF], $keyNodeTypes, $entityId);
            // End alter.
          }
          if (empty($queries[$pF])) {
            $pFs = explode('->', $pF);
            switch (count($pFs)) {
              case 1:
                $pFs = reset($pFs);
                if ($this->tableExists('node__' . $pFs)) {
                  $queries[$pF] = $this->db->select('node__' . $pFs, $pFs);
                  $queries[$pF]->addExpression($pFs . '.entity_id', 'nid');
                  $queries[$pF]->addExpression($pFs . '.' . $pFs . '_value', 'price');
                  $queries[$pF]->addExpression($pFs . '.' . $pFs . '_currency', 'currency');
                  $queries[$pF]->addExpression(0, 'pid');
                  // Price convert.
                  if ($this->isPriceConvert()) {
                    $queries[$pF]->innerJoin('basket_currency', 'bcDef', 'bcDef.id = ' . $this->basket->currency()->getCurrent());
                    $queries[$pF]->innerJoin('basket_currency', 'bc', 'bc.id = ' . $pFs . '.' . $pFs . '_currency');
                    $queries[$pF]->addExpression($pFs . '.' . $pFs . '_value*(bc.rate/bcDef.rate)', 'priceConvert');
                    $queries[$pF]->addExpression($pFs . '.' . $pFs . '_old_value*(bc.rate/bcDef.rate)', 'priceConvertOld');
                  }
                  else {
                    $queries[$pF]->addExpression($pFs . '.' . $pFs . '_value', 'priceConvert');
                    $queries[$pF]->addExpression($pFs . '.' . $pFs . '_old_value', 'priceConvertOld');
                  }
                  // Price convert AND.
                  if ($keyPriceField == 'FIRST') {
                    $queries[$pF]->condition($pFs . '.delta', 0);
                  }
                  if(!is_null($entityId)) {
                    $queries[$pF]->condition($pFs . '.entity_id', $entityId);
                  }
                }
                break;

              case 2:
                if ($this->tableExists("node__$pFs[0]") && $this->tableExists("paragraph__$pFs[1]")) {
                  $queries[$pF] = $this->db->select("node__$pFs[0]", $pFs[0]);
                  $queries[$pF]->addExpression("$pFs[0].entity_id", 'nid');
                  $queries[$pF]->leftJoin("paragraph__$pFs[1]", $pFs[1], "$pFs[1].entity_id = $pFs[0].$pFs[0]_target_id");
                  $queries[$pF]->addExpression("$pFs[1].$pFs[1]_value", 'price');
                  $queries[$pF]->addExpression("$pFs[1].$pFs[1]_currency", 'currency');
                  $queries[$pF]->addExpression("$pFs[1].entity_id", 'pid');
                  // Price convert.
                  if ($this->isPriceConvert()) {
                    $queries[$pF]->innerJoin('basket_currency', 'bcDef', 'bcDef.id = ' . $this->basket->currency()->getCurrent());
                    $queries[$pF]->innerJoin('basket_currency', 'bc', "bc.id = $pFs[1].$pFs[1]_currency");
                    $queries[$pF]->addExpression("$pFs[1].$pFs[1]_value*(bc.rate/bcDef.rate)", 'priceConvert');
                    $queries[$pF]->addExpression("$pFs[1].$pFs[1]_old_value*(bc.rate/bcDef.rate)", 'priceConvertOld');
                  }
                  else {
                    $queries[$pF]->addExpression("$pFs[1].$pFs[1]_value", 'priceConvert');
                    $queries[$pF]->addExpression("$pFs[1].$pFs[1]_old_value", 'priceConvertOld');
                  }
                  // Price convert AND.
                  if ($keyPriceField == 'FIRST') {
                    $queries[$pF]->condition("$pFs[0].delta", 0);
                  }
                  if(!is_null($entityId)) {
								  	$queries[$pF]->condition($pFs[0] . '.entity_id', $entityId);
								  }
                }
                break;
            }
          }
          if (empty($queries[$pF])) {
            unset($queries[$pF]);
          }
        }
        if (!empty($queries)) {
          $getQuery = NULL;
          foreach ($queries as $subQuery) {
            if (is_null($getQuery)) {
              $getQuery = $subQuery;
            }
            else {
              $getQuery->union($subQuery, 'ALL');
            }
          }
          $this->getQuery[$cKey] = $getQuery;
        }
      }
    }
    return $this->getQuery[$cKey];
  }
  
  /**
   * @return bool
   */
  public function isPriceConvert() {
    if (!isset($this->getQuery['isPriceConvert'])) {
      $this->getQuery['isPriceConvert'] = FALSE;
      $currency = $this->basket->currency()->tree();
      if (!empty($currency) && count($currency) > 1) {
        $this->getQuery['isPriceConvert'] = TRUE;
      }
    }
    return $this->getQuery['isPriceConvert'];
  }
  
  /**
   * @param $view
   * @param string $keyPriceField
   * @param array $filter
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function priceViewsJoin(&$view, $keyPriceField = 'MIN', $filter = []) {
		if (empty($view->query->relationships[$view->field . '_getPriceQuery_' . $keyPriceField])) {
			if (!empty($getPriceQuery = $this->getPriceQuery($keyPriceField))) {
        // ---
        $joinType = 'LEFT';
        // ---
        $subQuery = $this->db->select('node_field_data', 'n');
        $subQuery->leftJoin($getPriceQuery, 'getPriceQuery', 'getPriceQuery.nid = n.nid');
        $subQuery->addExpression('n.nid', 'nid');
        $subQuery->addExpression("COALESCE($keyPriceField(getPriceQuery.priceConvert), 0)", 'priceConvert');
        $subQuery->addExpression("COALESCE($keyPriceField(getPriceQuery.priceConvertOld), 0)", 'priceConvertOld');
        $subQuery->groupBy('n.nid');
        if (!empty($filter['min']) || !empty($filter['max'])) {
          if (!empty($filter['min'])) {
            $subQuery->where('ROUND(COALESCE(getPriceQuery.priceConvert, 0), 0) >= ' . $filter['min']);
          }
          if (!empty($filter['max'])) {
            $subQuery->where('ROUND(COALESCE(getPriceQuery.priceConvert, 0), 0) <= ' . $filter['max']);
          }
          $joinType = 'INNER';
        }
        // ---
        $join = Views::pluginManager('join')->createInstance('standard', [
          'type'          => $joinType,
          'table'         => $subQuery,
          'field'         => 'nid',
          'left_table'    => 'node_field_data',
          'left_field'    => 'nid',
          'operator'      => '=',
        ]);
        $view->query->addRelationship($view->field . '_getPriceQuery_' . $keyPriceField, $join, 'node_field_data');
        $view->query->addField($view->field . '_getPriceQuery_' . $keyPriceField, 'priceConvert', 'basket_node_priceConvert');
			}
      else {
        $view->query->addField(NULL, 0, 'basket_node_priceConvert');
      }
    }
  }
  
  /**
   * @param $view
   * @param $order
   * @param string $keyPriceField
   * @param array $filter
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function priceViewsJoinSort(&$view, $order, $keyPriceField = 'MIN', $filter = []) {
    $this->priceViewsJoin($view, $keyPriceField);
    if (!empty($view->query->relationships[$view->field . '_getPriceQuery_' . $keyPriceField])) {
      $view->query->addOrderBy(NULL, $view->field . '_getPriceQuery_' . $keyPriceField . '.priceConvert', $order, '_getPriceSort');
    }
  }
  
  /**
   * @param $entity
   * @param string $keyPriceField
   * @param array $filter
   *
   * @return object|null
   */
  public function getNodePriceMin($entity, $keyPriceField = 'MIN', $filter = []) {
    $getPrice = NULL;
    if (!empty($getPriceQuery = $this->getPriceQuery($keyPriceField, $entity->id()))) {
			if($keyPriceField == 'FIRST') {
				$keyPriceField = 'MIN';
			}
      $query = $this->db->select('node_field_data', 'n');
      $query->condition('n.nid', $entity->id());
      $query->leftJoin($getPriceQuery, 'getPriceQuery', 'getPriceQuery.nid = n.nid');
      $query->addExpression('n.nid', 'nid');
      $query->addExpression("COALESCE($keyPriceField(getPriceQuery.priceConvert), 0)", 'priceConvert');
      $query->addExpression("COALESCE($keyPriceField(getPriceQuery.priceConvertOld), 0)", 'priceConvertOld');
      $query->addExpression('MIN(COALESCE(getPriceQuery.pid, 0))', 'pid');
      switch ($keyPriceField) {
        case'MIN':
          $query->orderBy('priceConvert', 'ASC');
          break;
        case'MAX':
          $query->orderBy('priceConvert', 'DESC');
          break;
      }
      $query->groupBy('n.nid');
      if (!empty($filter['min']) || !empty($filter['max'])) {
        if (!empty($filter['min'])) {
          $query->where('ROUND(COALESCE(getPriceQuery.priceConvert, 0), 0) >= ' . $filter['min']);
        }
        if (!empty($filter['max'])) {
          $query->where('ROUND(COALESCE(getPriceQuery.priceConvert, 0), 0) <= ' . $filter['max']);
        }
      }
      $query->range(0, 1);
      $getPrice = $query->execute()->fetchObject();
    }
    return $getPrice;
  }
  
  /**
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getUserSumQuery() {
    $cKey = 'userSum';
    if (!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = $this->db->select('node_field_data', 'n');
      $this->getQuery[$cKey]->fields('n', ['uid']);
      // basket_orders.
      $this->getQuery[$cKey]->innerJoin('basket_orders', 'b', 'b.nid = n.nid');
      // basket_currency.
      $this->getQuery[$cKey]->innerJoin('basket_currency', 'bc', 'bc.id = b.currency');
      $this->getQuery[$cKey]->innerJoin('basket_currency', 'bc_def', 'bc_def.id = ' . $this->basket->currency()->getCurrent());
      // ---
      $this->getQuery[$cKey]->addExpression('SUM(b.price*(bc.rate/bc_def.rate))', 'total_sum');
      $this->getQuery[$cKey]->groupBy('n.uid');
    }
    return $this->getQuery[$cKey];
  }
  
  /**
   * @param $view
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function userSumViewsJoin(&$view) {
    if (empty($view->query->relationships[$view->field . '_getUserSumQuery'])) {
      if (!empty($subQuery = $this->getUserSumQuery())) {
        $join = Views::pluginManager('join')->createInstance('standard', [
          'type'          => 'LEFT',
          'table'         => $subQuery,
          'field'         => 'uid',
          'left_table'    => 'users_field_data',
          'left_field'    => 'uid',
          'operator'      => '=',
        ]);
        $view->query->addRelationship($view->field . '_getUserSumQuery', $join, 'users_field_data');
        $view->query->addField($view->field . '_getUserSumQuery', 'total_sum', $view->field . '_total_sum');
      }
    }
  }
  
  /**
   * @param $view
   * @param $order
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function userSumViewsJoinSort(&$view, $order) {
    $this->userSumViewsJoin($view);
    if (!empty($view->query->relationships[$view->field . '_getUserSumQuery'])) {
      $view->query->addOrderBy($view->field . '_getUserSumQuery', 'total_sum', $order);
    }
  }
  
  /**
   * @param null $entityId
   *
   * @return \Drupal\Core\Database\Query\SelectInterface|mixed|null
   */
  public function getImgQuery($entityId = NULL) {
    $cKey = 'img_' . ($entityId ?? 'all');
    if (!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = NULL;
      if (!empty($getNodeTypesFields = $this->nodeTypeFields['image'])) {
        $queries = [];
        foreach ($getNodeTypesFields as $iF => $keyNodeTypes) {
          $iFs = explode('->', $iF);
          switch (count($iFs)) {
            case 1:
              $iFs = reset($iFs);
              if ($this->tableExists('node__' . $iFs)) {
                $queries[$iF] = $this->db->select('node__' . $iFs, $iFs);
                $queries[$iF]->condition($iFs . '.delta', 0);
                $queries[$iF]->addExpression($iFs . '.entity_id', 'nid');
                $queries[$iF]->addExpression($iFs . '.' . $iFs . '_target_id', 'fid');
                if(!is_null($entityId)) {
                  $queries[$iF]->condition($iFs . '.entity_id', $entityId);
                }
                $GLOBALS['imageFieldName'] = $iFs;
              }
              break;

            case 2:
              if ($this->tableExists("node__$iFs[0]") && $this->tableExists("paragraph__$iFs[1]")) {
                $queries[$iF] = $this->db->select("node__$iFs[0]", $iFs[0]);
                $queries[$iF]->addExpression("$iFs[0].entity_id", 'nid');
                $queries[$iF]->condition("$iFs[0].delta", 0);
                $queries[$iF]->leftJoin("paragraph__$iFs[1]", $iFs[1], "$iFs[1].entity_id = $iFs[0].$iFs[0]_target_id");
                $queries[$iF]->addExpression("$iFs[1].$iFs[1]_target_id", 'fid');
                if(!is_null($entityId)) {
                  $queries[$iF]->condition($iFs[0] . '.entity_id', $entityId);
                }
                $GLOBALS['imageFieldName'] = $iFs[0];
              }
              break;
          }
        }
        if (!empty($queries)) {
          $getQuery = NULL;
          foreach ($queries as $subQuery) {
            if (is_null($getQuery)) {
              $getQuery = $subQuery;
            }
            else {
              $getQuery->union($subQuery, 'ALL');
            }
          }
          $this->getQuery[$cKey] = $getQuery;
        }
      }
    }
    return $this->getQuery[$cKey];
  }
  
  /**
   * @param $view
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function ImgViewsJoin(&$view) {
    if (empty($view->query->relationships[$view->field . '_getFirstImgQuery'])) {
      if (!empty($subQuery = $this->getImgQuery())) {
        $join = Views::pluginManager('join')->createInstance('standard', [
          'type'          => 'LEFT',
          'table'         => $subQuery,
          'field'         => 'nid',
          'left_table'    => 'node_field_data',
          'left_field'    => 'nid',
          'operator'      => '=',
        ]);
        $view->query->addRelationship($view->field . '_getFirstImgQuery', $join, 'node_field_data');
        $view->query->addField($view->field . '_getFirstImgQuery', 'fid', 'basket_node_first_img');
      }
    }
  }
  
  /**
   * @param $entity
   *
   * @return int
   */
  public function getNodeImgFirst($entity) {
    $cKey = 'img_node_' . $entity->id();
    if(!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = 0;
      $GLOBALS['imageFieldName'] = NULL;
      
      if (!empty($subQuery = $this->getImgQuery($entity->id()))) {
        $query = $this->db->select('node_field_data', 'n');
        $query->condition('n.nid', $entity->id());
        /*subQuery*/
        $query->innerJoin($subQuery, 'subQuery', 'subQuery.nid = n.nid');
        $query->addExpression('COALESCE(subQuery.fid, 0)', 'fid');
        $query->range(0, 1);
        $this->getQuery[$cKey] = $query->execute()->fetchField();
        if(empty($this->getQuery[$cKey]) && !empty($GLOBALS['imageFieldName'])) {
          $this->getQuery[$cKey] = $this->getDefFid($entity);
        }
      }
    }
    return $this->getQuery[$cKey];
  }
  
  /**
   * @param $entity
   *
   * @return int
   */
  public function getDefFid($entity) {
    $cKey = 'def_image_' . $entity->bundle();
    if(!isset($this->getQuery[$cKey])) {
      $this->getQuery[$cKey] = 0;
      if(!empty($imageField = $this->nodeTypeFields['imageDef'][$entity->bundle()])) {
        $imgFieldLines = explode('->', $imageField);
        $fieldStorageConfig = NULL;
        switch (count($imgFieldLines)) {
          case 1:
            $fieldStorageConfig = $this->db->select('config', 'c')
              ->fields('c', ['data'])
              ->condition('c.name', 'field.field.node.' . $entity->bundle() . '.' . $imgFieldLines[0], 'LIKE')
              ->execute()->fetchField();
            break;
  
          case 2:
            $fieldStorageConfig = $this->db->select('config', 'c')
              ->fields('c', ['data'])
              ->condition('c.name', 'field.field.paragraph.%', 'LIKE')
              ->condition('c.name', '%.' . $imgFieldLines[1], 'LIKE')
              ->execute()->fetchField();
            break;
        }
        if (!empty($fieldStorageConfig)) {
          $fieldStorageConfig = unserialize($fieldStorageConfig);
          if (!empty($fieldStorageConfig['settings']['default_image']['uuid'])) {
            $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $fieldStorageConfig['settings']['default_image']['uuid']);
            if (!empty($file)) {
              $this->getQuery[$cKey] = $file->id();
            }
          }
        }
      }
    }
    return $this->getQuery[$cKey];
  }
  
  
  /**
   * @param $table
   *
   * @return bool
   */
  private function tableExists($table) {
    if(!isset($this->getQuery['tableExists'][$table])) {
      $this->getQuery['tableExists'][$table] = $this->db->schema()->tableExists($table);
    }
    return $this->getQuery['tableExists'][$table];
  }

}
