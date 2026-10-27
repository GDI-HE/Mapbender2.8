<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_user.php";
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";
require_once dirname(__FILE__) . "/../classes/class_iso19139.php";
global $hierarchyLevel;
$ajaxResponse = new AjaxResponse($_POST);
//get hierarchyLevel filter from ajax call
$hierarchyLevel = $ajaxResponse->getParameter("hierarchyLevel");
//$e = new mb_exception("plugins/metadata_manager_server.php: ".$hierarchyLevel);
if ($hierarchyLevel != 'application') {
    $hierarchyLevel = 'metadata';
}
function abort ($message) {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die();
};

function validateType($variable, $value) {
	$startString = substr($variable, 0, 1);
	switch ($startString) {
		case "s":
			if (is_string($value)) {
				return true;
			} else {
				return false;
			}
			break;
		case "i":
			if (is_int($value)) {
				return true;
			} else {
				return false;
			}
			break;
		case "b":
			if ($value == true || $value == false) {
				return true;
			} else {
				return false;
			}
			break;
		default: 
			return false;
			break;
	}
};

//parse row (array) from database to new array - change some of the values and add some further
function parseMetadataRow($row, $withOutFirstColumn = false) {
	global $hierarchyLevel;
	//convert NULL to '', NULL values cause datatables to crash
	$walk = array_walk($row, create_function('&$s', '$s=strval($s);'));
	//preview with uuid
	$row[0] = $row[0];
	$row[1] = $row[1];
	//$row[2] = "<a class='modalDialog' target='_blank' id='metadata_".$row[0]."' href='../php/mod_exportIso19139.php?url=".urlencode(MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$row[2])."'>".$row[2]."</a>";
	$row[2] = "<a class='modalDialog' id='metadata_".$row[0]."' url='../php/mod_exportIso19139.php?url=".urlencode(MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$row[2])."'>".$row[2]."</a>";
	$row[3] = $row[3];
	$row[4] = $row[4];
	$coupledResourceRow = 5;
	$coupledResources = json_decode($row[$coupledResourceRow]);
	$row[$coupledResourceRow] = "";
	//get layer list
	$numberLayers = 0;
	foreach ($coupledResources->coupledResources->layerIds as $layerId) {
		//$row[$coupledResourceRow] .= "<a class='modalDialog' target='_blank' id='layer_".$row[0]."_".$numberLayers."' href='../php/mod_showMetadata.php?languageCode=de&resource=layer&id=".$layerId."'>".$layerId."</a>".", ";
		$row[$coupledResourceRow] .= "<a class='modalDialog' id='layer_".$row[0]."_".$numberLayers."' url='../php/mod_showMetadata.php?languageCode=de&resource=layer&id=".$layerId."'>".$layerId."</a>".", ";
		$numberLayers++;
	}
	$row[$coupledResourceRow] = rtrim($row[$coupledResourceRow], ', ');
	//generate column for featuretypes - problem: row has to be inserted after !!!!
	//#6
	array_splice($row, $coupledResourceRow+1, 0, "");
	$row[$coupledResourceRow+1] = "";
	//get featuretype list
	$numberFeaturetypes = 0;
	foreach ($coupledResources->coupledResources->featuretypeIds as $featuretypeId) {
		//$row[$coupledResourceRow+1] .= "<a class='modalDialog'  target='_blank' id='featuretype_".$row[0]."_".$numberFeaturetypes."' href='../php/mod_showMetadata.php?languageCode=de&resource=featuretype&id=".$featuretypeId."'>".$featuretypeId."</a>".", ";
		$row[$coupledResourceRow+1] .= "<a class='modalDialog' id='featuretype_".$row[0]."_".$numberFeaturetypes."' url='../php/mod_showMetadata.php?languageCode=de&resource=featuretype&id=".$featuretypeId."'>".$featuretypeId."</a>".", ";
		$numberFeaturetypes++;
	}
	$row[$coupledResourceRow+1] = rtrim($row[$coupledResourceRow+1], ', ');
	//origin
	
	//add column for defining searchability
	if ($row[8] == "t") {
		$row[8] = "<input style='cursor:pointer;' class='toggleSearchability' title='"._mb("Toggle searchability")."' type='checkbox' checked/>";
	} else {
		$row[8] = "<input style='cursor:pointer;' class='toggleSearchability' title='"._mb("Toggle searchability")."' type='checkbox'/>";
	}
	if ($row[9] == "t") {
		$row[9] = "<input style='cursor:pointer;' class='toggleExport' title='"._mb("Toggle catalogue export")."' type='checkbox' checked/>";
	} else {
		$row[9] = "<input style='cursor:pointer;' class='toggleExport' title='"._mb("Toggle catalogue export")."' type='checkbox'/>";
	}
	//add column for deleting metadata
	$row[] = "<img style='cursor:pointer;' class='deleteImg' title='"._mb("Delete")."' src='../img/cross.png' />";
	if ($row[7] == 'metador' || $row[7] == 'upload') {
		if ($hierarchyLevel == 'application') {
		    $row[] = "<img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadata(".$row[0].",null,\"".$hierarchyLevel."\",false);return false;'/>";
		} else {
		    $row[] = "<img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon(".$row[0].",null,\"".$hierarchyLevel."\",false);return false;'/>";
		}
	} else if ($row[7] == 'capabilities' || $row[7] == 'external') {
		$row[] = "<img style='cursor:pointer;width:16px' class='clickable refreshMetadata' title='"._mb("Metadaten von URL aktualisieren")."' src='../img/gnome/view-refresh.png' />";
	} else {
		$row[] = "";
	}
	if ($withOutFirstColumn == true) {
		$newRow = array($row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11]);
		$row = $newRow;
	}
	/*if ($hierachyLevel == 'application') {
		$newRow = array($row[0],$row[1],$row[2],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10]);
		$row = $newRow;
	}*/
	return $row;
}

