<?php

/**
 * Class CRM_Casetools_APIWrappers_HandleCaseTags
 *
 * Changes case tags if exists 'tags_ids' param.
 * All tags not in 'tags_ids' param will be removed.
 *
 * If 'is_only_add_tags' is checked,
 * it will only add tags from 'tags_ids' param and don't remove tags not in 'tags_ids' param.
 *
 * Creates a 'case tag change activity' when the Case.create API is used with
 * the "track_tags_change" parameter set to TRUE.
 *
 * Activity params may be changed via the "tags_change_activity_params" parameter,
 * which is passed to Activity.create.
 */
class CRM_Casetools_APIWrappers_HandleCaseTags implements API_Wrapper {

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

    $caseId = $apiRequest['params']['id'];
    $tagEntityTable = 'civicrm_case';
    $isOnlyAddTags = false;

    $tagsBeforeUpdate = CRM_Casetools_Utils_Tag::getTags($caseId, $tagEntityTable);
    if (CRM_Casetools_Utils_Tag::getTagsIds($caseId, $tagEntityTable) == $apiRequest['params']['tags_ids']) {
      return $result;
    }

    if (isset($apiRequest['params']['is_only_add_tags'])) {
      $isOnlyAddTags = (bool) $apiRequest['params']['is_only_add_tags'];
    }

    CRM_Casetools_Utils_Tag::setTagIdsToEntity($caseId, $apiRequest['params']['tags_ids'], $tagEntityTable, $isOnlyAddTags);
    $tagsAfterUpdate = CRM_Casetools_Utils_Tag::getTags($caseId, $tagEntityTable);

    if (!empty($apiRequest['params']['track_tags_change']))  {
        $activityParams = [];
        if (!empty($apiRequest['params']['tags_change_activity_params']) && is_array($apiRequest['params']['tags_change_activity_params'])) {
          $activityParams = $apiRequest['params']['tags_change_activity_params'];
        }

        $this->createTagsChangeActivity($caseId, $tagsBeforeUpdate, $tagsAfterUpdate, $activityParams);
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
      && isset($apiRequest['params']['tags_ids'])
      && is_array($apiRequest['params']['tags_ids']);
  }

  /**
   * Record a managers change activity
   *
   * @param int $caseId
   * @param $tagsBeforeUpdate
   * @param $tagsAfterUpdate
   * @param array $activityParams
   */
  private function createTagsChangeActivity($caseId, $tagsBeforeUpdate, $tagsAfterUpdate, $activityParams) {
    $tagsNamesBeforeUpdate = [];
    foreach ($tagsBeforeUpdate as $tag) {
      $tagsNamesBeforeUpdate[] = $tag['name'];
    }
    $tagsNamesAfterUpdate = [];
    foreach ($tagsAfterUpdate as $tag) {
      $tagsNamesAfterUpdate[] = $tag['name'];
    }

    $subject = ts('Case id %1. Tags are changed.', [1 => $caseId]);
    $details = ts('Tags are changed from: "%1" to "%2".', [
      1 => implode(', ', $tagsNamesBeforeUpdate),
      2 => implode(', ', $tagsNamesAfterUpdate),
    ]);

    $params = [
      'case_id' => $caseId,
      'activity_type_id' => 'Change Case Tags',
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
