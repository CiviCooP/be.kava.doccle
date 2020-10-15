<?php

class CRM_Doccle_IxorUploader {
  public function isReceiverConnected($receiverId) {
      $token = KAVA_IXOR_TOKEN;
      $url = "https://docs.ixor.be/api/doccle/receiver?id=" . $receiverId;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['access-token: ' . $token]);

      $result = curl_exec($ch);
      curl_close($ch);

      $json_data = json_decode($result);
      $linked_to_user = $json_data->linkedToEndUser;

      if ($linked_to_user) {
          return TRUE;
      }
      else {
          return FALSE;
      }
  }

  public function uploadXML($xml) {
    // create the ZIP file with the XML
    $zipFile = $this->zipXML($xml);

    // upload the ZIP file
    $retval = $this->uploadZip($zipFile);

    return $retval;
  }

  private function uploadZip($zipFile) {
    $token = KAVA_IXOR_TOKEN;
    $url = "https://docs.ixor.be/api/upload";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data', 'access-token: ' . $token]);

    // add the file
    $fields = [
      'data' => new \CurlFile($zipFile, 'application/zip', 'receiver.zip'),
      'type' => 'RECEIVERS',
      'reference' => 'kava-receivers-' . date('Y-m-d'),
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);
    curl_close($ch);
    if (!$result) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  private function zipXML($xml) {
    // create the zip file
    $zip = new ZipArchive();
    $zipFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'receiver.zip';
    $retval = $zip->open($zipFileName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
    if ($retval === TRUE) {
      // add the content to the zip
      $zip->addFromString('receivers.xml', $xml);
      $zip->close();
    }
    else {
      throw new Exception('Could not create ZIP file');
    }

    // return the file name
    return $zipFileName;
  }
}
