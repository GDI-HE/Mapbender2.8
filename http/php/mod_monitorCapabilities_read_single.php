
<?php
# $Id: mod_monitorCapabilities_read_single.php 76 2006-08-15 12:25:34Z heuser $
# http://www.mapbender.org/index.php/Monitor_Capabilities
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
/*  
 * @security_patch irv done
 */ 
//security_patch_log(__FILE__,__LINE__);
$con = db_connect($DBSERVER,$OWNER,$PW);
db_select_db(DB,$con);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
</head>
<body>
<?php
$admin = new administration();
if (isset($_REQUEST["serviceType"]) & $_REQUEST["serviceType"] != "") {
	$testMatch = $_REQUEST["serviceType"];	
 	if (!($testMatch == 'wms' or $testMatch == 'wfs')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>serviceType</b> is not valid (wms, wfs).<br/>'; 
		die(); 		
 	}
	$serviceType = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
        //validate integer
        $testMatch = $_REQUEST["id"];
        //give max 99 entries - more will be to slow
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                echo 'Parameter <b>id</b> is not valid (integer).<br/>';
                die();
        }
        $id = $testMatch;
        $testMatch = NULL;
}
$sql = "SELECT upload_id, status, status_comment, timestamp_begin, timestamp_end, upload_url, updated FROM mb_monitor ";
switch ($serviceType) {
	case "wms":
		$sql .= "WHERE fkey_wms_id = $1 AND NOT status = '-2' ORDER BY upload_id DESC";
		break;
	case "wfs":
		$sql .= "WHERE fkey_wfs_id = $1 AND NOT status = '-2' ORDER BY upload_id DESC";
		break;
}
$v = array($id);
$t = array('i');
$res = db_prep_query($sql,$v,$t);
$cnt=0;
while ($row = db_fetch_array($res)) {
	$upload_id[$cnt] = db_result($res,$cnt,"upload_id");
	$status[$cnt] = intval(db_result($res,$cnt,"status"));
	$comment[$cnt] = db_result($res,$cnt,"status_comment");
	$timestamp_begin = db_result($res,$cnt,"timestamp_begin");
	$timestamp_end = db_result($res,$cnt,"timestamp_end");
	$upload_url[$cnt] = db_result($res,$cnt,"upload_url");
	if ($status[$cnt] == '0' || $status[$cnt] == '1') {
		$response_time[$cnt] = strval($timestamp_end-$timestamp_begin) . " s"; 
	}
	else {
		$response_time[$cnt] = "n/a"; 
	}
	$cnt++;
}
$str = "<span style='font-size:30'>"._mb("Monitoring results")."</span><hr><br>\n";//Monitoring Ergebnisse
switch ($serviceType) {
	case "wms":
		$str .= "<b>" . $id . "</b><br>" . $admin->getWmsTitleByWmsId($id) . "<br><br><br>\n";
		break;
	case "wfs":
		$str .= "<b>" . $id . "</b><br>" . $admin->getWfsTitleByWfsId($id) . "<br><br><br>\n";
		break;
}
$str .= "<table cellpadding=10 cellspacing=0 border=0>";
$str .= "<tr bgcolor='#dddddd'><th align='left'>date</th><th align='left' colspan = 2>"._mb("Status")."</th><th align='center'>"._mb("Response time")."</th><th align='center'>"._mb("Diff")."</th></tr>";//Status Antwortzeit

for ($k=0; $k<count($upload_id); $k++) {
	$img = "stop.png";
	if ($status[$k]==0) $img = "wait.png";
	elseif ($status[$k]==1) $img = "go.png";

	if (fmod($k, 2) == 1) {
		$str .= "\n\t\t<tr bgcolor='#e6e6e6'>";
	}
	else {
		$str .= "\n\t\t<tr bgcolor='#f0f0f0'>";
	}
	$str .= "\n\t\t\t<td>".date("F j, Y, G:i:s", $upload_id[$k])."</td>";
	$str .= "\n\t\t\t<td><a href='".$upload_url[$k]."' target=_blank><img title='Connect to service' border=0 src = '../img/trafficlights/". $img. "'></a></td>";
	$str .= "\n\t\t\t<td>" . $comment[$k] . "</td>";
	$str .= "\n\t\t\t<td align='center'>" . $response_time[$k] . "</td>";
	if ($status[$k]==0) {
	    if ($serviceType == "wms") {
	        $str .= "\n\t\t\t<td align='center'><a href='../php/mod_showCapDiff.php?id=" . $id . "&upload_id=" . $upload_id[$k] . "&serviceType=" . $serviceType ."' target=_blank>Show diff</a></td>";
	    } else {
	       $str .= "\n\t\t\t<td align='center'>Diff for wfs not yet available!</td>";
	    }
	} else {
	    //$str .= "\n\t\t\t<td align='center'><a href='../php/mod_showCapDiff.php?id=" . $id . "&upload_id=" . $upload_id[$k] . "&serviceType=" . $serviceType ."' target=_blank>Show diff</a></td>";
	    //$str .= "\n\t\t\t<td align='center'><a href='../php/mod_monitorCapabilities_read_single_diff.php?wmsid=" . $id . "&upload_id=" . $upload_id[$k] . "' target=_blank>Show diff</a></td>";    
	    $str .= "\n\t\t\t<td align='center'></td>";
	    
	}
	
#	$str .= "\n\t\t\t<td><a href='output_".$wms_id[$k]."_".$max.".txt' target=_blank>log</a></td>";
}
$str .= "\n\t</table>\n\t";
echo $str;

?>
</body></html>
