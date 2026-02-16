<?php

namespace Drupal\aggrid\Utils;

/**
 * Class HelperFunctions.
 */
class HelperFunctions
{
  
  /**
   * Validate a JSON string.
   * If there is no error, then it will return NULL
   *
   * @param $string
   * @return mixed
   */
  public function json_validate($string)
  {
    // Default the return.
    $error = NULL;
    
    // Decode the JSON data.
    $result = json_decode($string);
    
    // Switch and check possible JSON errors
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
        $error = NULL; // JSON is valid // No error has occurred
        break;
      case JSON_ERROR_DEPTH:
        $error = t('The maximum stack depth has been exceeded.');
        break;
      case JSON_ERROR_STATE_MISMATCH:
        $error = t('Invalid or malformed JSON.');
        break;
      case JSON_ERROR_CTRL_CHAR:
        $error = t('Control character error, possibly incorrectly encoded.');
        break;
      case JSON_ERROR_SYNTAX:
        $error = t('Syntax error, malformed JSON.');
        break;
      // PHP >= 5.3.3
      case JSON_ERROR_UTF8:
        $error = t('Malformed UTF-8 characters, possibly incorrectly encoded.');
        break;
      // PHP >= 5.5.0
      case JSON_ERROR_RECURSION:
        $error = t('One or more recursive references in the value to be encoded.');
        break;
      // PHP >= 5.5.0
      case JSON_ERROR_INF_OR_NAN:
        $error = t('One or more NAN or INF values in the value to be encoded.');
        break;
      case JSON_ERROR_UNSUPPORTED_TYPE:
        $error = t('A value of a type that cannot be encoded was given.');
        break;
      default:
        $error = t('Unknown JSON error occured.');
        break;
    }
    
    // Send info.
    return $error;
  }
}