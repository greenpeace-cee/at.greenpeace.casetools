<?php

/**
 * Class CRM_Casetools_APIWrappers_CaseStatus
 *
 * Creates a case status change activity when the Case.create API is used with
 * the "track_status_change" parameter set to TRUE.
 *
 * Activity details may be changed via the "status_change_activity" parameter,
 * which is passed to Activity.create.
 */
class CRM_Casetools_APIWrappers_CaseStatus implements API_Wrapper {

  /**
   * Store the original case status before the change
   *
   * @param array $apiRequest
   *
   * @return array
   */
  public function fromApiInput($apiRequest) {
    if (!$this->shouldRun($apiRequest)) {
      return $apiRequest;
    }
    try {
      $apiRequest['params']['original_status_id'] = civicrm_api3('Case', 'getvalue', [
        'return' => 'status_id',
        'id'     => $apiRequest['params']['id'],
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      Civi::log()->warning("Could not determine case status for case {$apiRequest['params']['id']}: {$e->getMessage()}");
      // don't act on this API call, too much weirdness
    }
    return $apiRequest;
  }

  /**
   * Create a case status change activity if requested
   */
  public function toApiOutput($apiRequest, $result) {
    if (!$this->shouldRun($apiRequest) || empty($result['id'])
      || empty($result['values']) || empty($apiRequest['params']['original_status_id'])) {
      return $result;
    }
    $values = reset($result['values']);
    if (!empty($values['status_id']) && $values['status_id'] != $apiRequest['params']['original_status_id']) {
      $activityParams = [];
      if (!empty($apiRequest['params']['status_change_activity']) && is_array($apiRequest['params']['status_change_activity'])) {
        $activityParams = $apiRequest['params']['status_change_activity'];
      }
      $this->createStatusChangeActivity(
        $result['id'],
        $apiRequest['params']['original_status_id'],
        $values['status_id'],
        $activityParams
      );
    }
    return $result;
  }

  /**
   * Should we track a status change?
   *
   * @param $apiRequest
   *
   * @return bool
   */
  private function shouldRun($apiRequest) {
    return !empty($apiRequest['params']['id'])
      && !empty($apiRequest['params']['status_id'])
      && !empty($apiRequest['params']['track_status_change'])
      && $apiRequest['params']['track_status_change'];
  }

  /**
   * Record a status change activity
   *
   * @param int $caseId
   * @param int $oldStatus
   * @param int $newStatus
   * @param array $activityParams
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createStatusChangeActivity($caseId, $oldStatus, $newStatus, array $activityParams) {
    $params = [
      'case_id' => $caseId,
      'activity_type_id' => 'Change Case Status',
      'subject' => ts('Case status changed from %1 to %2', array(
          1 => CRM_Core_PseudoConstant::getLabel('CRM_Case_BAO_Case', 'status_id', $oldStatus),
          2 => CRM_Core_PseudoConstant::getLabel('CRM_Case_BAO_Case', 'status_id', $newStatus),
        )
      )
    ];

    try {
      $params['target_id'] = civicrm_api3('Case', 'get', [
        'sequential' => TRUE,
        'return'     => 'contact_id',
        'id'         => $caseId,
      ])['values'][0]['contact_id'];
    } catch (CiviCRM_API3_Exception $e) {
      Civi::log()->warning("Could not determine case client for case {$caseId}: {$e->getMessage()}");
    }
    $params = array_merge($params, $activityParams);
    civicrm_api3('Activity', 'create', $params);
  }

}
