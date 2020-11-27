<?php

class CRM_Doccle_Helper
{

    public function createFinancialBox($sourceContactID, $targetContactID)
    {
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
        } else {
            throw new Exception("Kan BTW-nummer niet vinden van contact $targetContactID");
        }
    }

    public function createPatientBox($sourceContactID, $targetContactID)
    {
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
        } else {
            throw new Exception("Kan APB-nummer niet vinden van contact $targetContactID");
        }
    }

    public function hasDoccleFields($contactID, $doccleName)
    {
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
        } else {
            return TRUE;
        }
    }

    public function createDoccleFields($contactID, $doccleName, $receiver)
    {
        // get the contact hash
        $hash = $this->createSecretCode($contactID);

        // create url tokens
        $token1 = base64_encode($receiver);
        $token2 = base64_encode($hash);
        $url = "https://secure.doccle.be/doccle-euui/direct/connect?senderName=kava&t1=$token1&t2=$token2";

        // strip BE and spaces from VAT
        $box = $this->stripNumber($receiver);

        // create the XML
        $xml = $this->generateXML($contactID . uniqid(),
            $receiver,
            $box,
            $hash,
            $url,
            $token1,
            $token2
        );

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
            'custom_' . $customFieldReceiverID => $box,
            'custom_' . $customFieldXml => $xml,
        ];
        civicrm_api3('CustomValue', 'create', $params);
    }

    private function createSecretCode($contact)
    {
        $sql = "select hash from civicrm_contact where id  = $contact";
        $hash = CRM_Core_DAO::singleValueQuery($sql);

        if (!$hash) {
            throw new Exception("Kan hash van contact '$contact' niet ophalen.");
        }

        // add randomness to hash so that links can be generated for the same contact
        return $hash . uniqid();
    }

    private function stripNumber($n)
    {
        // remove BE, spaces, and dots
        return str_replace('BE', '', str_replace('.', '', str_replace(' ', '', $n)));
    }

    private function generateXML($batchID, $receiver, $box, $uuid, $url, $token1, $token2)
    {
        $startDate = new DateTime();
        $endDate = new DateTime();
        $endDate->add(new DateInterval('P6M'));

        $escapedUrl = htmlspecialchars($url);

        $xml =
            '<?xml version="1.0" encoding="utf-8"?>
 <batch xmlns="http://ixor.be/docs/receivers">
    <batch-info>
        <id>receiverbatch_' . $batchID . '</id>
    </batch-info>
    <receivers>
        <receiver>
            <!-- ' . $escapedUrl . ' -->
            <id>' . $box . '</id>
            <token-set start-date=\'' . $startDate->format('Y-m-d\TH:i:s') . '\' end-date=\'' . $endDate->format('Y-m-d\TH:i:s') . '\' >
                <token>
                    <!-- token1: ' . $token1 . ' -->
                    <value>' . $receiver . '</value>
                </token>
                <token>
                    <!-- token2: ' . $token2 . ' --> 
                    <value>' . $uuid . '</value>
                </token>
            </token-set>
        </receiver>
    </receivers>
</batch>';

        return $xml;
    }
}
