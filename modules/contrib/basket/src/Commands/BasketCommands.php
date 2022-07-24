<?php

namespace Drupal\basket\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\Component\Gettext\PoHeader;
use \Drupal\Component\Gettext\PoItem;
use Drupal\Component\Serialization\Yaml;

/**
 * Drush basket commands.
 */
class BasketCommands extends DrushCommands {

  /**
   * Basket translate update po files (basket:po $module_name)
   *
   * @param string $moduleName
   *   The module name.
   *
   * @command basket:po
   * @usage basket:po                     Basket translate update po files
   */
  public function basketPo($moduleName = '---') {
    if (\Drupal::moduleHandler()->moduleExists($moduleName)) {
      $moduleInfo = \Drupal::service('extension.list.module')->getExtensionInfo($moduleName);
      if (empty($moduleInfo['project'])) {
        \Drupal::logger('basket:po')->error($moduleName . '.info.yml not "project" info');
      }
      if (!empty($moduleInfo['project'])) {
        $dir = realpath(drupal_get_path('module', $moduleName)) . '/translations/';
        if (is_dir($dir)) {
          \Drupal::service('file_system')->deleteRecursive($dir);
        }
        @\Drupal::service('file_system')->mkdir($dir, NULL, TRUE);
        foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
          if ($langcode == 'en') {
            continue;
          }

          $strings = $this->getStrings($langcode, $moduleName);

          if (is_dir($dir) && !empty($strings)) {

            $header  = new PoHeader($langcode);
            $header->setProjectName($moduleInfo['project']);
            $header->setLanguageName($langcode);
            $header->setFromString('Plural-Forms: ' . $this->getPluralForm($langcode));

            $uri = $dir . $moduleInfo['project'] . '.' . $langcode . '.po';

            $writer = new PoStreamWriter();
            $writer->setHeader($header);
            $writer->setURI($uri);
            $writer->open();
            foreach($strings as $string){
              $text = (array) $string;
              if ( empty($string->translation) ){
                  continue;
              }
              $item = new PoItem();
              $item->setFromArray($text);
              $writer->writeItem($item);
            }
            $writer->close();

            \Drupal::logger('basket:po')->notice('Finish "' . $langcode . '"');
          }
        }
      }
    }
    else {
      \Drupal::logger('basket:po')->error('Module not found "' . $moduleName . '"');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluralForm($langcode) {
    switch ($langcode) {
      case 'en':
        return 'nplurals=2; plural=(n != 1);';

      default:
        return 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getStrings($langcode, $context = 'basket') {
    $conditions = [
      'language'  => $langcode,
      'context'   => $context
    ];
    $options    = [
      'translated' => TRUE
    ];
    return \Drupal::service('locale.storage')->getTranslations($conditions, $options);
  }

  /**
   * Basket translate update po files (basket:po_update $module_name)
   *
   * @param string $moduleName
   *   The module name.
   *
   * @command basket:po_update
   * @usage basket:po_update                     Basket translate update po files
   */
  public function basketPoUpdate($moduleName = '---') {
    if (!\Drupal::moduleHandler()->moduleExists('locale')) {
      \Drupal::logger('basket:po_update')->error('Module not found "locale"');
    }
    elseif (\Drupal::moduleHandler()->moduleExists($moduleName)) {
      $moduleInfo = \Drupal::service('extension.list.module')->getExtensionInfo($moduleName);
      if (empty($moduleInfo['project'])) {
        \Drupal::logger('basket:po')->error($moduleName . '.info.yml not "project" info');
      }
      if (!empty($moduleInfo['project'])) {
        $dir = drupal_get_path('module', $moduleName) . '/translations/';
        if (is_dir($dir)) {
          \Drupal::moduleHandler()->loadInclude('locale', 'translation.inc');
          \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
          foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
            $file = \Drupal::service('file_system')->scanDirectory($dir, '/.*'.$langcode.'.po$/i');
            if(!empty($file)) {
              $file = reset($file);
              $options = array_merge(_locale_translation_default_update_options(), [
                'langcode'          => $langcode,
              ]);
              $file = locale_translate_file_attach_properties($file, $options);
              $batch = locale_translate_batch_build([$file->uri => $file], $options);
              batch_set($batch);
            }
          }
          drush_backend_batch_process();
        }
      }
    }
    else {
      \Drupal::logger('basket:po_update')->error('Module not found "' . $moduleName . '"');
    }
  }

  /**
   * Basket translate update po files (basket:po_update $module_name)
   *
   * @param string $moduleName
   *   The module name.
   *
   * @command basket:readme
   * @usage basket:readme                 Basket generate readme.md
   */
  public function genedateReadme($moduleName = '- - -') {
    if (!\Drupal::moduleHandler()->moduleExists($moduleName)) {
      return FALSE;
    }
    $items = [];
    // ---
    $hooksFile = drupal_get_path('module', $moduleName) . '/config/basket_install/HOOKs.yml';
    if (file_exists($hooksFile)) {
      $ymldata = Yaml::decode(file_get_contents($hooksFile));
      foreach ($ymldata as $groupKey => $groupInfo) {
        $items[] = '# ' . trim($groupInfo['title']);
        $items[] = '';
        foreach ($groupInfo['lists'] as $hookKey => $hookInfo) {
          $items[] = '#### ' . trim($hookInfo['title']);
          $items[] = '````';
          if(!empty($hookInfo['is_hook'])) {
            $items[] = '/**';
            $items[] = ' * Implements hook_' . $hookKey . '().';
            $items[] = ' * ' . implode(PHP_EOL . ' * ', !empty($hookInfo['descriptions']) ? $hookInfo['descriptions'] : []);
            $items[] = ' */';
            $items[] = 'function HOOK_' . $hookKey . '(' . $hookInfo['params'] . '){';
          } else {
            $items[] = '/**';
            $items[] = ' * Implements hook_' . $hookKey . '_alter().';
            $items[] = ' * ' . implode(PHP_EOL . ' * ', !empty($hookInfo['descriptions']) ? $hookInfo['descriptions'] : []);
            $items[] = ' */';
            $items[] = 'function HOOK_' . $hookKey . '_alter(' . $hookInfo['params'] . '){';
          }
          $items[] = '';
          $items[] = '}';
          $items[] = '````';

          $items[] = '';
        }
        $items[] = '';
        $items[] = '';
        $items[] = '';
      }
    }
    // ---
    file_put_contents(drupal_get_path('module', $moduleName) . '/README.md', trim(implode(PHP_EOL, $items)));
  }

}