function getMetadata ($metadataId = null) {
	global $hierarchyLevel;
	$user = new User(Mapbender::session()->get("mb_user_id"));
	if ($hierarchyLevel == 'application') {
	    $metadataIdArray = $user->getOwnedMetadata($hierarchyLevel);
	} else {
	    $metadataIdArray = $user->getOwnedMetadata();
	}
	if (!is_null($metadataId) && !in_array($metadataId, $metadataIdArray)) {
		abort(_mb("You are not allowed to access this metadata."));
	}
	return $metadataIdArray;
}

//validate user which sends ajax

$user = new User(Mapbender::session()->get("mb_user_id"));

//$e = new mb_exception("method param: ".$ajaxResponse->getMethod());

switch ($ajaxResponse->getMethod()) {	
	case "toggleSearchability" :
		$id = $ajaxResponse->getParameter("id");
		//first select metadata to see, if person who wants to delete it is really the owner!
		$sql = <<<SQL

SELECT fkey_mb_user_id, searchable FROM mb_metadata WHERE metadata_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not read metadata from database!"));
			$ajaxResponse->send();
			die;	
		}
		$row = db_fetch_assoc($res);
		if ((integer)$row['fkey_mb_user_id'] !== $user->id) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("User not allowed to toogle searchability of metadata, because he is not the owner!"));
			$ajaxResponse->send();
			die();
		}
		//do the toggling
		if ($row['searchable'] == 't') {
			$export2csw = 'f';
			$message = _mb('Searchability of metadata was deactivated!');
		} else {
			$export2csw = 't';
			$message = _mb('Searchability of metadata was activated!');
		}
		$sql = <<<SQL

UPDATE mb_metadata SET searchable = $2  WHERE metadata_id = $1;

SQL;
		$v = array($id, $export2csw);
		$t = array('i', 'b');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not toogle export for selected metadata!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage($message);
		$ajaxResponse->setSuccess(true);
		break;
	case "toggleExport" :
		$id = $ajaxResponse->getParameter("id");
		//first select metadata to see, if person who wants to delete it is really the owner!
		$sql = <<<SQL

