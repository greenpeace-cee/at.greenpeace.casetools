<?php

/**
 * Gets case manager settings
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_case_tools_get_manager_settings($params) {
  return civicrm_api3_create_success(['case_manager_relationship_settings' => (new CRM_Casetools_Utils_CaseManager($params['case_id']))->getCaseManagerRelationshipTypeInfo()]);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_case_tools_get_manager_settings_spec(&$params) {
  $params['case_id'] = [
    'name' => 'case_id',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Case id',
  ];
}
