<?php

class CRM_Casetools_Install_Entity_OptionValue extends CRM_Casetools_Install_Entity_Base {

  /**
   * Entity name
   *
   * @var string
   */
  protected $entityName = 'OptionValue';

  /**
   * Option Group name
   *
   * @var string
   */
  const CHANGE_CASE_MANAGERS = 'change_case_managers';

  /**
   * Option Group name
   *
   * @var string
   */
  const CHANGE_CASE_TAGS = 'change_case_tags';

  /**
   * Params to check entity existence
   *
   * @var array
   */
  protected $entitySearchParams = ['name', 'option_group_id'];

  /**
   * Gets list of entities params
   *
   * @return array
   */
  protected function getEntityParam() {
    return [
      [
        'option_group_id' => 'activity_type',
        'name' => self::CHANGE_CASE_MANAGERS,
        'label' => ts("Change case managers"),
        'is_default' => 0,
      ],
      [
        'option_group_id' => 'activity_type',
        'name' => self::CHANGE_CASE_TAGS,
        'label' => ts("Change case tags"),
        'is_default' => 0,
      ],
    ];
  }

}