SELECT fkey_mb_user_id, export2csw FROM mb_metadata WHERE metadata_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not read metadata from database!"));
			$ajaxResponse->send();
			die;	
		}
		$row = db_fetch_assoc($res);
		if ((integer)$row['fkey_mb_user_id'] !== $user->id) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("User not allowed to toogle export for metadata, because he is not the owner!"));
			$ajaxResponse->send();
			die();
		}
		//do the toggling
		if ($row['export2csw'] == 't') {
			$export2csw = 'f';
			$message = _mb('Export of metadata to external catalogues was deactivated!');
		} else {
			$export2csw = 't';
			$message = _mb('Export of metadata to external catalogues was activated!');
		}
		$sql = <<<SQL

UPDATE mb_metadata SET export2csw = $2  WHERE metadata_id = $1;

SQL;
		$v = array($id, $export2csw);
		$t = array('i', 'b');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not toogle export for selected metadata!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage($message);
		$ajaxResponse->setSuccess(true);
		break;
	case "deleteMetadata" :
		$id = $ajaxResponse->getParameter("id");
		//first select metadata to see, if person who wants to delete it is really the owner!
		$sql = <<<SQL

SELECT fkey_mb_user_id FROM mb_metadata WHERE metadata_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not read metadata from database!"));
			$ajaxResponse->send();
			die;	
		}
		$row = db_fetch_assoc($res);
		if ((integer)$row['fkey_mb_user_id'] !== $user->id) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("User not allowed to delete requested metadata from database, because he is not the owner!"));
			$ajaxResponse->send();
			die();
		}
		//delete the metadata 
		$sql = <<<SQL

DELETE FROM mb_metadata WHERE metadata_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not delete metadata in database!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage(_mb("Metadata deleted!"));
		$ajaxResponse->setSuccess(true);
		break;
	case "updateMetadataFromUrl" :
		$id = $ajaxResponse->getParameter("id");
		if (!validateType("i", $id) && !is_numeric($id)) {
			abort(_mb("Invalid metadata ID."));
		}
		$id = intval($id);

		// First select metadata to verify ownership and read URL / origin
		$sql = <<<SQL

