<?php
//
// Description
// -----------
// This method returns the stats for attachments for a business.
//
// Arguments
// ---------
//
// Returns
// -------
// <attachments total=23>
// 	<attachment name="host" count="23"
// </attachments>
function ciniki_alerts_attachmentOpenCount($ciniki, $business_id, $package, $module, $element, $element_id) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'alerts', 'private', 'checkAccess');
	$rc = ciniki_alerts_checkAccess($ciniki, $business_id, 'ciniki.alerts.list', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Build the SQL query to count the number of alerts, in different statuses
	//
	$strsql = "SELECT status, COUNT(ciniki_alerts.id) AS num_alerts "
		. "FROM ciniki_alerts, ciniki_alert_attachments "
		. "WHERE ciniki_alerts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_alerts.id = ciniki_alert_attachments.alert_id "
		. "AND ciniki_alert_attachments.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
		. "AND ciniki_alert_attachments.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
		. "AND ciniki_alert_attachments.element = '" . ciniki_core_dbQuote($ciniki, $element) . "' "
		. "";
	if( isset($element_ids) && is_array($element_ids) ) {
		$strsql .= "AND ciniki_alert_attachments.element_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $element_ids) . ") ";
	} else {
		$strsql .= "AND ciniki_alert_attachments.element_id = '" . ciniki_core_dbQuote($ciniki, $element_id) . "' ";
	}
	// Grab ONLY open alerts
	$strsql .= "AND ciniki_alerts.status = 1 ";

	$strsql .= ""
		. "GROUP BY ciniki_alerts.status "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'core', 'status');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'490', 'msg'=>'Error retrieving alert information', 'err'=>$rc['err']));
	}
	if( isset($rc['status'][1]) ) {
		return array('stat'=>'ok', 'open_alerts'=>$rc['status'][1]);
	}

	return array('stat'=>'ok', 'open_alerts'=>0);
}
?>
