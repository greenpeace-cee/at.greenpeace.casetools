<?php

class CRM_Casetools_Utils_Settings {
  
  /**
   * Cache for relationship type info which use for case managers
   *
   * @var array
   */
  private static $caseManagerRelationshipTypeInfo = [];

  /**
   * Gets relationship type id which use for case managers
   * @param $caseType
   * @return array|null
   */
  public static function getCaseManagerRelationshipTypeInfo($caseType) {
    if (empty($caseType)) {
      return NULL;
    }

    if (!key_exists($caseType, self::$caseManagerRelationshipTypeInfo)) {
      $managerRoleData = (new CRM_Case_XMLProcessor_Process())->getCaseManagerRoleId($caseType);
      if (empty($managerRoleData)) {
        self::$caseManagerRelationshipTypeInfo[$caseType] = NULL;
        return self::$caseManagerRelationshipTypeInfo[$caseType];
      }

      $managerRelationshipDirection = substr($managerRoleData, -4);
      $info = [
        'case_type' => $caseType,
        'manager_relationship_type_id' => substr($managerRoleData, 0, -4),
        'manager_relationship_direction' => $managerRelationshipDirection,
        'manager_relationship_type' => [],
        'manager_column_name' => ($managerRelationshipDirection == '_a_b') ? 'contact_id_b' : 'contact_id_a',
        'client_column_name' => ($managerRelationshipDirection == '_a_b') ? 'contact_id_a' : 'contact_id_b',
      ];

      try {
        $relationshipType = civicrm_api3('RelationshipType', 'getsingle', [
          'id' => $info['manager_relationship_type_id'],
          "return" => [
            "id",
            "name_a_b",
            "label_a_b",
            "name_b_a",
            "label_b_a",
            "description",
            "contact_type_a",
            "contact_type_b",
            "is_reserved",
            "is_active",
          ],
        ]);
      } catch (CiviCRM_API3_Exception $e) {
        self::$caseManagerRelationshipTypeInfo[$caseType] = NULL;
        return self::$caseManagerRelationshipTypeInfo[$caseType];
      }

      $info['manager_relationship_type'] = $relationshipType;
      self::$caseManagerRelationshipTypeInfo[$caseType] = $info;
    }

    return self::$caseManagerRelationshipTypeInfo[$caseType];
  }

}
