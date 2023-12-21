<?php

require_once 'casetools.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function casetools_civicrm_config(&$config) {
  _casetools_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function casetools_civicrm_install() {
  _casetools_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function casetools_civicrm_enable() {
  _casetools_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_apiWrappers().
 */
function casetools_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if ($apiRequest['entity'] == 'Case' && $apiRequest['action'] == 'create') {
    $wrappers[] = new CRM_Casetools_APIWrappers_CaseStatus();
    $wrappers[] = new CRM_Casetools_APIWrappers_HandleCaseManagers();
    $wrappers[] = new CRM_Casetools_APIWrappers_HandleCaseTags();
  }
}

/**
 * Implements hook_civicrm_pre().
 */
function casetools_civicrm_pre($operation, $objectName, $id, &$params) {
  CRM_Casetools_Hooks_CaseEndDate::handlePreHook($operation, $objectName, $id, $params);
}
