<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Element\Element;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\Tests\WebAssert;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsNull;
use PHPUnit\Framework\Constraint\LogicalNot;
use WebDriver\Exception;
use WebDriver\Exception\CurlExec;

// cspell:ignore interactable

/**
 * Defines a class with methods for asserting presence of elements during tests.
 */
class JSWebAssert extends WebAssert {

  /**
   * Waits for AJAX request to be completed.
   *
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message for exception.
   *
   * @throws \RuntimeException
   *   When the request is not completed. If left blank, a default message will
   *   be displayed.
   */
  public function assertWaitOnAjaxRequest($timeout = 10000, $message = 'Unable to complete AJAX request.') {
    // Wait for a very short time to allow page state to update after clicking.
    usleep(5000);
    $condition = <<<JS
      (function() {
        function isAjaxing(instance) {
          return instance && instance.ajaxing === true;
        }
        return (
          // Assert no AJAX request is running (via jQuery or Drupal) and no
          // animation is running.
          (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
          (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
        );
      }())
JS;
    $result = $this->session->wait($timeout, $condition);
    if (!$result) {
      throw new \RuntimeException($message);
    }
  }

  /**
   * Asserts that the specific element is visible on the current page.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The document to check against.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function assertElementVisible($selector_type, $selector, ElementInterface $container = NULL) {
    $node = $this->findNode($selector_type, $selector, $container);

    $message = sprintf(
      'Element "%s" is not visible.',
      $this->getMatchingElementRepresentation($selector_type, $selector)
    );
    $this->assertElement($node->isVisible(), $message, $node);
  }

  /**
   * Asserts that the specific element is not visible on the current page.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The document to check against.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function assertElementNotVisible($selector_type, $selector, ElementInterface $container = NULL) {
    $node = $this->findNode($selector_type, $selector, $container);

    $message = sprintf(
      'Element "%s" is not visible.',
      $this->getMatchingElementRepresentation($selector_type, $selector)
    );
    $this->assertElement(!$node->isVisible(), $message, $node);
  }

  /**
   * Asserts that the specific element is not required on the current page.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The document to check against.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function assertElementRequired($selector_type, $selector, ElementInterface $container = NULL) {
    $node = $this->findNode($selector_type, $selector, $container);

    $message = sprintf(
      'Element "%s" is required.',
      $this->getMatchingElementRepresentation($selector_type, $selector)
    );
    $this->assertElement($node->getAttribute('required') == 'required', $message, $node);
  }

  /**
   * Asserts that the specific element is not required on the current page.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The document to check against.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function assertElementOptional($selector_type, $selector, ElementInterface $container = NULL) {
    $node = $this->findNode($selector_type, $selector, $container);

    $message = sprintf(
      'Element "%s" is optional.',
      $this->getMatchingElementRepresentation($selector_type, $selector)
    );
    $this->assertElement(!$node->hasAttribute('required'), $message, $node);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertElement($condition, $message, Element $element) {
    if ($condition) {
      return;
    }

    throw new ElementHtmlException($message, $this->session->getDriver(), $element);
  }

  /**
   * Find a node in the container specified usually the current page.
   *
   * @param string $selector_type
   *   The element selector type (CSS, XPath).
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The document to check against.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The node element if exists in the current container.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  protected function findNode($selector_type, $selector, ElementInterface $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->find($selector_type, $selector);
    if ($node === NULL) {
      if (is_array($selector)) {
        $selector = implode(' ', $selector);
      }
      throw new ElementNotFoundException($this->session->getDriver(), 'element', $selector_type, $selector);
    }
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMatchingElementRepresentation($selector_type, $selector, $plural = FALSE) {
    $pluralization = $plural ? 's' : '';

    if (in_array($selector_type, ['named', 'named_exact', 'named_partial'])
      && is_array($selector) && 2 === count($selector)
    ) {
      return sprintf('%s%s matching locator "%s"', $selector[0], $pluralization, $selector[1]);
    }

    if (is_array($selector)) {
      $selector = implode(' ', $selector);
    }

    return sprintf('element%s matching %s "%s"', $pluralization, $selector_type, $selector);
  }

  /**
   * Waits for the specified selector and returns it when available.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   *
   * @see \Behat\Mink\Element\ElementInterface::findAll()
   */
  public function waitForElement($selector, $locator, $timeout = 10000) {
    return $this->waitForHelper($timeout, function (Element $page) use ($selector, $locator) {
      return $page->find($selector, $locator);
    });
  }

  /**
   * Looks for the specified selector and returns TRUE when it is unavailable.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return bool
   *   TRUE if not found, FALSE if found.
   *
   * @see \Behat\Mink\Element\ElementInterface::findAll()
   */
  public function waitForElementRemoved($selector, $locator, $timeout = 10000) {
    return (bool) $this->waitForHelper($timeout, function (Element $page) use ($selector, $locator) {
      return !$page->find($selector, $locator);
    });
  }

  /**
   * Waits for the specified selector and returns it when available and visible.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found and visible, NULL if not.
   *
   * @see \Behat\Mink\Element\ElementInterface::findAll()
   */
  public function waitForElementVisible($selector, $locator, $timeout = 10000) {
    return $this->waitForHelper($timeout, function (Element $page) use ($selector, $locator) {
      $element = $page->find($selector, $locator);
      if (!empty($element) && $element->isVisible()) {
        return $element;
      }
      return NULL;
    });
  }

  /**
   * Waits for the specified text and returns TRUE when it is available.
   *
   * @param string $text
   *   The text to wait for.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return bool
   *   TRUE if found, FALSE if not found.
   */
  public function waitForText($text, $timeout = 10000) {
    return (bool) $this->waitForHelper($timeout, function (Element $page) use ($text) {
      $actual = preg_replace('/\s+/u', ' ', $page->getText());
      $regex = '/' . preg_quote($text, '/') . '/ui';
      return (bool) preg_match($regex, $actual);
    });
  }

  /**
   * Wraps waits in a function to catch curl exceptions to continue waiting.
   *
   * @param int $timeout
   *   Timeout in milliseconds.
   * @param callable $callback
   *   Callback, which result is both used as waiting condition and returned.
   *
   * @return mixed
   *   The result of $callback.
   */
  private function waitForHelper(int $timeout, callable $callback) {
    WebDriverCurlService::disableRetry();
    $wrapper = function (Element $element) use ($callback) {
      try {
        return call_user_func($callback, $element);
      }
      catch (CurlExec $e) {
        return NULL;
      }
    };
    $result = $this->session->getPage()->waitFor($timeout / 1000, $wrapper);
    WebDriverCurlService::enableRetry();
    return $result;
  }

  /**
   * Waits for a button (input[type=submit|image|button|reset], button) with
   * specified locator and returns it.
   *
   * @param string $locator
   *   The button ID, value or alt string.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  public function waitForButton($locator, $timeout = 10000) {
    return $this->waitForElement('named', ['button', $locator], $timeout);
  }

  /**
   * Waits for a link with specified locator and returns it when available.
   *
   * @param string $locator
   *   The link ID, title, text or image alt.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  public function waitForLink($locator, $timeout = 10000) {
    return $this->waitForElement('named', ['link', $locator], $timeout);
  }

  /**
   * Waits for a field with specified locator and returns it when available.
   *
   * @param string $locator
   *   The input ID, name or label for the field (input, textarea, select).
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  public function waitForField($locator, $timeout = 10000) {
    return $this->waitForElement('named', ['field', $locator], $timeout);
  }

  /**
   * Waits for an element by its id and returns it when available.
   *
   * @param string $id
   *   The element ID.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  public function waitForId($id, $timeout = 10000) {
    return $this->waitForElement('named', ['id', $id], $timeout);
  }

  /**
   * Waits for the jQuery autocomplete delay duration.
   *
   * @see https://api.jqueryui.com/autocomplete/#option-delay
   */
  public function waitOnAutocomplete() {
    // Wait for the autocomplete to be visible.
    return $this->waitForElementVisible('css', '.ui-autocomplete li');
  }

  /**
   * Tests that a node, or its specific corner, is visible in the viewport.
   *
   * Note: Always set the viewport size. This can be done in your test with
   * \Behat\Mink\Session->resizeWindow(). Drupal CI JavaScript tests by default
   * use a viewport of 1024x768px.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   * @param bool|string $corner
   *   (Optional) The corner to test:
   *   topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   * @param string $message
   *   (optional) A message for the exception.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   When the element doesn't exist.
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element is not visible in the viewport.
   */
  public function assertVisibleInViewport($selector_type, $selector, $corner = FALSE, $message = 'Element is not visible in the viewport.') {
    $node = $this->session->getPage()->find($selector_type, $selector);
    if ($node === NULL) {
      if (is_array($selector)) {
        $selector = implode(' ', $selector);
      }
      throw new ElementNotFoundException($this->session->getDriver(), 'element', $selector_type, $selector);
    }

    // Check if the node is visible on the page, which is a prerequisite of
    // being visible in the viewport.
    if (!$node->isVisible()) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }

    $result = $this->checkNodeVisibilityInViewport($node, $corner);

    if (!$result) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }
  }

