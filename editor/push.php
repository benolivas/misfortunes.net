<?php
// -----------------------------------------------
// push.php — misfortunes editor push to live
// -----------------------------------------------
// Receives the updated fortuneArray from the editor,
// validates the session token, and writes the file.
// -----------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://misfortunes.net');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$token = isset($body['token']) ? $body['token'] : '';
$array = isset($body['fortuneArray']) ? $body['fortuneArray'] : null;

// Validate token
$tokenFile = __DIR__ . '/.token';
if (!file_exists($tokenFile)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$tokenData = json_decode(file_get_contents($tokenFile), true);

if (
    !isset($tokenData['token']) ||
    !hash_equals($tokenData['token'], $token) ||
    time() > $tokenData['expires']
) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired session — please log in again']);
    exit;
}

// Validate the fortune array
if (!is_array($array) || count($array) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fortune array']);
    exit;
}

// Build the fortuneArray.js content
$lines = array_map(function($fortune) {
    // Escape backslashes first, then single quotes
    $escaped = str_replace('\\', '\\\\', $fortune);
    $escaped = str_replace("'", "\\'", $escaped);
    return "'" . $escaped . "'";
}, $array);

$output  = "var fortuneArray = [\n";
$output .= implode(",\n", $lines);
$output .= "\n];";

// Write to fortuneArray.js in the site root (one level up from editor/)
$target = dirname(__DIR__) . '/fortuneArray.js';

if (file_put_contents($target, $output, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file — check server permissions']);
    exit;
}

echo json_encode([
    'success' => true,
    'count'   => count($array),
    'message' => count($array) . ' fortunes pushed to live'
]);
