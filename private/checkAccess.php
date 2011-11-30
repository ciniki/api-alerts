<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_alerts_checkAccess($ciniki, $business_id, $method, $alert_id, $user_id) {

	//
	// Sysadmin is allowed access to all functions
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Users who are an owner or employee of a business can see the business alerts
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND (groups&0x03) > 0 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	//
	// Double check business_id and user_id match, for single row returned.
	//
	if( isset($rc['user']) && isset($rc['user']['business_id']) 
		&& $rc['user']['business_id'] == $business_id 
		&& $rc['user']['user_id'] = $ciniki['session']['user']['id'] ) {
		// Access Granted!
		return array('stat'=>'ok');
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'487', 'msg'=>'Access denied.'));
}
?>
