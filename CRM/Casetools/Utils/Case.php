<?php

class CRM_Casetools_Utils_Case {

  /**
   * Cache for case statuses
   *
   * @var array|null
   */
  protected static $caseStatuses = null;

  /**
   * Gets case statuses from database
   *
   * @return array
   */
  public static function getStatusesFromDb() {
    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'sequential' => 1,
        'option_group_id' => "case_status",
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return $result['values'];
  }

  /**
   * Gets case statuses from cache(if exist)
   *
   * @return array
   */
  public static function getStatuses() {
    if (!isset(self::$caseStatuses)) {
      self::$caseStatuses = self::getStatusesFromDb();
    }

    return self::$caseStatuses;
  }

  /**
   * Gets case statuses where grouping is Opened
   *
   * @return array
   */
  public static function getOpenedStatusesValues() {
    $values = [];
    foreach (self::getStatuses() as $status) {
      if ($status['grouping'] == 'Opened') {
        $values[] = $status['value'];
      }
    }

    return $values;
  }

  /**
   * Gets case statuses where grouping is Closed
   *
   * @return array
   */
  public static function getClosedStatusesValues() {
    $values = [];
    foreach (self::getStatuses() as $status) {
      if ($status['grouping'] == 'Closed') {
        $values[] = $status['value'];
      }
    }

    return $values;
  }

}
