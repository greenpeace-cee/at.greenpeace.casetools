<?php

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class api_v3_Case_ChangeClientsTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
      ->apply(TRUE);
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

  public function testChangeClients() {
    // Create contacts
    $contactIDs = [];

    foreach ([1, 2] as $i) {
      $contactIDs[] = $this->callAPISuccess('Contact', 'create', [
        'contact_type' => "Individual",
        'email'        => "random-$i@example.org",
        'first_name'   => 'Test',
        'last_name'    => "Contact #$i",
      ])['id'];
    }

    // Create case type
    $this->callAPISuccess('CaseType', 'create', [
      'title'      => 'TestCaseType',
      'name'       => 'TestCaseType',
      'is_active'  => 1,
      'weight'     => 1,
      'definition' => [
        'activityTypes' => [
          [ 'name' => 'Meeting' ],
        ],
      ],
    ]);

    // Create case for contact #1
    $caseID = $this->callAPISuccess('Case', 'create', [
      'contact_id'   => $contactIDs[0],
      'case_type_id' => 'TestCaseType',
      'subject'      => 'TestCase'
    ])['id'];

    // Create case activities for contact #1
    $activityIDs = [];

    foreach ([1, 2, 3] as $i) {
      $activityIDs[] = $this->callAPISuccess('Activity', 'create', [
        'activity_type_id'  => 'Meeting',
        'case_id'           => $caseID,
        'source_contact_id' => $contactIDs[0],
        'target_contact_id' => $contactIDs[0],
      ])['id'];
    }

    // Change case client to contact #2
    $this->callAPISuccess('Case', 'change_clients', [
      'id'        => $caseID,
      'client_id' => [$contactIDs[1]],
    ]);

    // Assert that case contact has changed
    $caseContactCount_1 = $this->callAPISuccessGetCount('CaseContact', [
      'case_id'    => $caseID,
      'contact_id' => $contactIDs[0],
    ]);

    $this->assertEquals(0, $caseContactCount_1, "Contact #1 should not be linked to the case anymore");

    $caseContactCount_2 = $this->callAPISuccessGetCount('CaseContact', [
      'case_id'    => $caseID,
      'contact_id' => $contactIDs[1],
    ]);

    $this->assertEquals(1, $caseContactCount_2, "Contact #2 should be linked to the case now");

    // Assert that case activity contacts have changed
    foreach ($activityIDs as $activityID) {
      $activityContactCount_1 = $this->callAPISuccessGetCount('ActivityContact', [
        'activity_id' => $activityID,
        'contact_id'  => $contactIDs[0],
      ]);

      $this->assertEquals(0, $activityContactCount_1, "Contact #1 should not be linked to the case activity anymore");

      $activityContactCount_2 = $this->callAPISuccessGetCount('ActivityContact', [
        'activity_id' => $activityID,
        'contact_id'  => $contactIDs[1],
      ]);

      $this->assertEquals(2, $activityContactCount_2, "Contact #2 should be linked to the case activities now");
    }
  }

}

?>
