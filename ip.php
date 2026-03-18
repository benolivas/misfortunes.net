<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://misfortunes.net');

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
    ?? $_SERVER['HTTP_X_REAL_IP'] 
    ?? $_SERVER['REMOTE_ADDR'] 
    ?? '';

// Clean up forwarded IPs (take first one)
if (str_contains($ip, ',')) {
    $ip = trim(explode(',', $ip)[0]);
}

// Fetch geolocation from ip-api (free, no key needed)
$geo = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,regionName,country,isp,query");
$data = $geo ? json_decode($geo, true) : [];

echo json_encode([
    'ip'       => $ip ?: 'unknown',
    'city'     => $data['city'] ?? '',
    'region'   => $data['regionName'] ?? '',
    'country'  => $data['country'] ?? '',
    'isp'      => $data['isp'] ?? '',
    'status'   => $data['status'] ?? 'fail',
]);
