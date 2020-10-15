<?php

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Activity.Fileoncase API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Activity_FileoncaseTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  /**
   * Set up for headless tests.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
   */
  public function setUpHeadless() {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test Activity.fileoncase
   */
  public function testFileOnCase() {
    $contact_id = reset($this->callAPISuccess('Contact', 'create', [
      'email'        => 'random@example.org',
      'contact_type' => 'Individual',
    ])['values'])['id'];

    // create dummy case type
    $this->callAPISuccess('CaseType', 'create', [
      'title'      => 'Sample',
      'name'       => 'Sample',
      'is_active'  => 1,
      'weight'     => 1,
      'definition' => [
        'activityTypes' => [
          ['name' => 'Meeting'],
        ],
      ],
    ]);

    // create case
    $case_id = reset($this->callAPISuccess('Case', 'create', [
      'contact_id'   => $contact_id,
      'case_type_id' => 'Sample',
      'subject'      => 'Test'
    ])['values'])['id'];

    // create activity (without case)
    $activity_id = reset($this->callAPISuccess('Activity', 'create', [
      'activity_type_id'  => 'Meeting',
      'source_contact_id' => $contact_id,
      'target_contact_id' => $contact_id,
    ])['values'])['id'];

    // file activity on case
    $this->callAPISuccess('Activity', 'fileoncase', [
      'id'      => $activity_id,
      'case_id' => $case_id
    ]);

    $this->assertEquals(
      1,
      $this->callAPISuccess('Activity', 'getcount', [
        'id' => $activity_id, 'case_id' => $case_id
      ]),
      'Activity should be filed on case'
    );
  }

}