  /**
   * Tests that a node, or its specific corner, is not visible in the viewport.
   *
   * Note: the node should exist in the page, otherwise this assertion fails.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   * @param bool|string $corner
   *   (Optional) Corner to test: topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   * @param string $message
   *   (optional) A message for the exception.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   When the element doesn't exist.
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element is not visible in the viewport.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertVisibleInViewport()
   */
  public function assertNotVisibleInViewport($selector_type, $selector, $corner = FALSE, $message = 'Element is visible in the viewport.') {
    $node = $this->session->getPage()->find($selector_type, $selector);
    if ($node === NULL) {
      if (is_array($selector)) {
        $selector = implode(' ', $selector);
      }
      throw new ElementNotFoundException($this->session->getDriver(), 'element', $selector_type, $selector);
    }

    $result = $this->checkNodeVisibilityInViewport($node, $corner);

    if ($result) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }
  }

  /**
   * Check the visibility of a node, or its specific corner.
   *
   * @param \Behat\Mink\Element\NodeElement $node
   *   A valid node.
   * @param bool|string $corner
   *   (Optional) Corner to test: topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   *
   * @return bool
   *   Returns TRUE if the node is visible in the viewport, FALSE otherwise.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When an invalid corner specification is given.
   */
  private function checkNodeVisibilityInViewport(NodeElement $node, $corner = FALSE) {
    $xpath = $node->getXpath();

    // Build the JavaScript to test if the complete element or a specific corner
    // is in the viewport.
    switch ($corner) {
      case 'topLeft':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.top <= ly &&
              r.left >= 0 &&
              r.left <= lx
            )
          }
