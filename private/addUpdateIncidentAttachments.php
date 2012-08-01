<?php
//
// Description
// -----------
// This function will add a new incident to the alerts database, if one doesn't exist.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_alerts_addUpdateIncidentAttachments($ciniki, $business_id, $alert, $incident, $attachments) {


	if( !is_array($alert) || !isset($alert['ref_uid']) || $alert['ref_uid'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'473', 'msg'=>'Invalid alert reference'));
	}

	$strsql = "INSERT INTO ciniki_alerts (business_id, ref_uid, user_id, status, severity, flags, "
		. "subject, date_added, last_updated) VALUES ( "
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $alert['ref_uid']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "";
	// Check for alert status, default to open
	if( isset($alert['status']) && $alert['status'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $alert['status']) . "' ";
	} else {
		$strsql .= ", '1' ";
	}
	// Check for severity
	if( isset($alert['severity']) && $alert['severity'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $alert['severity']) . "' ";
	} else {
		$strsql .= ", '10' ";
	}
	// Check for flags
	if( isset($alert['flags']) && $alert['flags'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $alert['flags']) . "' ";
	} else {
		$strsql .= ", '0' ";
	}
	// Check for subject
	if( isset($alert['subject']) && $alert['subject'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $alert['subject']) . "' ";
	} else {
		$strsql .= ", 'Unknown' ";
	}
	$strsql .= ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
		. " ON DUPLICATE KEY UPDATE last_updated = UTC_TIMESTAMP() ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.alerts');
	if( $rc['stat'] != 'ok' ) {	
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'474', 'msg'=>'Invalid alert reference', 'err'=>$rc['err']));
	}
	$alert_id = $rc['insert_id'];

	//
	// Add incident
	//
	if( is_array($incident) && count($incident) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'alerts', 'private', 'addIncident');
		$rc = ciniki_alerts__addIncident($ciniki, $alert_id, $incident);
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
	}

	//
	// Add Update attachments
	//
	if( is_array($attachments) && count($attachments) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'alerts', 'private', 'addUpdateAttachments');
		$rc = ciniki_alerts_addUpdateAttachments($ciniki, $alert_id, $attachments);
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'alerts');

	return array('stat'=>'ok');
}
