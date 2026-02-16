<?php

namespace Drupal\Tests\log\Functional;

/**
 * Tests the Log form actions.
 *
 * @group Log
 */
class LogActionsTest extends LogTestBase {

  /**
   * Tests cloning a single log.
   */
  public function testCloneSingleLog() {
    $timestamp = \Drupal::time()->getRequestTime();

    $log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 1, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_clone_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to clone this log?'));
    $this->assertText($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[month]'] = date('n', $new_timestamp);
    $edit_clone['date[year]'] = date('Y', $new_timestamp);
    $edit_clone['date[day]'] = date('j', $new_timestamp);
    $this->drupalPostForm(NULL, $edit_clone, $this->t('Clone'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Cloned 1 log'));
    $logs = $this->storage->loadMultiple();
    $this->assertEqual(count($logs), 2, 'There are two logs in the system.');
    $timestamps = [];
    foreach ($logs as $log) {
      $timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEqual($timestamps, [$timestamp, $new_timestamp], 'Timestamp on the new log has been updated.');
  }

  /**
   * Tests cloning multiple logs.
   */
  public function testCloneMultipleLogs() {
    $timestamps = [];
    $expected_timestamps = [];
    $timestamp = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime('+1 day', $timestamp);
      $timestamps[] = $timestamp;
      $expected_timestamps[] = $timestamp;
      $log = $this->createLogEntity([
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();
    }

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 3, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_clone_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to clone these logs?'));
    $this->assertText($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[month]'] = date('n', $new_timestamp);
    $edit_clone['date[year]'] = date('Y', $new_timestamp);
    $edit_clone['date[day]'] = date('j', $new_timestamp);
    $this->drupalPostForm(NULL, $edit_clone, $this->t('Clone'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Cloned 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEqual(count($logs), 6, 'There are six logs in the system.');
    for ($i = 1; $i <= 3; $i++) {
      $expected_timestamps[] = $new_timestamp;
    }
    $log_timestamps = [];
    foreach ($logs as $log) {
      $log_timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEqual($log_timestamps, $expected_timestamps, 'Timestamp on the new logs has been updated.');
  }

  /**
   * Tests rescheduling a single log to an absolute date.
   */
  public function testRescheduleSingleLogAbsolute() {
    $timestamp = \Drupal::time()->getRequestTime();

    $log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 1, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to reschedule this log?'));
    $this->assertText($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_reschedule = [];
    $edit_reschedule['date[month]'] = date('n', $new_timestamp);
    $edit_reschedule['date[year]'] = date('Y', $new_timestamp);
    $edit_reschedule['date[day]'] = date('j', $new_timestamp);
    $this->drupalPostForm(NULL, $edit_reschedule, $this->t('Reschedule'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Rescheduled 1 log'));

    $logs = $this->storage->loadMultiple();
    $this->assertEqual($num_of_logs, 1, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEqual($log->get('timestamp')->value, $new_timestamp, 'Timestamp on the log has changed.');
    $this->assertEqual($log->get('status')->value, 'pending', 'Log has been set to pending.');
  }

  /**
   * Tests rescheduling multiple logs to an absolute date.
   */
  public function testRescheduleMultipleLogsAbsolute() {
    $timestamps = [];
    $expected_timestamps = [];
    $timestamp = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));
      $timestamps[] = $timestamp;
      $expected_timestamps[] = $timestamp;
      $log = $this->createLogEntity([
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();
    }

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 3, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to reschedule these logs?'));
    $this->assertText($this->t('New date'));

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['date[month]'] = date('n', $new_timestamp);
    $edit_reschedule['date[year]'] = date('Y', $new_timestamp);
    $edit_reschedule['date[day]'] = date('j', $new_timestamp);
    $this->drupalPostForm(NULL, $edit_reschedule, $this->t('Reschedule'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Rescheduled 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEqual(count($logs), 3, 'There are three logs in the system.');
    foreach ($logs as $log) {
      $this->assertEqual($log->get('timestamp')->value, $new_timestamp, 'Timestamp on the log has changed.');
      $this->assertEqual($log->get('status')->value, 'pending', 'Log has been set to pending.');
    }
  }

  /**
   * Tests rescheduling a single log to an relative date.
   */
  public function testRescheduleSingleLogRelative() {
    $timestamp = \Drupal::time()->getRequestTime();

    $log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 1, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to reschedule this log?'));
    $this->assertText($this->t('New date'));

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $this->drupalPostForm(NULL, $edit_reschedule, $this->t('Reschedule'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log/reschedule');
    $this->assertText($this->t('Please enter the amount of time for rescheduling.'));

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = 1;
    $edit_reschedule['time'] = 'day';
    $this->drupalPostForm(NULL, $edit_reschedule, $this->t('Reschedule'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Rescheduled 1 log'));

    $logs = $this->storage->loadMultiple();
    $this->assertEqual($num_of_logs, 1, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEqual($log->get('timestamp')->value, $new_timestamp, 'Timestamp on the log has changed.');
    $this->assertEqual($log->get('status')->value, 'pending', 'Log has been set to pending.');
  }

  /**
   * Tests rescheduling multiple logs to an relative date.
   */
  public function testRescheduleMultipleLogsRelative() {
    $timestamp = \Drupal::time()->getRequestTime();
    $timestamps = [];
    $expected_timestamps = [];
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime('+1 day', $timestamp);
      $new_timestamp = strtotime('-1 month', $timestamp);
      $timestamps[] = $timestamp;
      $expected_timestamps[] = $new_timestamp;
      $log = $this->createLogEntity([
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();
    }

    $num_of_logs = $this->storage->getQuery()->count()->execute();
    $this->assertEqual($num_of_logs, 3, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalPostForm('admin/content/log', $edit, $this->t('Apply to selected items'));
    $this->assertResponse(200);
    $this->assertText($this->t('Are you sure you want to reschedule these logs?'));
    $this->assertText($this->t('New date'));

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = -1;
    $edit_reschedule['time'] = 'month';
    $this->drupalPostForm(NULL, $edit_reschedule, $this->t('Reschedule'));
    $this->assertResponse(200);
    $this->assertUrl('admin/content/log');
    $this->assertText($this->t('Rescheduled 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEqual(count($logs), 3, 'There are three logs in the system.');
    $log_timestamps = [];
    foreach ($logs as $log) {
      $log_timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEqual($log_timestamps, $expected_timestamps, 'Logs have been rescheduled');
  }

}
