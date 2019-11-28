<?php

require_once 'doccle.civix.php';
use CRM_Doccle_ExtensionUtil as E;

function doccle_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == 'Relationship' && ($op == 'create' || $op == 'edit')) {
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT,
      'doccle_createDoccleRecord', [$objectRef->id]);
  }
}

function doccle_createDoccleRecord($relationshipID) {
  try {
    // get the relationship
    $params = [
      'id' => $relationshipID,
      'sequential' => 1,
    ];
    $result = civicrm_api3('Relationship', 'get', $params);

    if ($result['count'] > 0) {
      // check the relationship type
      if ($result['values'][0]['relationship_type_id'] == CRM_Doccle_Config::singleton()->getFinancialBoxRelationshipID()) {
        // create the financial box fields (if needed)
        $doccle = new CRM_Doccle_Helper();
        $doccle->createFinancialBox($result['values'][0]['contact_id_a'], $result['values'][0]['contact_id_b']);
      }
      elseif ($result['values'][0]['relationship_type_id'] == CRM_Doccle_Config::singleton()->getPatientBoxRelationshipID()) {
        // create the patient box fields (if needed)
        $doccle = new CRM_Doccle_Helper();
        $doccle->createPatientBox($result['values'][0]['contact_id_a'], $result['values'][0]['contact_id_b']);
      }
    }
  }
  catch (Exception $e) {
    CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function doccle_civicrm_config(&$config) {
  _doccle_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function doccle_civicrm_xmlMenu(&$files) {
  _doccle_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function doccle_civicrm_install() {
  _doccle_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function doccle_civicrm_postInstall() {
  _doccle_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function doccle_civicrm_uninstall() {
  _doccle_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function doccle_civicrm_enable() {
  _doccle_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function doccle_civicrm_disable() {
  _doccle_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function doccle_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _doccle_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function doccle_civicrm_managed(&$entities) {
  _doccle_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function doccle_civicrm_caseTypes(&$caseTypes) {
  _doccle_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function doccle_civicrm_angularModules(&$angularModules) {
  _doccle_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function doccle_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _doccle_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function doccle_civicrm_entityTypes(&$entityTypes) {
  _doccle_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function doccle_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function doccle_civicrm_navigationMenu(&$menu) {
  _doccle_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _doccle_civix_navigationMenu($menu);
} // */
