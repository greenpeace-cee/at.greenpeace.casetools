<?php
use CRM_Casetools_ExtensionUtil as E;

/**
 * Activity.fileoncase API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_activity_fileoncase_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'Activity ID',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.aliases'  => ['activity_id']
  ];

  $spec['case_id'] = [
    'name'         => 'case_id',
    'title'        => 'Case ID',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Activity.fileoncase API
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
function civicrm_api3_activity_fileoncase($params) {
  $activity = new CRM_Activity_DAO_Activity();
  $activity->id = $params['id'];
  if (!$activity->find(TRUE)) {
    throw new API_Exception('Cannot find activity');
  }
  $caseActivity = new CRM_Case_DAO_CaseActivity();
  $caseActivity->case_id = $params['case_id'];
  $caseActivity->activity_id = $activity->id;
  if ($caseActivity->find(TRUE)) {
    throw new API_Exception('Activity is already filed on this case');
  }
  $caseActivity->save();
  CRM_Utils_Hook::post('create', 'CaseActivity', $caseActivity->id, $caseActivity);
  return civicrm_api3_create_success([$caseActivity->id => $caseActivity->toArray()]);
}
