<?php

class CRM_Casetools_Hooks_CaseEndDate {

  /**
   * Handles hook_civicrm_pre().
   *
   * @param $operation
   * @param $objectName
   * @param $id
   * @param $params
   * @return void
   */
  public static function handlePreHook($operation, $objectName, $id, &$params) {
    if ($objectName !== 'Case') {
      return;
    }

    if ($operation !== 'edit') {
      return;
    }

    if (empty($params['status_id'])) {
      return;
    }

    $newStatusId = (string) $params['status_id'];
    if (empty($newStatusId)) {
      return;
    }

    if (isset($params['end_date'])) {
      return;
    }

    try {
      $currentStatusId = (string) civicrm_api3('Case', 'getvalue', ['return' => "status_id", 'id' => $id]);
    } catch (CiviCRM_API3_Exception $e) {
      $currentStatusId = null;
    }

    $openedStatusesValues = CRM_Casetools_Utils_Case::getOpenedStatusesValues();
    $closedStatusesValues = CRM_Casetools_Utils_Case::getClosedStatusesValues();

    if (in_array($currentStatusId, $closedStatusesValues) && in_array($newStatusId, $openedStatusesValues)) {
      $params['end_date'] = '';
    } else if (in_array($currentStatusId, $openedStatusesValues) && in_array($newStatusId, $closedStatusesValues)) {
      $params['end_date'] = (new DateTime())->format('Ymd');
    }
  }

}
