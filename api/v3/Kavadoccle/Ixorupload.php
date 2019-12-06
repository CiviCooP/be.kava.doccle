<?php
use CRM_Doccle_ExtensionUtil as E;

function _civicrm_api3_kavadoccle_Ixorupload_spec(&$spec) {
  $spec['limit']['api.required'] = 0;
}

function civicrm_api3_kavadoccle_Ixorupload($params) {
  try {
    // select all doccle entries that have to be uploaded
    $doccleTable = CRM_Doccle_Config::singleton()->getDoccleCustomGroup('table_name');
    $customFieldXml = CRM_Doccle_Config::singleton()->getDoccleCustomField('xml', 'column_name');
    $customFieldConnectionTime = CRM_Doccle_Config::singleton()->getDoccleCustomField('Verbonden_op', 'column_name');

    // make sure we have the KAVA Doccle Token
    if (!defined('KAVA_IXOR_TOKEN')) {
      throw new Exception("Cannot find KAVA_IXOR_TOKEN. Define it in civicrm.settings.php");
    }

    $sql = "
      SELECT
        id,
        $customFieldXml xml
      FROM
        $doccleTable
      WHERE
        $customFieldConnectionTime IS NULL
      AND
        $customFieldXml IS NOT NULL
    ";

    // see if we have to limit the number of records (= param of this API)
    if (array_key_exists('limit', $params)) {
      $sql .= ' limit 0,%1';
      $sqlParams = [
        1 => [$params['limit'], 'Integer'],
      ];
    }
    else {
      $sqlParams = [];
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $ixorUploader = new CRM_Doccle_IxorUploader();
    $successful = 0;
    $failed = 0;
    while ($dao->fetch()) {
      $retval = $ixorUploader->uploadXML(htmlspecialchars_decode($dao->xml));
      if ($retval === TRUE) {
        $successful++;

        // success, update the timestamp
        $sqlUpdate = "UPDATE $doccleTable SET $customFieldConnectionTime = '" . date('Y-m-d') . "' WHERE id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
        $failed++;
      }
    }

    $returnMessage = "Successful uploaded: $successful, Failed uploads: $failed";
    return civicrm_api3_create_success([$returnMessage], $params, 'kavadoccle', 'ixorupload');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), 999);
  }
}
