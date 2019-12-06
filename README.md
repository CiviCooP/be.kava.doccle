# be.kava.doccle

This extension:

* automatically creates a "Doccle Connect"-entry for the contact whenever a relationship is created of type:
  * patientbox
  * financiële box
* contains the API: kavadoccle.ixorupload

The API should be scheduled as a job that runs e.g. every hour.

Make sure you define KAVA_IXOR_TOKEN in civicrm.settings.php. It must contain the KAVA test token or production token.


