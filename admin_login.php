<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as Admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'backend/db.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT UserID, Name, PasswordHash, Role, Phone, Address FROM Users WHERE Email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['PasswordHash'])) {
                if ($user['Role'] === 'Admin') {
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $user['Name'];
                    $_SESSION['user_role'] = $user['Role'];
                    $_SESSION['user_phone'] = $user['Phone'];
                    $_SESSION['user_address'] = $user['Address'];
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Access Denied: Customer accounts cannot access the administrative portal.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = "Admin Login - FeatherFlow";
include 'header.php';
?>

    <main class="container">
        <div class="form-box">
            <h2 style="color: var(--primary-color); margin-bottom: 0.5rem; text-align: center;">Admin Control Panel</h2>
            <p style="color: #718096; text-align: center; margin-bottom: 2rem;">Authorized administrator credentials required for entry</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert-box alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="admin_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Admin Email Address</label>
                    <input type="email" id="email" name="email" placeholder="admin@featherflow.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Security Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn" style="width: 100%; font-size: 1.05rem;">Access Operational Dashboard</button>
            </form>
        </div>
    </main>

<?php
include 'footer.php';
?>
