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
function ciniki_alerts_addUpdateAttachments($ciniki, $alert_id, $attachments) {
	
	//
	// Check the attachment array to make sure it has what's required
	//
	if( !is_array($attachments) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'478', 'msg'=>'Unable to update alert'));
	}

	if( $alert_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'479', 'msg'=>'Unable to update alert'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Add/update each attachment
	//
	foreach($attachments as $attachment) {
		if( !is_array($attachment) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'480', 'msg'=>'Unable to update alert'));
		}
		if( !isset($attachment['package']) || $attachment['package'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'481', 'msg'=>'Unable to update alert'));
		}
		if( !isset($attachment['module']) || $attachment['module'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'482', 'msg'=>'Unable to update alert'));
		}
		if( !isset($attachment['element']) || $attachment['element'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'483', 'msg'=>'Unable to update alert'));
		}
		if( !isset($attachment['element_id']) || $attachment['element_id'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'484', 'msg'=>'Unable to update alert'));
		}
		$strsql = "INSERT INTO ciniki_alert_attachments (alert_id, package, module, element, element_id, "
			. "flags, date_added, last_updated) VALUES ( "
			. "'" . ciniki_core_dbQuote($ciniki, $alert_id) . "' "
			. ", '" . ciniki_core_dbQuote($ciniki, $attachment['package']) . "' "
			. ", '" . ciniki_core_dbQuote($ciniki, $attachment['module']) . "' "
			. ", '" . ciniki_core_dbQuote($ciniki, $attachment['element']) . "' "
			. ", '" . ciniki_core_dbQuote($ciniki, $attachment['element_id']) . "' "
			. "";

		// Check for flags
		if( isset($attachment['type']) && $attachment['type'] == 'primary' ) {
			$flags = 0x01;
		} else {
			$flags = 0;
		}
		$strsql .= ", '$flags', UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
			. " ON DUPLICATE KEY UPDATE flags = (flags | $flags), last_updated = UTC_TIMESTAMP() "
			. "";

		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.alerts');
		if( $rc['stat'] != 'ok' ) {	
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'485', 'msg'=>'Invalid alert reference', 'err'=>$rc['err']));
		}
	}

	return array('stat'=>'ok');
}
