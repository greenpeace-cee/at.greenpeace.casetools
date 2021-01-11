<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Casetools_Upgrader extends CRM_Casetools_Upgrader_Base {

  /**
   * Runs while extension is installing
   */
  public function install() {

  }

  /**
   * Runs after extension is installed
   */
  public function onPostInstall() {
    CRM_Casetools_Install_Install::createEntities();
  }

  /**
   * Runs while extension is uninstalling
   */
  public function uninstall() {
    CRM_Casetools_Install_Install::deleteEntities();
  }

  /**
   * Runs while extension is enabling
   */
  public function enable() {
    CRM_Casetools_Install_Install::enableEntities();
  }

  /**
   * Runs while extension is disabling
   */
  public function disable() {
    CRM_Casetools_Install_Install::disableEntities();
  }

  public function upgrade_0001() {
    $this->ctx->log->info('Applying update 0001. Install new activity type.');
    (new CRM_Casetools_Install_Entity_OptionValue())->createAll();

    return TRUE;
  }

}
