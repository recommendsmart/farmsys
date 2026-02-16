<?php

namespace Drupal\Tests\log\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the Log name pattern.
 *
 * @group Log
 */
class LogNamePatternTest extends LogTestBase {

  use StringTranslationTrait;

  /**
   * Tests creating a log entity without name.
   */
  public function testCreateLogWithoutName() {
    $edit = [
      'status' => 'done',
    ];
    $this->drupalPostForm('log/add/name_pattern', $edit, $this->t('Save'));

    $result = $this->storage
      ->getQuery()
      ->range(0, 1)
      ->execute();
    $log_id = reset($result);
    $log = $this->storage->load($log_id);
    $this->assertEquals($log->label(), $log_id . ' done', 'Log name is the pattern and not the name.');

    $this->drupalGet($log->toUrl('canonical'));
    $this->assertResponse(200);
    $this->assertText($log_id);
  }

  /**
   * Tests creating a log entity with name.
   */
  public function testCreateLogWithName() {
    $name = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $name,
    ];

    $this->drupalPostForm('log/add/name_pattern', $edit, $this->t('Save'));

    $result = $this->storage
      ->getQuery()
      ->range(0, 1)
      ->execute();
    $log_id = reset($result);
    $log = $this->storage->load($log_id);
    $this->assertEquals($log->get('name')->value, $name, 'Log name is the pattern and not the name.');

    $this->drupalGet($log->toUrl('canonical'));
    $this->assertResponse(200);
    $this->assertText($name);
  }

  /**
   * Edit log entity.
   */
  public function testEditLog() {
    $log = $this->createLogEntity(['type' => 'name_pattern']);
    $log->save();

    // Test that a manually set name does not get overwritten.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($log->toUrl('edit-form'), $edit, $this->t('Save'));
    $this->assertText($edit['name[0][value]']);

    // Test that clearing the name forces it to be auto-generated.
    $edit = [
      'name[0][value]' => '',
      'status' => 'pending',
    ];
    $this->drupalPostForm($log->toUrl('edit-form'), $edit, $this->t('Save'));
    $this->assertText($log->id() . ' pending');

    // Test that updating a log with an auto-generated name automatically
    // updates the name.
    $edit = [
      'status' => 'done',
    ];
    $this->drupalPostForm($log->toUrl('edit-form'), $edit, $this->t('Save'));
    $this->assertText($log->id() . ' done');
  }

}
