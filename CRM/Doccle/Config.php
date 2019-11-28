<?php

/**
 * Class CRM_Doccle_Config
 *
 * "Dump" singleton class for Doccle settings and configuration.
 * The more "intelligent" code is in CRM_Doccle_Helper.
 */
class CRM_Doccle_Config {
  // property for singleton pattern (caching the config)
  static private $_singleton = NULL;

  private $financialBoxRelationshipID = 0;
  private $patientBoxRelationshipID = 0;
  private $doccleCustomGroup = [];
  private $doccleCustomFields = [];

  public function __construct() {
  }

  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Doccle_Config();
    }
    return self::$_singleton;
  }

  public function getFinancialBoxRelationshipID() {
    if ($this->financialBoxRelationshipID == 0) {
      // we haven't retrieved the ID yet, get it now
      $this->financialBoxRelationshipID = civicrm_api3('RelationshipType', 'getvalue', [
        'name_a_b' => 'beheert financiële box van',
        'return' => 'id',
      ]);
    }

    return $this->financialBoxRelationshipID;
  }

  public function getPatientBoxRelationshipID() {
    if ($this->patientBoxRelationshipID == 0) {
      // we haven't retrieved the ID yet, do it now
      $this->patientBoxRelationshipID = civicrm_api3('RelationshipType', 'getvalue', [
        'name_a_b' => 'beheert patientbox van',
        'return' => 'id',
      ]);
    }

    return $this->patientBoxRelationshipID;
  }

  public function getDoccleCustomGroup($item) {
    if (count($this->doccleCustomGroup) == 0) {
      // we haven't retrieved the details yet, do it now
      $this->doccleCustomGroup = civicrm_api3('CustomGroup', 'getsingle', [
        'extends' => 'Individual',
        'name' => 'Doccle_Connect',
      ]);
    }

    // make sure the requested item is in the custom group
    if (!array_key_exists($item, $this->doccleCustomGroup)) {
      throw new Exception("$item not found in doccleCustomGroup");
    }

    return $this->doccleCustomGroup[$item];
  }

  public function getDoccleCustomField($customFieldName, $item) {
    if (count($this->doccleCustomFields) == 0) {
      // we haven't retrieved the details yet, do it now
      $params = [
        'custom_group_id' => $this->getDoccleCustomGroup('id'),
        'sequential' => 1,
      ];
      $result = civicrm_api3('CustomField', 'get', $params);
      foreach ($result['values'] as $customField) {
        // store the custom field details in the array, the key is the custom field name
        $this->doccleCustomFields[$customField['name']] = $customField;
      }
    }

    // make sure the requested field is in the custom field array
    if (!array_key_exists($customFieldName, $this->doccleCustomFields)) {
      throw new Exception("$customFieldName not found in doccleCustomFields");
    }

    // make sure the requested item is in the custom field
    if (!array_key_exists($item, $this->doccleCustomFields[$customFieldName])) {
      throw new Exception("$item not found in doccleCustomFields['$customFieldName']");
    }

    return $this->doccleCustomFields[$customFieldName][$item];
  }


}
