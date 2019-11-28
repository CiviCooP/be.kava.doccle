<?php

class CRM_Doccle_Helper {

  public function createFinancialBox($sourceContactID, $targetContactID) {
    // get the VAT number of the target contact
    $sql = "
      select 
        btw_nummer_24 
      from 
        civicrm_value_contact_organisation
      where 
        entity_id = $targetContactID
    ";
    $vat = CRM_Core_DAO::singleValueQuery($sql);
    if ($vat) {
      $doccleName = "Verbinden met Doccle financiële box voor $vat";
      if (!$this->hasDoccleFields($sourceContactID, $doccleName)) {
        $this->createDoccleFields($sourceContactID, $doccleName, $vat);
      }
    }
    else {
      throw new Exception("Kan BTW-nummer niet vinden van contact $targetContactID");
    }
  }

  public function createPatientBox($sourceContactID, $targetContactID) {
    // get the APB number of the target contact
    $sql = "
      select 
        apb_nummer_43 apb_nummer
      from
        civicrm_value_contact_apotheekuitbating
      where 
        entity_id = $targetContactID
    ";
    $apb = CRM_Core_DAO::singleValueQuery($sql);
    if ($apb) {
      $doccleName = "Verbinden met Doccle patientbox voor $apb";
      if (!$this->hasDoccleFields($sourceContactID, $doccleName)) {
        $this->createDoccleFields($sourceContactID, $doccleName, $apb);
      }
    }
    else {
      throw new Exception("Kan APB-nummer niet vinden van contact $targetContactID");
    }
  }

  public function hasDoccleFields($contactID, $doccleName) {
    // see if the contact has a record in the Doccle Connect table
    $table = CRM_Doccle_Config::singleton()->getDoccleCustomGroup('table_name');
    $customFieldName = CRM_Doccle_Config::singleton()->getDoccleCustomField('Doccle_Connect_label', 'column_name');

    $sql = "
      select
        count(*)
      from
        $table
      where
        entity_id = %1
      and
        $customFieldName = %2
    ";
    $sqlParams = [
      1 => [$contactID, 'Integer'],
      2 => [$doccleName, 'String'],
    ];

    $num = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($num == 0) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  public function createDoccleFields($contactID, $doccleName, $APBorVATnumber) {
    // get the contact hash
    $sql = "select hash from civicrm_contact where id  = $contactID";
    $hash = CRM_Core_DAO::singleValueQuery($sql);
    if (!$hash) {
      throw new Exception("Kan hash van contact $contactID niet ophalen.");
    }

    // strip BE and spaces from VAT
    $strippedNumber = $this->stripNumber($APBorVATnumber);

    // create url tokens
    $token1 = base64_encode($strippedNumber);
    $token2 = base64_encode($hash);
    $url = "https://secure.doccle.be/doccle-euui/direct/connect?senderName=kava&t1=$token1&$token2";

    // create the XML
    $randomID = $contactID . mt_rand(100, 999);
    $xml = $this->generateXML($randomID, $APBorVATnumber, $strippedNumber, $hash);

    $entityTable = CRM_Doccle_Config::singleton()->getDoccleCustomGroup('table_name');
    $customFieldLabel = CRM_Doccle_Config::singleton()->getDoccleCustomField('Doccle_Connect_label', 'id');
    $customFieldLink = CRM_Doccle_Config::singleton()->getDoccleCustomField('Doccle_Connect_link', 'id');
    $customFieldReceiverID = CRM_Doccle_Config::singleton()->getDoccleCustomField('Receiver_id', 'id');
    $customFieldXml = CRM_Doccle_Config::singleton()->getDoccleCustomField('xml', 'id');

    $params = [
      'entity_id' => $contactID,
      'entity_table' => $entityTable,
      'custom_' . $customFieldLabel => $doccleName,
      'custom_' . $customFieldLink => $url,
      'custom_' . $customFieldReceiverID => $strippedNumber,
      'custom_' . $customFieldXml => $xml,
    ];
    civicrm_api3('CustomValue', 'create', $params);
  }

  private function stripNumber($n) {
    // remove BE, spaces, and dots
    return str_replace('BE', '', str_replace('.', '', str_replace(' ', '', $n)));
  }

  private function generateXML($batchID, $receiverID, $boxID, $uuID) {
    $startDate = new DateTime();
    $endDate = $startDate->add(new DateInterval('P6M'));

    $xml =
'<?xml version="1.0" encoding="utf-8"?>
 <batch>
    <batch-info>
        <id>receiverbatch_' . $batchID . '</id>
    </batch-info>
    <receivers>
        <receiver>
            <id>' . $receiverID . '</id>
            <token-set start-date=\'' . $startDate->format('Y-m-d\TH:i:s') . '\' end-date=\'' . $endDate->format('Y-m-d\TH:i:s') . '\' >
                <token>
                    <value>'. $boxID . '</value>
                </token>
                <token>
                    <value>' . $uuID . '</value>
                </token>
            </token-set>
        </receiver>
    </receivers>
</batch>';

    return $xml;
  }
}
