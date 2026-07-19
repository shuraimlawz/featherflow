<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');
    }
}

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name, email, and message are required.']);
    exit;
}

// Log inquiry to file
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

$log_file = __DIR__ . '/inquiries.json';
$current_data = [];

if (file_exists($log_file)) {
    $file_content = file_get_contents($log_file);
    $current_data = json_decode($file_content, true) ?: [];
}

$current_data[] = $log_entry;
file_put_contents($log_file, json_encode($current_data, JSON_PRETTY_PRINT));

echo json_encode([
    'success' => true,
    'message' => "Thank you, $name! Your inquiry has been successfully transmitted."
]);
