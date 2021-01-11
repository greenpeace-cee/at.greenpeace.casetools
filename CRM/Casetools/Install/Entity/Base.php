<?php

/**
 * Base entity class which can  create/disable/enable/delete/update entities
 */
abstract class CRM_Casetools_Install_Entity_Base {

  /**
   * Entity name
   *
   * @var string
   */
  protected $entityName = '';

  /**
   * List of entities params which will be installed
   *
   * @var array
   */
  protected $entitiesParams = [];

  /**
   * Params to check entity existence
   *
   * @var array
   */
  protected $entitySearchParams = [];

  public function __construct() {
    $this->entitiesParams = $this->getEntityParam();
  }

  /**
   * Gets list of entities params
   *
   * @return array
   */
  protected function getEntityParam() {
    return [];
  }

  /**
   * Creates all entities
   */
  public function createAll() {
    foreach ($this->entitiesParams as $entityParam) {
      if (!$this->isExist($entityParam)) {
        $this->create($entityParam);
      }
    }
  }

  /**
   * Is Entity exists?
   *
   * @param $entityParam
   *
   * @return bool|int
   */
  private function isExist($entityParam) {
    return !empty(($this->getId($entityParam)));
  }

  /**
   * Gets entity id
   *
   * @param $entityParam
   *
   * @return bool|int
   */
  protected function getId($entityParam) {
    $searchParam = [];
    foreach ($this->entitySearchParams as $nameParam) {
      $searchParam[$nameParam] = $entityParam[$nameParam];
    }

    $searchParam['options'] = ['limit' => 1];

    try {
      $entities = civicrm_api3($this->entityName, 'get', $searchParam);
    } catch (\CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    if (empty($entities['values'])) {
      return FALSE;
    }

    foreach ($entities['values'] as $entity) {
      return $entity['id'];
    }

    return FALSE;
  }

  /**
   * Creates entity
   *
   * @param $entityParam
   */
  protected function create($entityParam) {
    try {
      civicrm_api3($this->entityName, 'create', $entityParam);
    } catch (\CiviCRM_API3_Exception $e) {}
  }

  /**
   * Disables all entities
   */
  public function disableAll() {
    foreach ($this->entitiesParams as $entityParam) {
      $entityId = $this->getId($entityParam);
      if (!empty($entityId)) {
        $this->disable($entityId);
      }
    }
  }

  /**
   * Disables entity by id
   *
   * @param $entityId
   */
  protected function disable($entityId) {
    try {
      civicrm_api3($this->entityName, 'create', [
        'id' => $entityId,
        'is_active' => 0,
      ] );
    } catch (\CiviCRM_API3_Exception $e) {}
  }

  /**
   * Enables all entities
   */
  public function enableAll() {
    foreach ($this->entitiesParams as $entityParam) {
      $entityId = $this->getId($entityParam);
      if (!empty($entityId)) {
        $this->enable($entityId);
      }
    }
  }

  /**
   * Enables entity by id
   *
   * @param $entityId
   */
  protected function enable($entityId) {
    try {
      civicrm_api3($this->entityName, 'create', [
        'id' => $entityId,
        'is_active' => 1,
      ] );
    } catch (\CiviCRM_API3_Exception $e) {}
  }

  /**
   * Deletes all entities
   */
  public function deleteAll() {
    foreach ($this->entitiesParams as $entityParam) {
      $entityId = $this->getId($entityParam);
      if (!empty($entityId)) {
        $this->delete($entityId);
      }
    }
  }

  /**
   * Enables entity by id
   *
   * @param $entityId
   */
  protected function delete($entityId) {
    try {
      civicrm_api3($this->entityName, 'delete', [
        'id' => $entityId
      ]);
    } catch (\CiviCRM_API3_Exception $e) {}
  }

}
