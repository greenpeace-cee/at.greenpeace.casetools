<?php

/**
 * Handles all actions of entities
 */
class CRM_Casetools_Install_Install {

  /**
   * Creates all entities
   */
  public static function createEntities() {
    (new CRM_Casetools_Install_Entity_OptionValue())->createAll();
  }

  /**
   * Disables all entities
   */
  public static function disableEntities() {
    (new CRM_Casetools_Install_Entity_OptionValue())->disableAll();
  }

  /**
   * Enables all entities
   */
  public static function enableEntities() {
    (new CRM_Casetools_Install_Entity_OptionValue())->enableAll();
  }

  /**
   * Deletes all entities
   */
  public static function deleteEntities() {}

}