SELECT metadata_id, link, origin, uuid, fkey_mb_user_id, fkey_mb_group_id, searchable, export2csw, md_proxy, md_format, linktype, randomid FROM mb_metadata WHERE metadata_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql, $v, $t);
		}
		catch (Exception $e) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not read metadata from database!"));
			$ajaxResponse->send();
			die();
		}
		$row = db_fetch_assoc($res);
		if (!$row) {
			abort(_mb("Metadata record not found!"));
		}
		if ((integer)$row['fkey_mb_user_id'] !== $user->id && $user->id !== 1) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("User not allowed to update metadata, because he is not the owner!"));
			$ajaxResponse->send();
			die();
		}
		if ($row['origin'] !== 'capabilities' && $row['origin'] !== 'external') {
			abort(_mb("Only metadata with origin 'capabilities' or 'external' can be refreshed from URL!"));
		}
		if (empty($row['link'])) {
			abort(_mb("No URL configured for this metadata record!"));
		}

		// Fetch remote XML via connector
		$connector = new connector();
		$connector->timeOut = 15;
		$connector->load($row['link']);
		if ($connector->timedOut == true) {
			$e = new mb_exception("updateMetadataFromUrl: Request to " . $row['link'] . " timed out!");
			abort(_mb("Could not fetch metadata from URL: Request timed out!"));
		}
		if (empty($connector->file)) {
			abort(_mb("Could not fetch metadata from URL: Response was empty!"));
		}
		$xml = $connector->file;

		// Parse XML using Iso19139 - on failure, existing database record is NOT modified
		$iso19139 = new Iso19139();
		$parsed = $iso19139->createMapbenderMetadataFromXML($xml);
		if ($parsed === false || empty($iso19139->title)) {
			$e = new mb_exception("updateMetadataFromUrl: Failed to parse XML from " . $row['link']);
			abort(_mb("Could not parse XML metadata from URL!"));
		}

		// Set required properties
		$iso19139->href = $row['link'];
		$iso19139->origin = $row['origin'];
		if (!empty($row['randomid'])) {
			$iso19139->randomId = $row['randomid'];
		}
		if (empty($iso19139->fileIdentifier)) {
			$iso19139->fileIdentifier = $row['uuid'];
		}
		$iso19139->searchable = ($row['searchable'] == 't' || $row['searchable'] === true || $row['searchable'] == '1');
		$iso19139->export2Csw = ($row['export2csw'] == 't' || $row['export2csw'] === true || $row['export2csw'] == '1');
		$iso19139->harvestResult = 1;
		$iso19139->harvestException = null;

		// Update record in database
		$resUpdate = $iso19139->updateMetadataById($id, false);
		if (!$resUpdate) {
			abort(_mb("Could not update metadata in database!"));
		}

		// Update keywords and categories
		$sqlRel = "SELECT fkey_layer_id, fkey_featuretype_id FROM ows_relation_metadata WHERE fkey_metadata_id = $1";
		$resRel = db_prep_query($sqlRel, array($id), array('i'));
		$hasCoupled = false;
		if ($resRel) {
			while ($rowRel = db_fetch_assoc($resRel)) {
				if (!empty($rowRel['fkey_layer_id'])) {
					$iso19139->insertKeywordsAndCategoriesIntoDB($id, 'layer', $rowRel['fkey_layer_id']);
					$hasCoupled = true;
				} else if (!empty($rowRel['fkey_featuretype_id'])) {
					$iso19139->insertKeywordsAndCategoriesIntoDB($id, 'featuretype', $rowRel['fkey_featuretype_id']);
					$hasCoupled = true;
				}
			}
		}
		if (!$hasCoupled) {
			$iso19139->insertKeywordsAndCategoriesIntoDB($id, 'dataset', null);
		}

		// Retrieve updated timestamp and title
		$sqlUpdated = "SELECT lastchanged, title FROM mb_metadata WHERE metadata_id = $1";
		$resUpdated = db_prep_query($sqlUpdated, array($id), array('i'));
		$rowUpdated = ($resUpdated) ? db_fetch_assoc($resUpdated) : array('lastchanged' => '', 'title' => '');

		$ajaxResponse->setMessage(_mb("Metadata successfully refreshed from source URL!"));
		$ajaxResponse->setResult(array(
			"metadataId" => $id,
			"lastchanged" => $rowUpdated['lastchanged'],
			"title" => $rowUpdated['title']
		));
		$ajaxResponse->setSuccess(true);
		break;
	case "getHeader" :
		//$hierarchyLevel = $ajaxResponse->getParameter("hierarchyLevel");
		switch ($hierarchyLevel) {
		    case "application_test":
		        $header = array(
				_mb("ID"),
				_mb("UUID"),
				_mb("Title"),
				_mb("Last changed"),
				_mb("Origin"),
				_mb("Searchability"),
				_mb("Catalogue export"),
				_mb("Delete"),
				_mb("Edit")
			);
		        break;
		    default:
			$header = array(
				_mb("ID"),
				_mb("UUID"),
				_mb("Title"),
				_mb("Last changed"),
				_mb("Layers"),
				_mb("Featuretypes"),
				_mb("Origin"),
				_mb("Searchability"),
				_mb("Catalogue export"),
				_mb("Delete"),
				_mb("Edit")
			);
			break;
		
		}
		$translation = array(
			"confirmSearchabilityMessage" => _mb('Do you really want to change the searchability?'),
			"confirmExportMessage" => _mb('Do you really want to change the export handling for this metadata?'),
			"confirmDeleteMessage" => _mb('Do you really want to delete this entry?'),
			"confirmRefreshMessage" => _mb('Do you really want to refresh this metadata from its source URL?')
		);
		$resultObj['header'] = $header;
		$resultObj['translation'] = $translation;
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		
		break;
	case "loadTableIncremental": 
		//defaults to server side handling
		//use datatables serverside api: http://legacy.datatables.net/usage/server-side (version <= 1.9!!!)
		//parse relevant ajax variables (they also available in params var - cause mapbenders ajax class demand this):
		$ajaxVariables = array("sEcho","iDisplayLength","iDisplayStart","iColumns","sSearch","sSearch_1","bSortable_1","iSortingCols","iSortCol_1","sSortDir_1","sEcho","hierarchyLevel");
		foreach($ajaxVariables as $variable) {
			//validate type
			//$e = new mb_exception("var:".$variable." - value: ".$ajaxResponse->getParameter($variable));
			if (validateType($variable, $ajaxResponse->getParameter($variable)) == true) {
				${$variable} = $ajaxResponse->getParameter($variable);
			}
		}
		//first count all records for this user
		$metadataIdArray = getMetadata();
		$highLevelObj["iTotalRecords"] = count($metadataIdArray);	
		$highLevelObj["iTotalDisplayRecords"] = count($metadataIdArray);	
		$highLevelObj["sEcho"] = $sEcho;
		//do the sql with limit and offset
		$metadataList = implode(",", $metadataIdArray);
		//initialize parameter arrays
		$v = array();
		$t = array();
		$vCount = array();
		$tCount = array();
		$numberOfVariables = 0;

