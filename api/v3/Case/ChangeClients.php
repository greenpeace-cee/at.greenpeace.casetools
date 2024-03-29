<?php

use Civi\Api4\Activity;
use Civi\Api4\CaseActivity;
use CRM_Casetools_ExtensionUtil as E;

/**
 * Case.change_clients API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_case_change_clients_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'Case ID',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1
  ];

  $spec['client_id'] = [
    'name'         => 'client_id',
    'title'        => 'Client IDs',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Case.change_clients API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_case_change_clients($params)
{
  if (count($params['client_id']) > 1) {
    throw new \Civi\API\Exception\NotImplementedException("No multi-client cases implemented.");
  }
  $return_values = [
    'id' => $params['id'],
    'client_id' => $params['client_id'],
    'rows' => []
  ];
  if (is_array($params['client_id'])) {
    foreach ($params['client_id'] as $client_id) {
      $return_values['rows'][] = change_clients_single_row($params['id'], (int) $client_id);
    }
  } else {
    $return_values['rows'][] = change_clients_single_row($params['id'], (int) $params['client_id']);
  }
  return civicrm_api3_create_success($return_values, $params, 'Case', 'change_clients');
}

/**
 * Change client single row (one client)
 *
 * @param $case_id
 * @param $client_id
 * @return bool
 * @throws API_Exception
 */
function change_clients_single_row($case_id, $client_id) : array
{
  $return_values = [
    'activity_id' => [],
    'activity_contact' => [],
    'relationship' => [],
  ];
  try {
    $case_contact = civicrm_api3('CaseContact', 'get', [ 'case_id' => $case_id ]);
    $current_client_id = reset($case_contact['values'])['contact_id'];
    civicrm_api3('CaseContact', 'delete', [ 'id' => reset($case_contact['values'])['id'] ]);
    civicrm_api3('CaseContact', 'create', [
      'case_id' => $case_id,
      'contact_id' => $client_id,
    ]);
  } catch (CiviCRM_API3_Exception $e) {
    throw new API_Exception('CaseContact change failed: ', $e->getMessage());
  }
  $return_values['activity_id'] = change_clients_get_case_activities($case_id);
  try {
    foreach ($return_values['activity_id'] as $activity_id) {
      $return_values['activity_contact'] = array_merge(
        $return_values['activity_contact'],
        change_clients_update_activity_contacts($activity_id, $current_client_id, $client_id)
      );
    }
  } catch (CiviCRM_API3_Exception $e) {
    throw new API_Exception('ActivityContact update failed: ', $e->getMessage());
  }
  $return_values['relationship'] = change_clients_change_case_relationships($case_id, $current_client_id, $client_id);
  $return_values['reassigned_case_activity'] = change_clients_add_reassigned_activity($case_id, $current_client_id, $client_id);
  return $return_values;
}

/**
 * Get case activities associated to the case
 *
 * @param $case_id
 * @return array
 */
function change_clients_get_case_activities($case_id): array
{
  $case_activity_ids = [];
  $case_activity_query = "
      SELECT civicrm_case_activity.activity_id
      FROM civicrm_case_activity
      WHERE civicrm_case_activity.case_id = %1";
  $dao = CRM_Core_DAO::executeQuery($case_activity_query, [
    1 => [$case_id, 'Integer']
  ]);
  while ($dao->fetch()) {
    $case_activity_ids[] = (int) $dao->activity_id;
  }
  return $case_activity_ids;
}

/**
 * Update activity contact to new client
 *
 * @param $activity_id
 * @param $current_client_id
 * @param $new_client_id
 * @return array
 * @throws CiviCRM_API3_Exception
 */
function change_clients_update_activity_contacts($activity_id, $current_client_id, $new_client_id): array
{
  $activity_contact = civicrm_api3('ActivityContact', 'get', [
    'activity_id' => $activity_id,
    'contact_id' => $current_client_id
  ]);

  $activity_contact_ids = [];

  foreach($activity_contact['values'] as $activity_contact) {
    $activity_contact_ids[] = civicrm_api3('ActivityContact', 'create', [
      'id' => $activity_contact['id'],
      'activity_id' => $activity_id,
      'contact_id' => $new_client_id,
    ])['id'];
  }

  return $activity_contact_ids;
}

/**
 * Move case coordinator relationship to new client
 *
 * @param $case_id
 * @param $current_client_id
 * @param $new_client_id
 *
 * @return array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function change_clients_change_case_relationships($case_id, $current_client_id, $new_client_id): array {
  $result = \Civi\Api4\Relationship::update(FALSE)
    ->addWhere('case_id', '=', $case_id)
    ->addWhere('contact_id_a', '=', $current_client_id)
    ->addWhere('relationship_type_id:name', '=', 'Case Coordinator is')
    ->addValue('contact_id_a', $new_client_id)
    ->execute()
    ->getArrayCopy();
  return array_column($result, 'id');
}

function change_clients_add_reassigned_activity($case_id, $current_client_id, $new_client_id): array {
  return Activity::create(FALSE)
    ->addValue('activity_type_id:name', 'Reassigned Case')
    ->addValue('source_contact_id', CRM_Core_Session::singleton()->getLoggedInContactID())
    ->addValue('subject', ts(
      'Case %1 reassigned client from contact id %2 to contact id %3.',
      [
        1 => $case_id,
        2 => $current_client_id,
        3 => $new_client_id,
      ]
    ))
    ->addValue('activity_date_time', 'now')
    ->addValue('status_id:name', 'Completed')
    ->addChain('case_activity', CaseActivity::create()
      ->addValue('activity_id', '$id')
      ->addValue('case_id', $case_id)
    )
    ->execute()
    ->first();
}
