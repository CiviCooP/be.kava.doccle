<?php
use CRM_Doccle_ExtensionUtil as E;

function _civicrm_api3_kavadoccle_Receiverconncheck_spec(&$spec) {
    $spec['limit']['api.required'] = 0;
}

function civicrm_api3_kavadoccle_Receiverconncheck($params) {
    try {
        // select all doccle entries that have to be uploaded
        $doccleTable = CRM_Doccle_Config::singleton()->getDoccleCustomGroup('table_name');
        $customFieldReceiverID = CRM_Doccle_Config::singleton()->getDoccleCustomField('Receiver_id', 'column_name');
        $customFieldConnectionTime = CRM_Doccle_Config::singleton()->getDoccleCustomField('Verbonden_op', 'column_name');
        $customFieldLink = CRM_Doccle_Config::singleton()->getDoccleCustomField('Doccle_Connect_link', 'column_name');

        // make sure we have the KAVA Doccle Token
        if (!defined('KAVA_IXOR_TOKEN')) {
            throw new Exception("Cannot find KAVA_IXOR_TOKEN. Define it in civicrm.settings.php");
        }

        $sql = "
      SELECT
        id,
        $customFieldReceiverID receiver
      FROM
        $doccleTable
      WHERE
        ($customFieldConnectionTime IS NULL or $customFieldConnectionTime = '')
        AND 
        $customFieldReceiverID IS NOT NULL";

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
            $retval = $ixorUploader->isReceiverConnected($dao->receiver);
            if ($retval === TRUE) {
                $successful++;

                // success, update the timestamp
                $sqlUpdate = "UPDATE $doccleTable SET $customFieldConnectionTime = '" . date('Y-m-d') . "', $customFieldLink = NULL WHERE id = " . $dao->id;
                CRM_Core_DAO::executeQuery($sqlUpdate);
            }
            else {
                $failed++;
            }
        }

        $returnMessage = "connected: $successful, not connected: $failed";
        return civicrm_api3_create_success([$returnMessage], $params, 'kavadoccle', 'receiverconncheck');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), 999);
    }
}