//$e = new mb_exception("loadTableIncremental: " . $hierarchyLevel);

if ($hierarchyLevel == 'application') {
$sqlCount = <<<SQL

	SELECT count(metadata_id) FROM mb_metadata WHERE (mb_metadata.type = 'application' AND mb_metadata.metadata_id IN ($metadataList)

SQL;
} else {
$sqlCount = <<<SQL

	SELECT count(metadata_id) FROM mb_metadata WHERE (mb_metadata.type != 'application' AND mb_metadata.metadata_id IN ($metadataList)

SQL;
}

if ($hierarchyLevel == 'application') {
$sqlAll = <<<SQL

	SELECT metadata_id as metadata_id, metadata_id as id, uuid, title, lastchanged, f_get_coupled_resources(metadata_id), origin, searchable, export2csw FROM mb_metadata WHERE (mb_metadata.type = 'application' AND mb_metadata.metadata_id IN ($metadataList)

SQL;
} else {
$sqlAll = <<<SQL

	SELECT metadata_id as metadata_id, metadata_id as id, uuid, title, lastchanged, f_get_coupled_resources(metadata_id), origin, searchable, export2csw FROM mb_metadata WHERE (mb_metadata.type != 'application' AND mb_metadata.metadata_id IN ($metadataList)

SQL;
}
		if (isset($sSearch)) {
			$sSearch = "%".$sSearch."%";
			$numberOfVariables++;
			//$e = new mb_exception("search: ".$sSearch." - type: ".gettype($sSearch));
			$v[] = $sSearch;
			$vCount[] = $sSearch;
			$t[] = 's';
			$tCount[] = 's';
			$sqlCount .= "AND title LIKE $".$numberOfVariables;
			$sqlAll .= "AND title LIKE $".$numberOfVariables;
		}
		$sqlCount .= ")";
		$sqlAll .= ") ORDER BY lastchanged DESC";
		if (isset($iDisplayLength)) {
			$numberOfVariables++;
			//$e = new mb_exception("length: ".$iDisplayLength);
			$v[] = $iDisplayLength;
			$t[] = 'i';
			$sqlAll .= " LIMIT $".$numberOfVariables;
			
		}
		if (isset($iDisplayStart)) {
			$numberOfVariables++;
			//$e = new mb_exception("start: ".$iDisplayStart);
			$v[] = $iDisplayStart;
			$t[] = 'i';
			$sqlAll .= " OFFSET $".$numberOfVariables;
		}
		//do count after filtering
		$res = db_prep_query($sqlCount, $vCount, $tCount);
		$row = db_fetch_assoc($res);
		$highLevelObj["iTotalDisplayRecords"] = (integer)$row['count'];  
		$res = db_prep_query($sqlAll, $v, $t);
		$withOutFirstColumn = true;
		while ($row = db_fetch_row($res)) {
			$resultObj["aaData"][] = parseMetadataRow($row, $withOutFirstColumn);
		}
		if ($highLevelObj["iTotalDisplayRecords"] == 0) {
			$resultObj["aaData"] = array();
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setHighLevelAttributes($highLevelObj);
		$ajaxResponse->setSuccess(true);
		break;
	default: 
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("An unknown error occured."));
		break;
}
$ajaxResponse->send();
?>
