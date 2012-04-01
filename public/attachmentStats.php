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
function ciniki_alerts_attachmentStats($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No package specified'), 
		'module'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No module specified'), 
		'element'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No element specified'), 
		'element_id'=>array('required'=>'no', 'default'=>'', 'errmsg'=>'No element ID specified'), 
		'element_ids'=>array('required'=>'no', 'type'=>'idlist', 'errmsg'=>'No element ID specified'), 
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
	
	//
	// Build the SQL query to count the number of alerts, in different statuses
	//
	$strsql = "SELECT status, severity, COUNT(ciniki_alerts.id) AS num_alerts "
		. "FROM ciniki_alerts, ciniki_alert_attachments "
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

	$strsql .= ""
		. "GROUP BY ciniki_alerts.severity, ciniki_alerts.status "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'core', 'status');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'486', 'msg'=>'Error retrieving alert information', 'err'=>$rc['err']));
	}

	$open = array('0'=>0, '10'=>0, '30'=>0, '50'=>0);
	$closed = array('0'=>0, '10'=>0, '30'=>0, '50'=>0);
	foreach($rc['rows'] as $snum => $row) {
		if( $row['status'] == 1 ) { 
			$open['0'] += $row['num_alerts']; 
			if( $row['severity'] >= 10 && $row['severity'] < 30 ) {
				$open['10'] += $row['num_alerts'];
			} else if( $row['severity'] >= 30 && $row['severity'] < 50 ) {
				$open['30'] += $row['num_alerts'];
			} else if( $row['severity'] >= 50 ) {
				$open['50'] += $row['num_alerts'];
			}
		}
		if( $row['status'] >= 60 ) { 
			$closed['0'] += $row['num_alerts']; 
			if( $row['severity'] >= 10 && $row['severity'] < 30 ) {
				$closed['10'] += $row['num_alerts'];
			} else if( $row['severity'] >= 30 && $row['severity'] < 50 ) {
				$closed['30'] += $row['num_alerts'];
			} else if( $row['severity'] >= 50 ) {
				$closed['50'] += $row['num_alerts'];
			}
		}
	}
	$severities = array(
		array('red'=>array('severity'=>'50', 'open'=>$open['50'], 'closed'=>$closed['50'])),
		array('yellow'=>array('severity'=>'30', 'open'=>$open['30'], 'closed'=>$closed['30'])),
		array('green'=>array('severity'=>'10', 'open'=>$open['10'], 'closed'=>$closed['10'])),
		);

	return array('stat'=>'ok', 'open'=>$open['0'], 'closed'=>$closed['0'], 'severities'=>$severities, 'red'=>$open['50'], 'yellow'=>$open['30'], 'green'=>$open['10']);
}
?>
