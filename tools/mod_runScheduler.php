<?php
require_once(dirname(__FILE__)."/../core/system.php");
require_once(dirname(__FILE__)."/../http/classes/class_wms.php"); 

$sql = <<<SQL
SELECT scheduler_id, wms_id, wms_title, wms_auth_type, wms_username, wms_password, to_timestamp(wms_timestamp)::date AS last_change, last_status, fkey_upload_id, wms_upload_url, 
scheduler_interval,scheduler_mail,wms_owner,scheduler_publish,scheduler_searchable,scheduler_overwrite, scheduler_overwrite_categories,
scheduler_status FROM (SELECT scheduler_id, wms_id, fkey_wms_id, wms_title, wms_timestamp, wms_owner, wms_auth_type, wms_username, wms_password,
scheduler_interval,scheduler_mail,scheduler_publish,scheduler_searchable,scheduler_overwrite,scheduler_overwrite_categories,
scheduler_status,wms_upload_url FROM scheduler 
INNER JOIN wms ON scheduler.fkey_wms_id=wms.wms_id ) AS test 
LEFT OUTER JOIN mb_wms_availability ON test.fkey_wms_id = mb_wms_availability.fkey_wms_id;
SQL;
$res = db_query($sql);
//$resultObj = array();
//array containing all wms which should be updated
$wmsToUpdate = array();
echo "Scheduler for WMS start work...\n";
while ($row = db_fetch_array($res)) {
	$resultObj = array(
		"scheduler_id" 	=> $row['scheduler_id'],
		"wms_id"  =>  $row['wms_id'],
		"wms_title"  =>  $row['wms_title'],
	    "last_change"  =>  $row['last_change'],
	   	"last_status"  =>  $row['last_status'],
		"wms_upload_url"  =>  $row['wms_upload_url'],
	   	"fkey_upload_id"  =>  $row['fkey_upload_id'],
		"wms_owner"  =>  $row['wms_owner'],
	   	"scheduler_interval"  =>  $row['scheduler_interval'],
	   	"scheduler_mail"  =>  $row['scheduler_mail'],
	   	"scheduler_publish"  =>  $row['scheduler_publish'],
	   	"scheduler_searchable"  =>  $row['scheduler_searchable'],
		"scheduler_overwrite"  =>  $row['scheduler_overwrite'],
		"scheduler_overwrite_categories"  =>  $row['scheduler_overwrite_categories'],
	   	"scheduler_status"  =>  $row['scheduler_status'],
	   	"wms_auth_type"  =>  $row['wms_auth_type'],
	   	"wms_username"  =>  $row['wms_username'],
	   	"wms_password"  =>  $row['wms_password']
	);

    //check last_status: update immediately when changes were detected by capabilities monitoring (last_status == 0)
    if ($row['last_status'] === 0 || $row['last_status'] === '0') {
        array_push($wmsToUpdate, $resultObj);
    }
}
for ($i=0; $i<count($wmsToUpdate); $i++) {
    //create new wms object
	echo "\nTry to call wms ".$wmsToUpdate[$i]['wms_id']."\n";
	//call update via http!
	$uri = 'http://localhost/mapbender/php/mod_updateOwsRoot.php?';
	$query = 'serviceType=wms&serviceId=' . $wmsToUpdate[$i]['wms_id']; 
	$query .= '&schedulerPublish=' . (($wmsToUpdate[$i]['scheduler_publish'] === 1 || $wmsToUpdate[$i]['scheduler_publish'] === '1') ? 'true' : 'false');
	$query .= '&schedulerSearchable=' . (($wmsToUpdate[$i]['scheduler_searchable'] === 1 || $wmsToUpdate[$i]['scheduler_searchable'] === '1') ? 'true' : 'false');
	$query .= '&schedulerOverwrite=' . (($wmsToUpdate[$i]['scheduler_overwrite'] === 1 || $wmsToUpdate[$i]['scheduler_overwrite'] === '1') ? 'true' : 'false');
	$query .= '&schedulerOverwriteCategories=' . (($wmsToUpdate[$i]['scheduler_overwrite_categories'] === 1 || $wmsToUpdate[$i]['scheduler_overwrite_categories'] === '1') ? 'true' : 'false');
	$updateConnector = new Connector();
	$updateConnector->timeout = 120;
	$resultJson = $updateConnector->load($uri . $query);
	$result = json_decode($resultJson);
	if ($updateConnector->timedOut) {
	    $result->success = false;
	    $result->message = 'Update service timedout!';
	    $resultJson = json_encode($result);
	}
	if ($result->success) {
	    echo "WMS updated successfully!\n";
	    echo $resultJson;
	    echo '\n';
	} else {
	    echo "WMS update was not possibly!\n";
	    echo $resultJson;
	    echo '\n';
	}
	//notify per mail if set in scheduler
	if($wmsToUpdate[$i]['scheduler_mail']) {
	    $mail_error_message = "";
	    $admin = new administration();
		//get all users which have the wms integrated in their guis
		$ownerIds = $admin->getOwnerByWms($wmsToUpdate[$i]['wms_id']);
		//get all users who subscribed to this service
		$subscriberIds = $admin->getSubscribersByWms($wmsToUpdate[$i]['wms_id']);
		
		$recipientIds = array();
		if ($ownerIds && count($ownerIds) > 0) {
			$recipientIds = array_merge($recipientIds, $ownerIds);
		}
		if ($subscriberIds && count($subscriberIds) > 0) {
			$recipientIds = array_merge($recipientIds, $subscriberIds);
		}
		$recipientIds = array_unique($recipientIds);

		if (count($recipientIds) > 0) {
			$recipientMailAddresses = array();
			foreach ($recipientIds as $userId) {
				$adrTmp = $admin->getEmailByUserId($userId);
				if (!in_array($adrTmp, $recipientMailAddresses) && !empty($adrTmp)) {
					$recipientMailAddresses[] = $adrTmp;
				} 
			}
			$adrRoot = $admin->getEmailByUserId("1");	
			$from = $adrRoot;
			if($from != "") {
				$wmsTitle = $admin->getWmsTitleByWmsId($wmsToUpdate[$i]['wms_id']);
			    if ($result->success) {
    				$body = "WMS '" . $wmsTitle . "' has been updated by the scheduler update. \n\nYou may want to check the changes as you are an owner or subscriber of this WMS.";
			    } else {
			        $body = "WMS '" . $wmsTitle . "' could not be updated by the scheduler. \n\nYou have to check the configuration of this WMS.";
			    }
    			for ($m=0; $m<count($recipientMailAddresses); $m++) {
					echo "Sending notification to: " . $recipientMailAddresses[$m] . "\n";
     				if (!$admin->sendEmail($from, $from, $recipientMailAddresses[$m], $recipientMailAddresses[$m], "[Mapbender Update Scheduler] WMS '" . $wmsTitle . "' has been updated", $body, $mail_error_message)) {
    					echo "Notification could not be send!\n";
    				}
    			}
			}
		}
	}	
	if($result->success) {
	    $status = 1;
	    // update availability status to 1 (OK) and clear diff since DB was updated to current capabilities
	    $sql_avail = "UPDATE mb_wms_availability SET last_status = 1, cap_diff = '' WHERE fkey_wms_id = $1";
	    try {
	        db_prep_query($sql_avail, array($wmsToUpdate[$i]['wms_id']), array('i'));
	    } catch (Exception $e) {
	        // ignore
	    }
	}
	else {
	    $status = -1;
	}	
    $sql = <<<SQL
UPDATE scheduler SET scheduler_status = $1, scheduler_status_error_message = $2, scheduler_change = now() WHERE fkey_wms_id = $3;
SQL;
    $v = array($status, $result->message, $wmsToUpdate[$i]['wms_id']);
	$t = array('i','s','i');
	try {
		$res = db_prep_query($sql,$v,$t);
	}
	catch (Exception $e){
		echo "ERROR";
	}
}
?>
