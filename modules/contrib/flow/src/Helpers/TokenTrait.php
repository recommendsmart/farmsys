<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\Token;

/**
 * Trait for components making use of the Token service.
 */
trait TokenTrait {

  /**
   * The service name of the Token service.
   *
   * @var string
   */
  protected static $tokenServiceName = 'token';

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Set the Token service.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   */
  public function setToken(Token $token): void {
    $this->token = $token;
  }

  /**
   * Get the Token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The Token service.
   */
  public function getToken(): Token {
    if (!isset($this->token)) {
      $this->token = \Drupal::service(self::$tokenServiceName);
    }
    return $this->token;
  }

  /**
   * Convenience method for running Token replacement.
   *
   * This method takes care of mapping the correct Token type.
   *
   * @param mixed $text
   *   The text to replace, which should be a string or an object that can be
   *   cast to a string.
   * @param mixed $data
   *   The data to use for Token replacement. If you have an entity, just use
   *   that one as argument. This method maps to its according Token type.
   * @param array $options
   *   (optional) An array of options to pass to the Token service. By default,
   *   empty tokens will be cleared from the text.
   *
   * @return string
   *   The processed text as string.
   */
  public function tokenReplace($text, $data, array $options = ['clear' => TRUE]): string {
    $token_data = [];

    if (!is_array($data)) {
      $data = [$data];
    }
    foreach ($data as $key => $value) {
      $token_type = $value instanceof EntityInterface ? $this->getTokenTypeForEntityType($value->getEntityTypeId()) : $key;
      $token_data[$token_type] = $value;
    }
    return trim($this->getToken()->replace($text, $token_data, $options));
  }

  /**
   * Get the Token type for the given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The token type.
   */
  public function getTokenTypeForEntityType(string $entity_type_id): string {
    $token_type = $entity_type_id;
    if (\Drupal::hasService('token.entity_mapper')) {
      /** @var \Drupal\token\TokenEntityMapperInterface $token_entity_mapper */
      $token_entity_mapper = \Drupal::service('token.entity_mapper');
      $token_type = $token_entity_mapper->getTokenTypeForEntityType($entity_type_id, TRUE);
    }
    elseif ($definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id, FALSE)) {
      $token_type = $definition->get('token_type') ?: $entity_type_id;
    }
    return $token_type;
  }

}
