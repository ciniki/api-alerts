<?php
//
// Description
// -----------
// This method will return a list of alerts
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The business the bug is attached to.
// name:				The very brief bug description.
// 
// Returns
// -------
// <alerts>
// 	<alert id="1" />
// </alerts>
//
function ciniki_alerts_list($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'),
		'state'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No state specified'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No package specified'), 
		'module'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No module specified'), 
		'element'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No element specified'), 
		'element_id'=>array('required'=>'no', 'default'=>'', 'errmsg'=>'No element ID specified'), 
		'element_ids'=>array('required'=>'no', 'type'=>'idlist', 'errmsg'=>'No element ID specified'), 
		'orderby'=>array('required'=>'no', 'errmsg'=>'No ordering rule specified'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'alerts', 'private', 'checkAccess');
	$rc = ciniki_alerts_checkAccess($ciniki, $args['business_id'], 'ciniki.alerts.list', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$strsql = "SELECT ciniki_alerts.id, ciniki_alerts.ref_uid, ciniki_alerts.status, severity, ciniki_alerts.flags, "
		. "subject, ciniki_alerts.date_added, ciniki_alerts.last_updated ";
	if( isset($args['package']) && $args['package'] != '' 
		&& isset($args['module']) && $args['module'] != '' 
		&& isset($args['element']) && $args['element'] != '' 
		&& ((isset($args['element_id']) && $args['element_id'] != '') 
			|| (isset($args['element_ids']) && is_array($args['element_ids']))) ) {

		$strsql .= "FROM ciniki_alerts, ciniki_alert_attachments "
			. "WHERE ciniki_alerts.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_alerts.id = ciniki_alert_attachments.alert_id "
			. "AND ciniki_alert_attachments.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
			. "AND ciniki_alert_attachments.module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' "
			. "AND ciniki_alert_attachments.element = '" . ciniki_core_dbQuote($ciniki, $args['element']) . "' "
			. "";
		if( isset($args['element_ids']) && is_array($args['element_ids']) ) {
			$strsql .= "AND ciniki_alert_attachments.element_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['element_ids']) . ") ";
		} else {
			$strsql .= "AND ciniki_alert_attachments.element_id = '" . ciniki_core_dbQuote($ciniki, $args['element_id']) . "' ";
		}
	} else {
		$strsql .= "FROM ciniki_alerts "
			. "WHERE ciniki_alerts.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
	}
	//
	// Check if they only want alerts in a certain state
	//
	if( isset($args['state']) && $args['state'] == 'open' ) {
		$strsql .= "AND status = 1 ";
	} elseif( isset($args['state']) && $args['state'] == 'closed' ) {
		$strsql .= "AND status >= 60 ";
	}

	//
	// If the output should be in a tree structure by element
	//
	if( isset($args['orderby']) && $args['orderby'] == 'element_id' ) {

	} 

	//
	// Default the output to a tree structure by severity
	//
	else {
		$strsql .= "ORDER BY severity DESC, subject "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.alerts',
			array(
				array('container'=>'severities', 'fname'=>'severity', 'name'=>'severity', 'fields'=>array('severity')),
				array('container'=>'alerts', 'fname'=>'id', 'name'=>'id', 'fields'=>array('id', 'ref_uid', 'status', 'severity', 'flags', 'subject', 'date_added', 'last_updated')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		return array('stat'=>'ok', 'severities'=>$rc['severities']);
	}

	return array('stat'=>'ok');
}
?>
