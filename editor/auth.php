<?php
// -----------------------------------------------
// auth.php — misfortunes editor login
// -----------------------------------------------
// Password lives in config.php which is never
// committed to GitHub.
// -----------------------------------------------

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://misfortunes.net');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$password = isset($body['password']) ? $body['password'] : '';

if (!hash_equals(EDITOR_PASSWORD, $password)) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect password']);
    exit;
}

// Generate a secure random token
$token   = bin2hex(random_bytes(32));
$expires = time() + TOKEN_EXPIRY;

// Store token in a temp file next to this script
$tokenData = json_encode(['token' => $token, 'expires' => $expires]);
file_put_contents(__DIR__ . '/.token', $tokenData, LOCK_EX);

echo json_encode(['token' => $token]);