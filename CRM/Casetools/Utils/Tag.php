<?php

class CRM_Casetools_Utils_Tag {

  /**
   * Gets tags ids connected to the entity
   *
   * @param $entityId
   * @param $entityTableName
   * @return array
   */
  public static function getTagsIds($entityId, $entityTableName) {
    if (empty($entityId) || empty($entityTableName)) {
      return [];
    }

    $entityTagIds = CRM_Core_BAO_EntityTag::getTag($entityId, $entityTableName);
    $preparedEntityTagIds = [];

    if (!empty($entityTagIds)) {
      foreach ($entityTagIds as $entityTagId) {
        $preparedEntityTagIds[] = $entityTagId;
      }
    }

    return $preparedEntityTagIds;
  }

  /**
   * Gets available tags for entity
   * @param $entityTableName
   * @return array
   */
  public static function getAvailableTags($entityTableName) {
    if (empty($entityTableName)) {
      return [];
    }

    $preparedAvailableTags = [];
    $availableTags = CRM_Core_BAO_Tag::getTagsUsedFor($entityTableName, FALSE);

    if (!empty($availableTags)) {
      foreach ($availableTags as $tagId => $availableTag) {
        $preparedAvailableTags[$tagId] = [
          'id' => $tagId,
          'name' => $availableTag['name'],
          'description' => $availableTag['description'],
          'color' => $availableTag['color'],
        ];
      }
    }

    return $preparedAvailableTags;
  }

  /**
   * Gets tags data connected to the entity
   *
   * @param $entityId
   * @param $entityTableName
   * @return array
   */
  public static function getTags($entityId, $entityTableName) {
    if (empty($entityId) || empty($entityTableName)) {
      return [];
    }

    $availableTags = self::getAvailableTags($entityTableName);
    $tagsIds = self::getTagsIds($entityId, $entityTableName);
    $tags = [];

    foreach ($tagsIds as $tagId) {
      if (!empty($availableTags[$tagId])) {
        $tags[] = $availableTags[$tagId];
      }
    }

    return $tags;
  }

  /**
   * Is entity's tag exist
   *
   * @param $entityTagId
   * @param $entityTableName
   * @return bool
   */
  public static function isTagExist($entityTagId, $entityTableName) {
    if (empty($entityTagId)) {
      return false;
    }

    try {
      $tag = civicrm_api3('Tag', 'getsingle', [
        'used_for' => $entityTableName,
        'id' => $entityTagId,
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      return false;
    }

    return !empty($tag['id']);
  }

  /**
   * Get tag id by name
   *
   * @param $tagName
   * @return bool
   */
  public static function getTagId($tagName) {
    if (empty($tagName)) {
      return false;
    }

    try {
      $tag = civicrm_api3('Tag', 'getsingle', [
        'name' => $tagName,
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      return false;
    }

    return (int) $tag['id'];
  }

  /**
   * Remove all tags related to the entity
   *
   * @param $entityId
   * @param $entityTableName
   */
  public static function deleteAllTagsRelatedToEntity($entityId, $entityTableName) {
    if (empty($entityId)) {
      return;
    }

    $tagParams = [
      'entity_table' => $entityTableName,
      'entity_id' => $entityId,
    ];

    CRM_Core_BAO_EntityTag::del($tagParams);
  }

  /**
   * Sets list of tags ids to the entity
   *
   * @param $entityId
   * @param $newTagsIds
   * @param $entityTableName
   */
  public static function setTagIdsToEntity($entityId, $newTagsIds, $entityTableName) {
    self::deleteAllTagsRelatedToEntity($entityId, $entityTableName);

    $entityIds = [$entityId];
    foreach ($newTagsIds as $tagId) {
      if (self::isTagExist($tagId, $entityTableName)) {
        CRM_Core_BAO_EntityTag::addEntitiesToTag($entityIds, $tagId, $entityTableName, FALSE);
      }
    }
  }

}
