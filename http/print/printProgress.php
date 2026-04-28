<?php
/**
 * Print progress polling endpoint.
 * Returns the current progress state for a given print job token.
 *
 * GET  ?token=<token>          → returns { step, stepLabel, percent, done, error }
 * POST ?token=<token>&...      → internal use by printFactory.php only (loopback)
 */
require_once dirname(__FILE__) . "/../php/mb_validateSession.php";
if (session_status() === PHP_SESSION_ACTIVE) {
     session_write_close();
}
// Validate token: alphanumeric only, max 64 chars
$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
if (!preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $token)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'invalid token'));
    exit;
}

$progressFile = TMPDIR . '/print_progress_' . $token . '.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Frontend polling request
    header('Content-Type: application/json');
    if (file_exists($progressFile)) {
        $data = @file_get_contents($progressFile);
        if ($data !== false) {
            $decoded = json_decode($data, true);
            // Clean up the file once the job is complete or errored
            if (!empty($decoded['done']) || !empty($decoded['error'])) {
                @unlink($progressFile);
            }
            echo $data;
        } else {
            echo json_encode(array('step' => 0, 'stepLabel' => 'Wird gestartet...', 'percent' => 0, 'done' => false, 'error' => false));
        }
    } else {
        echo json_encode(array('step' => 0, 'stepLabel' => 'Wird gestartet...', 'percent' => 0, 'done' => false, 'error' => false));
    }
    exit;
}

