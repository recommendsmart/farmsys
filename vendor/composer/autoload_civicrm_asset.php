<?php
global $civicrm_paths, $civicrm_setting;
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
$civicrm_paths['civicrm.vendor']['path'] = $vendorDir;
$civicrm_setting['domain']['userFrameworkResourceURL'] = '[cms.root]/libraries/civicrm/core';
$GLOBALS['civicrm_asset_map']['civicrm/civicrm-core']['src'] = 'F:\\SOFTWARE\\dev\\web\\farmsys\\vendor/civicrm/civicrm-core';
$GLOBALS['civicrm_asset_map']['civicrm/civicrm-core']['dest'] = $baseDir . '/libraries/civicrm\\core';
$GLOBALS['civicrm_asset_map']['civicrm/civicrm-core']['url'] = '/libraries/civicrm/core';
$civicrm_paths['civicrm.root']['path'] = 'F:\\SOFTWARE\\dev\\web\\farmsys\\vendor/civicrm/civicrm-core/';
$civicrm_paths['civicrm.root']['url'] = '/libraries/civicrm/core/';
