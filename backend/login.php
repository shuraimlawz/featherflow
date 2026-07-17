<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = null;
$password = null;

if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
    }
} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$host = 'localhost';
$db   = 'featherflow';
$user = 'root';
$pass = ''; // Update these credentials as needed.

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $mysqli->prepare('SELECT id, password_hash, name FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed']);
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$stmt->bind_result($id, $passwordHash, $name);
$stmt->fetch();

if (!password_verify($password, $passwordHash)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$_SESSION['user_id'] = $id;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $id,
        'email' => $email,
        'name' => $name,
    ],
]);

$stmt->close();
$mysqli->close();
