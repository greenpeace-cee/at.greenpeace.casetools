<?php

/**
 * Class CRM_Casetools_APIWrappers_HandleCaseManagers
 *
 * Changes case managers if exists 'new_case_manager_ids' param
 * Creates a 'case managers change activity' when the Case.create API is used with
 * the "track_managers_change" parameter set to TRUE.
 *
 * Activity params may be changed via the "managers_change_activity_params" parameter,
 * which is passed to Activity.create.
 */
class CRM_Casetools_APIWrappers_HandleCaseManagers implements API_Wrapper {

  /**
   * @param array $apiRequest
   *
   * @return array
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * Create a case managers change activity if requested
   * @param $apiRequest
   * @param $result
   *
   * @return array
   */
  public function toApiOutput($apiRequest, $result) {
    if (!$this->isShouldRun($apiRequest) || empty($result['id'])) {
      return $result;
    }

    $values = reset($result['values']);
    $caseManager = new CRM_Casetools_Utils_CaseManager($apiRequest['params']['id']);
    $managersChangeReport = $caseManager->setManagerIds($apiRequest['params']['new_case_manager_ids']);

    if (!empty($apiRequest['params']['track_managers_change']))  {
      if (!empty($managersChangeReport['added_manager_ids'] || !empty($managersChangeReport['removed_manager_ids']))) {
        $activityParams = [];
        if (!empty($apiRequest['params']['managers_change_activity_params']) && is_array($apiRequest['params']['managers_change_activity_params'])) {
          $activityParams = $apiRequest['params']['managers_change_activity_params'];
        }

        $this->createManagerChangeActivity(
          $values['id'],
          $managersChangeReport['managers_before_update'],
          $managersChangeReport['managers_after_update'],
          $activityParams
        );
      }
    }

    return $result;
  }

  /**
   * Is should handle case managers
   *
   * @param $apiRequest
   *
   * @return bool
   */
  private function isShouldRun($apiRequest) {
    return !empty($apiRequest['params']['id'])
      && isset($apiRequest['params']['new_case_manager_ids'])
      && is_array($apiRequest['params']['new_case_manager_ids']);
  }

  /**
   * Record a managers change activity
   *
   * @param int $caseId
   * @param $managersBeforeUpdate
   * @param $managersAfterUpdate
   * @param array $activityParams
   */
  private function createManagerChangeActivity($caseId, $managersBeforeUpdate, $managersAfterUpdate, $activityParams) {
    $managersNamesBeforeUpdate = [];
    foreach ($managersBeforeUpdate as $contactId) {
      $managersNamesBeforeUpdate[] = CRM_Contact_BAO_Contact::displayName($contactId);
    }
    $managersNamesAfterUpdate = [];
    foreach ($managersAfterUpdate as $contactId) {
      $managersNamesAfterUpdate[] = CRM_Contact_BAO_Contact::displayName($contactId);
    }

    $subject = ts('Case id %3. Managers are changed.', [
      1 => $caseId,
    ]);

    $details = ts('Managers are changed from: "%1" to "%2".', [
      1 => implode(', ', $managersNamesBeforeUpdate),
      2 => implode(', ', $managersNamesAfterUpdate),
      3 => $caseId,
    ]);

    $params = [
      'case_id' => $caseId,
      'activity_type_id' => CRM_Casetools_Install_Entity_OptionValue::CHANGE_CASE_MANAGERS,
      'subject' => $subject,
      'details' => $details,
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
