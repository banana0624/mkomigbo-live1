<?php
declare(strict_types=1);
/**
 * /awag/feedback.php — Community feedback handler
 * Accepts corrections and new community submissions
 * Stores to JSON files for review
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://mkomigbo.com');

$data_dir = __DIR__ . '/feedback/';
if (!is_dir($data_dir)) mkdir($data_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Try form post
    $input = $_POST;
}

$type     = trim($input['type']    ?? '');   // 'correction' or 'new_community'
$skin     = trim($input['skin']    ?? '');
$month    = (int)($input['month']  ?? 0);
$module   = trim($input['module']  ?? '');
$field    = trim($input['field']   ?? '');
$current  = trim($input['current'] ?? '');
$correct  = trim($input['correct'] ?? '');
$name     = trim($input['name']    ?? '');
$email    = trim($input['email']   ?? '');
$source   = trim($input['source']  ?? '');
$community = trim($input['community'] ?? '');
$region   = trim($input['region']  ?? '');
$language = trim($input['language'] ?? '');
$area     = trim($input['area']    ?? '');
$notes    = trim($input['notes']   ?? '');

// Basic validation
if (empty($type) || !in_array($type, ['correction','new_community','general'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

if (empty($correct) && empty($notes) && empty($community)) {
    http_response_code(400);
    echo json_encode(['error' => 'No content provided']);
    exit;
}

// Rate limit by IP: max 10 per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_file = $data_dir . 'rate_' . md5($ip) . '.json';
$rate = is_file($ip_file) ? json_decode(file_get_contents($ip_file), true) : ['count'=>0,'reset'=>time()+3600];
if (time() > $rate['reset']) { $rate = ['count'=>0,'reset'=>time()+3600]; }
if ($rate['count'] >= 10) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many submissions. Please try again later.']);
    exit;
}
$rate['count']++;
file_put_contents($ip_file, json_encode($rate));

// Build entry
$entry = [
    'id'        => uniqid('fb_', true),
    'type'      => $type,
    'timestamp' => date('c'),
    'ip_hash'   => md5($ip),
    'skin'      => $skin,
    'month'     => $month,
    'module'    => $module,
    'field'     => $field,
    'current'   => $current,
    'correct'   => $correct,
    'community' => $community,
    'region'    => $region,
    'language'  => $language,
    'area'      => $area,
    'notes'     => $notes,
    'name'      => $name,
    'email'     => $email,
    'source'    => $source,
    'status'    => 'pending',
];

// Save to daily file
$file = $data_dir . date('Y-m-d') . '.json';
$existing = is_file($file) ? json_decode(file_get_contents($file), true) : [];
$existing[] = $entry;
file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo json_encode([
    'success' => true,
    'message' => 'Thank you for your contribution. Our team will review and update the calendar.',
    'id'      => $entry['id'],
]);