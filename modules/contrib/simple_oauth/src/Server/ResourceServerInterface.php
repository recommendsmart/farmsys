<?php

namespace Drupal\simple_oauth\Server;

use Symfony\Component\HttpFoundation\Request;

/**
 * The resource server interface.
 */
interface ResourceServerInterface {

  /**
   * Determine the access token validity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object augmented with the token information.
   *
   * @throws \League\OAuth2\Server\Exception\OAuthServerException
   */
  public function validateAuthenticatedRequest(Request $request): Request;

}
