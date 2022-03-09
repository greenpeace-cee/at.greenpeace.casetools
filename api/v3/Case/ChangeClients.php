<?php
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
    'activity_contact' => []
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
      $return_values['activity_contact'][] = change_clients_update_activity_contact($activity_id, $current_client_id, $client_id);
    }
  } catch (CiviCRM_API3_Exception $e) {
    throw new API_Exception('ActivityContact update failed: ', $e->getMessage());
  }
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
 * @return bool
 * @throws CiviCRM_API3_Exception
 */
function change_clients_update_activity_contact($activity_id, $current_client_id, $new_client_id): int
{
  $activity_contact = civicrm_api3('ActivityContact', 'get', [
    'activity_id' => $activity_id,
    'contact_id' => $current_client_id
  ]);
  $activity_contact_id = (int) reset($activity_contact['values'])['id'];
  civicrm_api3('ActivityContact', 'create', [
    'id' => $activity_contact_id,
    'activity_id' => $activity_id,
    'contact_id' => $new_client_id,
  ]);
  return $activity_contact_id;
}
