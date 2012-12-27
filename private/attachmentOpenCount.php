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
	// Build the SQL query to count the number of alerts, in different statuses
	//
	$strsql = "SELECT severity, COUNT(ciniki_alerts.id) AS num_alerts "
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
		. "GROUP BY ciniki_alerts.severity "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.alerts', 'severity');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'490', 'msg'=>'Error retrieving alert information', 'err'=>$rc['err']));
	}
	$open_alerts = 0;
	$red_alerts = 0;
	$yellow_alerts = 0;
	$green_alerts = 0;
	if( isset($rc['severity'][50]) ) {
		$open_alerts += $rc['severity'][50];
		$red_alerts = $rc['severity'][50];
	}
	if( isset($rc['severity'][30]) ) {
		$open_alerts += $rc['severity'][30];
		$yellow_alerts = $rc['severity'][30];
	}
	if( isset($rc['severity'][10]) ) {
		$open_alerts += $rc['severity'][10];
		$green_alerts = $rc['severity'][10];
	}

	return array('stat'=>'ok', 'open_alerts'=>$open_alerts, 'red_alerts'=>$red_alerts, 'yellow_alerts'=>$yellow_alerts, 'green_alerts'=>$green_alerts);
}
?>
