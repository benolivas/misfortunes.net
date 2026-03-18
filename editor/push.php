<?php
// -----------------------------------------------
// push.php -- misfortunes editor push to live
// -----------------------------------------------
// Receives the updated fortuneArray from the editor,
// validates the session token, and writes the file.
// Supports object format: {text:'...',added:'YYYY-MM-DD'}
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
    echo json_encode(['error' => 'Invalid or expired session -- please log in again']);
    exit;
}

// Validate the fortune array
if (!is_array($array) || count($array) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fortune array']);
    exit;
}

// Detect format: string array (legacy) or object array (new)
$firstItem  = $array[0];
$isOldFormat = is_string($firstItem);

// Build the fortuneArray.js content
$lines = array_map(function($fortune) use ($isOldFormat) {
    if ($isOldFormat) {
        // Legacy string format -- should not happen after migration, but handle gracefully
        $text    = $fortune;
        $added   = '2026-03-15';
    } else {
        $text    = isset($fortune['text'])  ? $fortune['text']  : '';
        $added   = isset($fortune['added']) ? $fortune['added'] : '2026-03-15';
        // Validate added is YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $added)) {
            $added = '2026-03-15';
        }
    }
    // Sanitize: collapse all whitespace/newlines to single space
    $text = preg_replace('/[\r\n]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    // Escape backslashes first, then single quotes
    $escapedText = str_replace('\\', '\\\\', $text);
    $escapedText = str_replace("'", "\\'", $escapedText);
    return "{text:'" . $escapedText . "',added:'" . $added . "'}";
}, $array);

$output  = "var fortuneArray = [\n";
$output .= implode(",\n", $lines);
$output .= "\n];";

// Write to fortuneArray.js in the site root (one level up from editor/)
$target = dirname(__DIR__) . '/fortuneArray.js';

if (file_put_contents($target, $output, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file -- check server permissions']);
    exit;
}

echo json_encode([
    'success' => true,
    'count'   => count($array),
    'message' => count($array) . ' fortunes pushed to live'
]);
