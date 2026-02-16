<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Implements Yasm Datatables helper.
 */
class Datatables implements DatatablesInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The CDN url to access the language files.
   *
   * @var string
   */
  private $localeCdn = 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/';

  /**
   * The locale JSON file if exists.
   */
  public function getLocale() {
    try {
      $currentLanguage = $this->languageManager->getCurrentLanguage();
      $langCode = $currentLanguage->getId();

      $localeFilename = $this->getLocaleFilename($langCode);
      if ($localeFilename) {
        $url = $this->localeCdn . $localeFilename . '.json';
        $response = $this->httpClient->get($url);

        return (200 == $response->getStatusCode()) ? $url : '';
      }

      return '';
    }
    catch (Exception $e) {
      return '';
    }
  }
    /**
     * Maps drupal langcodes with datatables available language filenames.
     */
  private function getLocaleFilename(string $langCode) {
      $map = [
        'lug' => 'Ganda',
        'af' => 'af',
        'am' => 'am',
        'ar' => 'ar',
        'az' => 'az-AZ',
        'be' => 'be',
        'bg' => 'bg',
        'bn' => 'bn',
        'bs' => 'bs-BA',
        'ca' => 'ca',
        'co' => 'co',
        'cs' => 'cs',
        'cy' => 'cy',
        'da' => 'da',
        'de' => 'de-DE',
        'el' => 'el',
        'en' => 'en-GB',
        'eo' => 'eo',
        'es-AR' => 'es-AR',
        'es-CL' => 'es-CL',
        'es-CO' => 'es-CO',
        'es' => 'es-ES',
        'es-MX' => 'es-MX',
        'et' => 'et',
        'eu' => 'eu',
        'fa' => 'fa',
        'fi' => 'fi',
        'fil' => 'fil',
        'fr' =>  'fr-FR',
        'ga' =>  'ga',
        'gl' =>  'gl',
        'gu' =>  'gu',
        'he' =>  'he',
        'hi' =>  'hi',
        'hr' =>  'hr',
        'hu' =>  'hu',
        'hy' =>  'hy',
        'id-ALT' => 'id-ALT',
        'id' => 'id',
        'is' => 'is',
        'it' => 'it-IT',
        'ja' => 'ja',
        'jv' => 'jv',
        'ka' => 'ka',
        'kk' => 'kk',
        'km' => 'km',
        'kn' => 'kn',
        'ko' => 'ko',
        'ku' => 'ku',
        'ky' => 'ky',
        'lo' => 'lo',
        'lt' => 'lt',
        'lv' => 'lv',
        'mk' => 'mk',
        'mn' => 'mn',
        'mr' => 'mr',
        'ms' => 'ms',
        'ne' => 'ne',
        'nl' => 'nl-NL',
        'no-NB' => 'no-NB',
        'no' => 'no-NO',
        'pa' => 'pa',
        'pl' => 'pl',
        'ps' => 'ps',
        'pt-BR' => 'pt-BR',
        'pt' => 'pt-PT',
        'rm' => 'rm',
        'ro' => 'ro',
        'ru' => 'ru',
        'si' => 'si',
        'sk' => 'sk',
        'sl' => 'sl',
        'snd' => 'snd',
        'sq' => 'sq',
        'sr-SP' => 'sr-SP',
        'sr' => 'sr',
        'sv' => 'sv-SE',
        'sw' => 'sw',
        'ta' => 'ta',
        'te' => 'te',
        'tg' => 'tg',
        'th' => 'th',
        'tr' => 'tr',
        'ug' => 'ug',
        'uk' => 'uk',
        'ur' => 'ur',
        'uz-CR' => 'uz-CR',
        'uz' => 'uz',
        'vi' => 'vi',
        'zh-HANT' => 'zh-HANT',
        'zh' => 'zh',
      ];

      return $map[$langCode] ?? false;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    ClientInterface $http_client
  ) {
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('http_client')
    );
  }

}
