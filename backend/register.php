<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$name = '';
$email = '';
$password = '';
$phone = '';
$address = '';

// Support JSON input (for API fetch calls) or standard POST
if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $phone = trim($input['phone'] ?? '');
        $address = trim($input['address'] ?? '');
    }
} else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
}

if ($name === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name, email, and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address format.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters long.']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'An account with this email address already exists.']);
        exit;
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $insertStmt = $pdo->prepare("
        INSERT INTO Users (Name, Email, PasswordHash, Phone, Address, Role)
        VALUES (?, ?, ?, ?, ?, 'Customer')
    ");
    $insertStmt->execute([$name, $email, $passwordHash, $phone, $address]);
    $newUserId = $pdo->lastInsertId();
    
    // Auto-login after registration
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'Customer';
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_address'] = $address;
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $newUserId,
            'email' => $email,
            'name' => $name,
            'role' => 'Customer'
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
