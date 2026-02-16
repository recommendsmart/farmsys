<?php

namespace Drupal\Tests\account_field_split\Functional;

use Drupal\Core\Field\FieldInputValueNormalizerTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Provides test for split fields.
 *
 * @group account_field_split
 */
class AccountFieldSplitTest extends BrowserTestBase {

  use FieldInputValueNormalizerTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'field',
    'field_ui',
    'account_field_split',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var mixed
   */
  protected $defaultTheme = 'stark';

  /**
   * Test hiding fields in form display, and changing row weights.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testForm() {
    $user = $this->createUser([], 'test', TRUE);
    $this->drupalLogin($user);

    // Form display configuration row weights test.
    $this->drupalGet('admin/config/people/accounts/form-display');
    $session = $this->assertSession();
    $fields = [
      '#name' => 'Username',
      '#mail' => 'E-mail address',
      '#pass' => 'Password',
      '#status' => 'Status',
      '#roles' => 'Roles',
      '#notify' => 'Notify user about new account',
      '#current-pass' => 'Current password',
    ];
    foreach ($fields as $id => $value) {
      $session->elementTextContains('css', $id, $value);
    }
    $edit = [
      'fields[mail][weight]' => -3,
      'fields[pass][weight]' => -2,
      'fields[name][weight]' => -1,
      'fields[roles][region]' => 'hidden'
    ];
    $this->submitForm($edit, 'Save');
    $expected_field_values = [
      'mail' => 'test@test.com',
      'name' => 'tester',
      'pass' => '123',
    ];
    $user = User::create($expected_field_values);
    $user->save();
    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertSession();
    $page = $this->getSession()->getPage();
    $inputs = $page->findAll('css', '#user-form input');
    $expected_fields = [
      0 => 'mail',
      1 => 'pass[pass1]',
      2 => 'pass[pass2]',
      3 => 'name',
    ];
    foreach ($expected_fields as $key => $id) {
      if ($inputs[$key]->getAttribute('name') != $id) {
        $this->expectError();
      }
    }
    if ($page->hasField('roles[authenticated]')) {
      $this->expectError();
    }

    // Edit form values test.
    if ($inputs[0]->getValue() != $expected_field_values['mail']) {
      $this->expectError();
    }
    if ($inputs[3]->getValue() != $expected_field_values['name']) {
      $this->expectError();
    }
    $edit = [
      'mail' => 'updated@test.com',
      'name' => 'updated_username',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertSession();
    $page = $this->getSession()->getPage();
    $inputs = $page->findAll('css', '#user-form input');
    if ($inputs[0]->getValue() != $edit['mail']) {
      $this->expectError();
    }
    if ($inputs[3]->getValue() != $edit['name']) {
      $this->expectError();
    }
  }

}
