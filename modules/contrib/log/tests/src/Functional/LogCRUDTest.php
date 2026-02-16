<?php

namespace Drupal\Tests\log\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the Log CRUD.
 *
 * @group Log
 */
class LogCRUDTest extends LogTestBase {

  use StringTranslationTrait;

  /**
   * Fields are displayed correctly.
   */
  public function testFieldsVisibility() {
    $this->drupalGet('log/add/default');
    $this->assertResponse('200');
    $assert_session = $this->assertSession();
    $assert_session->fieldExists('name[0][value]');
    $assert_session->fieldExists('timestamp[0][value][date]');
    $assert_session->fieldExists('timestamp[0][value][time]');
    $assert_session->fieldExists('status');
    $assert_session->fieldExists('revision_log_message[0][value]');
    $assert_session->fieldExists('uid[0][target_id]');
    $assert_session->fieldExists('created[0][value][date]');
    $assert_session->fieldExists('created[0][value][time]');
  }

  /**
   * Create Log entity.
   */
  public function testCreateLog() {
    $assert_session = $this->assertSession();
    $name = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $name,
    ];

    $this->drupalPostForm('log/add/default', $edit, $this->t('Save'));

    $result = $this->storage
      ->getQuery()
      ->range(0, 1)
      ->execute();
    $log_id = reset($result);
    $log = $this->storage->load($log_id);
    $this->assertEquals($log->get('name')->value, $name, 'Log has been saved.');

    $assert_session->pageTextContains("Saved log: $name");
    $assert_session->pageTextContains($name);
  }

  /**
   * Display log entity.
   */
  public function testViewLog() {
    $edit = [
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
    ];
    $log = $this->createLogEntity($edit);
    $log->save();

    $this->drupalGet($log->toUrl('canonical'));
    $this->assertResponse(200);

    $this->assertText($edit['name']);
    $this->assertRaw(\Drupal::service('date.formatter')->format(\Drupal::time()->getRequestTime()));
  }

  /**
   * Edit log entity.
   */
  public function testEditLog() {
    $log = $this->createLogEntity();
    $log->save();

    $edit = [
      'name[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($log->toUrl('edit-form'), $edit, $this->t('Save'));

    $this->assertText($edit['name[0][value]']);
  }

  /**
   * Delete log entity.
   */
  public function testDeleteLog() {
    $log = $this->createLogEntity();
    $log->save();

    $label = $log->getName();
    $log_id = $log->id();

    $this->drupalPostForm($log->toUrl('delete-form'), [], $this->t('Delete'));
    $this->assertRaw($this->t('The @entity-type %label has been deleted.', [
      '@entity-type' => $log->getEntityType()->getSingularLabel(),
      '%label' => $label,
    ]));
    $this->assertNull($this->storage->load($log_id));
  }

}
