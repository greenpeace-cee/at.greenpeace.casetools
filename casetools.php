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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function casetools_civicrm_xmlMenu(&$files) {
  _casetools_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function casetools_civicrm_postInstall() {
  _casetools_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function casetools_civicrm_uninstall() {
  _casetools_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function casetools_civicrm_disable() {
  _casetools_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function casetools_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _casetools_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function casetools_civicrm_managed(&$entities) {
  _casetools_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function casetools_civicrm_caseTypes(&$caseTypes) {
  _casetools_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function casetools_civicrm_angularModules(&$angularModules) {
  _casetools_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function casetools_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _casetools_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function casetools_civicrm_entityTypes(&$entityTypes) {
  _casetools_civix_civicrm_entityTypes($entityTypes);
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
 * Implements hook_civicrm_post().
 */
function casetools_civicrm_pre($operation, $objectName, $id, &$params) {
  if ($objectName == 'Case' && $operation == 'edit' && !empty($params['status_id'])) {
    try {
      $currentStatusId = civicrm_api3('Case', 'getvalue', ['return' => "status_id", 'id' => $id]);
    } catch (CiviCRM_API3_Exception $e) {
      $currentStatusId = null;
    }

    $newStatusId = $params['status_id'];
    $openedStatusesValues = CRM_Casetools_Utils_Case::getOpenedStatusesValues();
    $closedStatusesValues = CRM_Casetools_Utils_Case::getClosedStatusesValues();

    if (in_array($currentStatusId, $closedStatusesValues) && in_array($newStatusId, $openedStatusesValues)) {
      $params['end_date'] = '';
    }
  }
}
