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
function ciniki_alerts__addIncident($ciniki, $alert_id, $incident) {

	if( !is_array($incident) || !isset($incident['content']) || $incident['content'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'475', 'msg'=>'Invalid incident reference'));
	}

	if( $alert_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'477', 'msg'=>'Invalid alert identifier'));
	}

	$strsql = "INSERT INTO ciniki_alert_incidents (alert_id, user_id, data, content, incident_date, "
		. "date_added, last_updated) VALUES ( "
		. "'" . ciniki_core_dbQuote($ciniki, $alert_id) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "";
	// Check for data
	if( isset($incident['data']) && $incident['data'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $incident['data']) . "' ";
	} else {
		$strsql .= ", '' ";
	}
	// Check for content
	if( isset($incident['content']) && $incident['content'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $incident['content']) . "' ";
	} else {
		$strsql .= ", 'No information provided' ";
	}
	// Check for incident_date
	if( isset($incident['incident_date']) && $incident['incident_date'] != '' ) {
		$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $incident['incident_date']) . "' ";
	} else {
		$strsql .= ", UTC_TIMESTAMP() ";
	}
	$strsql .= ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.alerts');
	if( $rc['stat'] != 'ok' ) {	
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'476', 'msg'=>'Invalid alert reference', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
