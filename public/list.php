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
		'severity'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No state specified'), 
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
		. "subject, ciniki_alerts.date_added, ciniki_alerts.last_updated, "
		. "a1.package AS a_package, "
		. "a1.module AS a_module, "
		. "a1.element AS a_element, "
		. "a1.element_id AS a_element_id, "
		. "CONCAT_WS('.', ciniki_alert_attachments.package, ciniki_alert_attachments.module, "
			. "ciniki_alert_attachments.element, ciniki_alert_attachments.element_id) AS attachment_id, "
		. "IF(ciniki_alert_attachments.flags&0x01, 'primary', 'secondary') AS type, "
		. "ciniki_alert_attachments.package, "
		. "ciniki_alert_attachments.module, "
		. "ciniki_alert_attachments.element, "
		. "ciniki_alert_attachments.element_id "
		. "";
	if( isset($args['package']) && $args['package'] != '' 
		&& isset($args['module']) && $args['module'] != '' 
		&& isset($args['element']) && $args['element'] != '' 
		&& ((isset($args['element_id']) && $args['element_id'] != '') 
			|| (isset($args['element_ids']) && is_array($args['element_ids']))) ) {

		$strsql .= "FROM ciniki_alert_attachments a1, ciniki_alerts "
			. "LEFT JOIN ciniki_alert_attachments ON (ciniki_alerts.id = ciniki_alert_attachments.alert_id) "
			. "WHERE ciniki_alerts.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_alerts.id = a1.alert_id "
			. "AND a1.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
			. "AND a1.module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' "
			. "AND a1.element = '" . ciniki_core_dbQuote($ciniki, $args['element']) . "' "
			. "";
		if( isset($args['element_ids']) && is_array($args['element_ids']) ) {
			$strsql .= "AND a1.element_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['element_ids']) . ") ";
		} else {
			$strsql .= "AND a1.element_id = '" . ciniki_core_dbQuote($ciniki, $args['element_id']) . "' ";
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
	// Check if they only want alerts in a certain severity
	//
	if( isset($args['severity']) && $args['severity'] != '' ) {
		$strsql .= "AND severity = '" . ciniki_core_dbQuote($ciniki, $args['severity']) . "' ";
	}

	//
	// If the output should be in a tree structure by element
	//
	if( isset($args['orderby']) && $args['orderby'] == 'element_id' ) {

	} 

	//
	// Default the output to a tree structure by severity
	//
	elseif( isset($args['orderby']) && $args['orderby'] == 'alerts' ) {
		$strsql .= "ORDER BY severity DESC, subject "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.alerts',
			array(
				array('container'=>'alerts', 'fname'=>'id', 'name'=>'alert', 
					'fields'=>array('id', 'ref_uid', 'status', 'severity', 'flags', 'subject', 'date_added', 'last_updated')),
				array('container'=>'attachments', 'fname'=>'attachment_id', 'name'=>'attachment', 
					'fields'=>array('type', 'package', 'module', 'element', 'element_id',)),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['alerts']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'489', 'msg'=>'No alerts found'));
		}
		return array('stat'=>'ok', 'alerts'=>$rc['alerts']);
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
				array('container'=>'alerts', 'fname'=>'id', 'name'=>'id', 
					'fields'=>array('id', 'ref_uid', 'status', 'severity', 'flags', 'subject', 'date_added', 'last_updated')),
				array('container'=>'attachments', 'fname'=>'attachment_id', 'name'=>'attachment', 
					'fields'=>array('type', 'package', 'module', 'element', 'element_id',)),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['severities']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'488', 'msg'=>'No alerts found'));
		}
		return array('stat'=>'ok', 'severities'=>$rc['severities']);
	}

	return array('stat'=>'ok');
}
?>