JS;
        break;

      case 'topRight':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.top <= ly &&
              r.right >= 0 &&
              r.right <= lx
            );
          }
JS;
        break;

      case 'bottomRight':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.bottom >= 0 &&
              r.bottom <= ly &&
              r.right >= 0 &&
              r.right <= lx
            );
          }
JS;
        break;

      case 'bottomLeft':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.bottom >= 0 &&
              r.bottom <= ly &&
              r.left >= 0 &&
              r.left <= lx
            );
          }
JS;
        break;

      case FALSE:
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.left >= 0 &&
              r.bottom <= ly &&
              r.right <= lx
            );
          }
JS;
        break;

      // Throw an exception if an invalid corner parameter is given.
      default:
        throw new UnsupportedDriverActionException($corner, $this->session->getDriver());
    }

    // Build the full JavaScript test. The shared logic gets the corner
    // specific test logic injected.
    $full_javascript_visibility_test = <<<JS
      (function(t){
        var w = window,
        d = document,
        e = d.documentElement,
        n = d.evaluate("$xpath", d, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue,
        r = n.getBoundingClientRect(),
        lx = (w.innerWidth || e.clientWidth),
        ly = (w.innerHeight || e.clientHeight);

        return t(r, lx, ly);
      }($test_javascript_function));
JS;

    // Check the visibility by injecting and executing the full JavaScript test
    // script in the page.
    return $this->session->evaluateScript($full_javascript_visibility_test);
  }

  /**
   * Passes if the raw text IS NOT found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  public function assertNoEscaped($raw) {
    $this->responseNotContains($this->escapeHtml($raw));
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  public function assertEscaped($raw) {
    $this->responseContains($this->escapeHtml($raw));
  }

  /**
   * Escapes HTML for testing.
   *
   * Drupal's Html::escape() uses the ENT_QUOTES flag with htmlspecialchars() to
   * escape both single and double quotes. With WebDriverTestBase testing the
   * browser is automatically converting &quot; and &#039; to double and single
   * quotes respectively therefore we can not escape them when testing for
   * escaped HTML.
   *
   * @param $raw
   *   The raw string to escape.
   *
   * @return string
   *   The string with escaped HTML.
   *
   * @see Drupal\Component\Utility\Html::escape()
   */
  protected function escapeHtml($raw) {
    return htmlspecialchars($raw, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  /**
   * Asserts that no matching element exists on the page after a wait.
   *
   * @param string $selector_type
   *   The element selector type (css, xpath).
   * @param string|array $selector
   *   The element selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) The exception message.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   When an element still exists on the page.
   */
  public function assertNoElementAfterWait($selector_type, $selector, $timeout = 10000, $message = 'Element exists on the page.') {
    $start = microtime(TRUE);
    $end = $start + ($timeout / 1000);
    $page = $this->session->getPage();

    do {
      $node = $page->find($selector_type, $selector);
      if (empty($node)) {
        return;
      }
      usleep(100000);
    } while (microtime(TRUE) < $end);

    throw new ElementHtmlException($message, $this->session->getDriver(), $node);
  }

  /**
   * Determines if an exception is due to an element not being clickable.
   *
   * @param \WebDriver\Exception $exception
   *   The exception to check.
   *
   * @return bool
   *   TRUE if the exception is due to an element not being clickable,
   *   interactable or visible.
   */
  public static function isExceptionNotClickable(Exception $exception): bool {
    return (bool) preg_match('/not (clickable|interactable|visible)/', $exception->getMessage());
  }

  /**
   * Asserts that a status message exists after wait.
   *
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   * @param int $timeout
   *   Optional timeout in milliseconds, defaults to 10000.
   */
  public function statusMessageExistsAfterWait(string $type = NULL, int $timeout = 10000): void {
    $selector = $this->buildJavascriptStatusMessageSelector(NULL, $type);
    $status_message_element = $this->waitForElement('xpath', $selector, $timeout);
    if ($type) {
      $failure_message = sprintf('A status message of type "%s" does not appear on this page, but it should.', $type);
    }
    else {
      $failure_message = 'A status message does not appear on this page, but it should.';
    }
    // There is no Assert::isNotNull() method, so we make our own constraint.
    $constraint = new LogicalNot(new IsNull());
    Assert::assertThat($status_message_element, $constraint, $failure_message);
  }

  /**
   * Asserts that a status message does not exist after wait.
   *
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   * @param int $timeout
   *   Optional timeout in milliseconds, defaults to 10000.
   */
  public function statusMessageNotExistsAfterWait(string $type = NULL, int $timeout = 10000): void {
    $selector = $this->buildJavascriptStatusMessageSelector(NULL, $type);
    $status_message_element = $this->waitForElement('xpath', $selector, $timeout);
    if ($type) {
      $failure_message = sprintf('A status message of type "%s" appears on this page, but it should not.', $type);
    }
    else {
      $failure_message = 'A status message appears on this page, but it should not.';
    }
    Assert::assertThat($status_message_element, Assert::isNull(), $failure_message);
  }

  /**
   * Asserts that a status message containing given string exists after wait.
   *
   * @param string $message
   *   The partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   * @param int $timeout
   *   Optional timeout in milliseconds, defaults to 10000.
   */
  public function statusMessageContainsAfterWait(string $message, string $type = NULL, int $timeout = 10000): void {
    $selector = $this->buildJavascriptStatusMessageSelector($message, $type);
    $status_message_element = $this->waitForElement('xpath', $selector, $timeout);
    if ($type) {
      $failure_message = sprintf('A status message of type "%s" containing "%s" does not appear on this page, but it should.', $type, $message);
    }
    else {
      $failure_message = sprintf('A status message containing "%s" does not appear on this page, but it should.', $type);
    }
    // There is no Assert::isNotNull() method, so we make our own constraint.
    $constraint = new LogicalNot(new IsNull());
    Assert::assertThat($status_message_element, $constraint, $failure_message);
  }

  /**
   * Asserts that no status message containing given string exists after wait.
   *
   * @param string $message
   *   The partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   * @param int $timeout
   *   Optional timeout in milliseconds, defaults to 10000.
   */
  public function statusMessageNotContainsAfterWait(string $message, string $type = NULL, int $timeout = 10000): void {
    $selector = $this->buildJavascriptStatusMessageSelector($message, $type);
    $status_message_element = $this->waitForElement('xpath', $selector, $timeout);
    if ($type) {
      $failure_message = sprintf('A status message of type "%s" containing "%s" appears on this page, but it should not.', $type, $message);
    }
    else {
      $failure_message = sprintf('A status message containing "%s" appears on this page, but it should not.', $message);
    }
    Assert::assertThat($status_message_element, Assert::isNull(), $failure_message);
  }

  /**
   * Builds a xpath selector for a message with given type and text.
   *
   * The selector is designed to work with the Drupal.theme.message
   * template defined in message.js in addition to status-messages.html.twig
   * in the system module.
   *
   * @param string|null $message
   *   The optional message or partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   *
   * @return string
   *   The xpath selector for the message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when $type is not an allowed type.
   */
  private function buildJavascriptStatusMessageSelector(string $message = NULL, string $type = NULL): string {
    $allowed_types = [
      'status',
      'error',
      'warning',
      NULL,
    ];
    if (!in_array($type, $allowed_types, TRUE)) {
      throw new \InvalidArgumentException(sprintf("If a status message type is specified, the allowed values are 'status', 'error', 'warning'. The value provided was '%s'.", $type));
    }

    if ($type) {
      $class = 'messages--' . $type;
    }
    else {
      $class = 'messages__wrapper';
    }

    if ($message) {
      $js_selector = $this->buildXPathQuery('//div[contains(@class, :class) and contains(., :message)]', [
        ':class' => $class,
        ':message' => $message,
      ]);
    }
    else {
      $js_selector = $this->buildXPathQuery('//div[contains(@class, :class)]', [
        ':class' => $class,
      ]);
    }

    // We select based on WebAssert::buildStatusMessageSelector() or the
    // js_selector we have just built.
    return $this->buildStatusMessageSelector($message, $type) . ' | ' . $js_selector;
  }

}
