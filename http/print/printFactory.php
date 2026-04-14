<?php
require_once dirname(__FILE__) . "/../php/mb_validateSession.php";
require_once dirname(__FILE__) . "/classes/factoryClasses.php";

// Allow long-running print jobs (many WMS layers) to complete without PHP killing the process.
// 3 minutes is sufficient for even very large layer stacks while still bounding resource use.
set_time_limit(180);

/**
 * Internal flag: marks that featureInfo pages are currently being rendered.
 * Controlled only by PHP code — never by user input — to prevent external manipulation.
 */
function pfi_is_rendering_featureinfo($set = null) {
    static $flag = false;
    if ($set !== null) {
        $flag = (bool)$set;
    }
    return $flag;
}

/**
 * Write a progress state to the temporary progress file for this print job.
 */
function pfi_write_progress($token, $step, $stepLabel, $percent, $done = false, $error = false) {
    if (empty($token)) return;

    // Track elapsed time since first call; append warning when job is taking too long
    static $startTime = null;
    if ($startTime === null) {
        $startTime = microtime(true);
    }
    $elapsed = microtime(true) - $startTime;
    if (!$done && !$error && $elapsed > 45) {
        $minutes = (int)($elapsed / 60);
        $seconds = (int)($elapsed % 60);
        $timeStr = $minutes > 0 ? $minutes . ' min ' . $seconds . ' s' : $seconds . ' s';
        $stepLabel .= ' – dauert länger als gewöhnlich (' . $timeStr . ')...';
    }

    $progressFile = TMPDIR . '/print_progress_' . $token . '.json';
    $progressJson = json_encode(array(
        'step'      => $step,
        'stepLabel' => $stepLabel,
        'percent'   => $percent,
        'done'      => $done,
        'error'     => $error
    ));
    $tmpFile = $progressFile . '.tmp.' . getmypid() . '.' . uniqid('', true);
    $bytesWritten = file_put_contents($tmpFile, $progressJson, LOCK_EX);
    if ($bytesWritten === false) {
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
         }
         return;
    }
    rename($tmpFile, $progressFile);
}
$gui_id = Mapbender::session()->get("mb_user_gui");

// Release the session lock before starting the potentially long-running print job
// so concurrent requests (for example, progress polling) are not blocked.
session_write_close();

//select all element_ids from database, if $_REQUEST['e_id'] is in this list - use this e_id for getting php_var
$sql = "SELECT e_id FROM gui_element WHERE fkey_gui_id = $1";
$v = array($gui_id);
$t = array("s");
$res = db_prep_query($sql, $v, $t);
$e_id = false;
while ($row = db_fetch_array($res)){
	if ($row['e_id'] == $_REQUEST['e_id']) {
		$e_id = $row['e_id'];
		break;	
	}
}

if ($e_id != false) {
	include dirname(__FILE__) . "/../include/dyn_php.php";
}
$pf = new mbPdfFactory();
$confFile = basename($_REQUEST["printPDF_template"]);
if (!preg_match("/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9]+)$/", $confFile) || 
	!file_exists($_REQUEST["printPDF_template"])) {

	$errorMessage = _mb("Invalid configuration file");
	echo htmlentities($errorMessage, ENT_QUOTES, CHARSET);
	$e = new mb_exception($errorMessage);
	die;
}

$pdf = $pf->create($_REQUEST["printPDF_template"]);

new mb_notice("REQUEST:".json_encode($_REQUEST));

// Progress token — sent by the frontend to allow polling the progress state
$pfi_progress_token = (isset($_REQUEST['pfi_progress_token']) && preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $_REQUEST['pfi_progress_token']))
    ? $_REQUEST['pfi_progress_token']
    : '';

// Clear any stale progress file from a previous print
if ($pfi_progress_token) {
    $progressFile = TMPDIR . '/print_progress_' . $pfi_progress_token . '.json';
    if (file_exists($progressFile)) {
        @unlink($progressFile);
    }
}

pfi_write_progress($pfi_progress_token, 1, 'Kartendaten werden gesammelt...', 10);

// Capture any stray PHP output (notices/warnings) that would corrupt JSON response
ob_start();

//element vars of print
$pdf->unlinkFiles = isset($unlink) ? $unlink : false;
$pdf->logRequests = isset($logRequests) ? $logRequests : false;
$pdf->logType = isset($logType) ? $logType : "file";

if (isset($printLegend)){
    $pdf->printLegend = $printLegend;
}else{
    $pdf->printLegend = 'true';
}

if (isset($legendColumns)){
    $pdf->legendColumns = $legendColumns;
}else{
    $pdf->legendColumns = '1';
}

if (array_key_exists("featureInfo", $_REQUEST)) {
	$pdf->featureInfo = json_decode($_REQUEST["featureInfo"]);
}

pfi_write_progress($pfi_progress_token, 2, 'Kartenseiten werden gerendert...', 30);

try {
	$pdf->render();
	pfi_write_progress($pfi_progress_token, 3, 'PDF-Dateien werden zusammengeführt...', 70);
	$pdf->save();
	pfi_write_progress($pfi_progress_token, 4, 'Fertig', 100, true);
}
catch (Exception $e) {
	pfi_write_progress($pfi_progress_token, 0, 'Fehler beim Erstellen des PDFs.', 0, false, true);
	new mb_exception("printFactory error: " . $e->getMessage());
	$strayOutput = ob_get_clean();
	if ($strayOutput) {
		new mb_exception("printFactory stray output: " . $strayOutput);
	}
	die;
}

// Discard any stray output that was captured
$strayOutput = ob_get_clean();
if ($strayOutput) {
	new mb_exception("printFactory stray PHP output captured and discarded: " . $strayOutput);
}

if (isset($secureProtocol) && $secureProtocol == "true"){
    print $pdf->returnAbsoluteUrl(true);
}else{
    print $pdf->returnAbsoluteUrl();
}
?>
