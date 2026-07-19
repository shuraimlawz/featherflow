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

$email = '';
$password = '';

// Support JSON input (for API fetch calls) as well as regular POST
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
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->prepare('SELECT UserID, Name, PasswordHash, Role, Phone, Address FROM Users WHERE Email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['PasswordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password.']);
        exit;
    }
    
    // Set sessions
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $user['Name'];
    $_SESSION['user_role'] = $user['Role'];
    $_SESSION['user_phone'] = $user['Phone'];
    $_SESSION['user_address'] = $user['Address'];
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['UserID'],
            'email' => $email,
            'name' => $user['Name'],
            'role' => $user['Role']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage()]);
}
