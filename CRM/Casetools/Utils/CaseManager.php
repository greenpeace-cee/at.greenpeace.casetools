<?php

class CRM_Casetools_Utils_CaseManager {

  /**
   * Case id
   *
   * @var int
   */
  protected $caseId;

  /**
   * Relationship type info for case manager
   *
   * @var array
   */
  protected $relationshipTypeInfo;

  /**
   * Case client id
   *
   * @var int
   */
  protected $clientId;

  /**
   * CRM_Casetools_Utils_CaseManager constructor.
   * @param $caseId
   */
  public function __construct($caseId) {
    if (empty($caseId)) {
      throw new api_Exception('Case id is empty.', 'case_does_not_exist');
    }

    try {
      $case = civicrm_api3('Case', 'getsingle', [
        'id' => $caseId,
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      throw new api_Exception('Case does not exist.', 'case_does_not_exist');
    }
    $this->caseId = $caseId;

    //TODO handle when client is multiple (now it gets first client id)
    $clientContactId = '';
    foreach ($case['client_id'] as $clientId) {
      $clientContactId = $clientId;
    }
    if (empty($clientContactId)) {
      throw new api_Exception('Cannot retrieve case client id.', 'cannot_retrieve_case_client_id');
    }
    $this->clientId = $clientContactId;

    $caseType = CRM_Case_BAO_Case::getCaseType($this->caseId, 'name');
    if (empty($clientContactId)) {
      throw new api_Exception('Cannot retrieve case type.', 'cannot_retrieve_case_type');
    }

    $this->relationshipTypeInfo = CRM_Casetools_Utils_Settings::getCaseManagerRelationshipTypeInfo($caseType);
    if (empty($this->relationshipTypeInfo)) {
      throw new api_Exception(
        'Cannot retrieve relationship type info for case manager. Looks like something wrong with case settings.',
        'cannot_retrieve_manager_relationship_type'
      );
    }
  }

  /**
   * Gets relationship type info for case manager
   *
   * @return array
   */
  public function getCaseManagerRelationshipTypeInfo() {
    return $this->relationshipTypeInfo;
  }

  /**
   * Get case manger contact ids
   *
   * @return array
   */
  public function getCaseManagerContactIds() {
    $managersIds = [];
    $managerRoleQuery = "
      SELECT civicrm_contact.id as casemanager_id, civicrm_contact.sort_name as casemanager
      FROM civicrm_contact
      LEFT JOIN civicrm_relationship ON (civicrm_relationship." . $this->relationshipTypeInfo['manager_column_name'] . " = civicrm_contact.id
      AND civicrm_relationship.relationship_type_id = %1) AND civicrm_relationship.is_active
      LEFT JOIN civicrm_case ON civicrm_case.id = civicrm_relationship.case_id
      WHERE civicrm_case.id = %2 AND is_active = 1";

    $dao = CRM_Core_DAO::executeQuery($managerRoleQuery, [
      1 => [$this->relationshipTypeInfo['manager_relationship_type_id'], 'Integer'],
      2 => [$this->caseId, 'Integer'],
    ]);

    while ($dao->fetch()) {
      $managersIds[] = (int) $dao->casemanager_id;
    }

    return $managersIds;
  }

  /**
   * Sets managers to case
   *
   * @param $newManagerIds
   *
   * @return array
   */
  public function setManagerIds($newManagerIds) {
    $this->validateNewManagerIds($newManagerIds);
    $currentManagerIds = $this->getCaseManagerContactIds();
    $neededToSetManagerIds = array_diff($newManagerIds,$currentManagerIds);
    $neededToUnsetManagerIds = array_diff($currentManagerIds, $newManagerIds);

    foreach ($neededToSetManagerIds as $managerContactId) {
      $this->setManager($managerContactId, $this->clientId);
    }

    foreach ($neededToUnsetManagerIds as $managerContactId) {
      $this->unsetManager($managerContactId, $this->clientId);
    }

    return [
      'managers_before_update' => $currentManagerIds,
      'managers_after_update' => $newManagerIds,
      'added_manager_ids' => $neededToSetManagerIds,
      'removed_manager_ids' => $neededToUnsetManagerIds,
    ];
  }

  /**
   * Validates manager ids
   *
   * @param $newManagerIds
   */
  private function validateNewManagerIds($newManagerIds) {
    if (!is_array($newManagerIds)) {
      throw new api_Exception('Manager contact ids have to be array.', 'wrong_format');
    }

    foreach ($newManagerIds as $managerContactId) {
      try {
        civicrm_api3('Contact', 'getsingle', [
          'id' => $managerContactId,
        ]);
      } catch (CiviCRM_API3_Exception $e) {
        throw new api_Exception('Manager contact(id = ' . $managerContactId . ') does not exist', 'manager_contact_id_does_not_exist');
      }
    }
  }

  /**
   * Set manager to case
   *
   * @param $newManagerContactId
   * @param $clientContactId
   */
  private function setManager($newManagerContactId, $clientContactId) {
    $relationship = self::findRelationship($newManagerContactId, $clientContactId);

    $relationshipParams = [];
    if (empty($relationship)) {
      $relationshipParams = [
        'relationship_type_id' => $this->relationshipTypeInfo['manager_relationship_type_id'],
        'case_id' => $this->caseId,
        $this->relationshipTypeInfo['manager_column_name'] => $newManagerContactId,
        $this->relationshipTypeInfo['client_column_name'] => $clientContactId,
      ];
    } elseif(!empty($relationship['id']) && $relationship['is_active'] == 0) {
      $relationshipParams['id'] = $relationship['id'];
      $relationshipParams['is_active'] = 1;
    }

    if (empty(!$relationshipParams)) {
      civicrm_api3('Relationship', 'create', $relationshipParams);
    }
  }

  /**
   * Unset manager on case
   *
   * @param $managerContactId
   * @param $clientContactId
   */
  private function unsetManager($managerContactId, $clientContactId) {
    $relationship = self::findRelationship($managerContactId, $clientContactId);
    if (!empty($relationship['id']) && $relationship['is_active'] == 1) {
      civicrm_api3('Relationship', 'create', [
        'id' => $relationship['id'],
        'is_active' => 0
      ]);
    }
  }

  /**
   * Finds case manager relationship
   *
   * @param $managerContactId
   * @param $clientContactId

   * @return array
   */
  private function findRelationship($managerContactId, $clientContactId) {
    try {
      $relationship = civicrm_api3('Relationship', 'getsingle', [
        $this->relationshipTypeInfo['manager_column_name'] => $managerContactId,
        $this->relationshipTypeInfo['client_column_name'] => $clientContactId,
        'relationship_type_id' => $this->relationshipTypeInfo['manager_relationship_type_id'],
        'case_id' => $this->caseId,
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return $relationship;
  }

}
