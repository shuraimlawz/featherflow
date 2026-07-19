<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'FeatherFlow - Poultry Management & E-Commerce'; ?></title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Global auth state for frontend scripts
        window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
</head>
<body>

    <header>
        <div class="nav-container">
            <div class="logo">
                <h1>Feather<span>Flow</span><?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') ? ' Admin' : ''; ?></h1>
            </div>
            <nav>
                <ul id="nav-links">
                    <li><a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Welcome Dashboard</a></li>
                    <li><a href="home.php" class="<?php echo ($current_page === 'home.php') ? 'active' : ''; ?>">Shop Front</a></li>
                    <li><a href="contact.php" class="<?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>">Contact Us</a></li>
                    
                    <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin'): ?>
                        <li><a href="admin_login.php" class="<?php echo ($current_page === 'admin_login.php') ? 'active' : ''; ?>" style="border: 1px solid var(--secondary-color); padding: 0.3rem 0.75rem; border-radius: 4px; color: var(--secondary-color) !important;">Admin Login</a></li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><span style="color: #cbd5e0; font-weight: 500; font-size: 0.95rem; border-left: 1px solid var(--border-color); padding-left: 1rem;">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></li>
                        <li><a href="backend/logout.php" style="color: var(--secondary-color);">Logout</a></li>
                    <?php else: ?>
                        <li><a href="#" id="login-nav-btn" onclick="openLoginModal(event)">Login / Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Customer Login & Registration Popup Modal -->
    <div id="auth-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeLoginModal()">&times;</span>
            
            <div class="auth-tabs">
                <button id="tab-login" class="tab-btn active" onclick="switchAuthTab('login')">Customer Login</button>
                <button id="tab-register" class="tab-btn" onclick="switchAuthTab('register')">Register Account</button>
            </div>
            
            <div id="auth-alert" class="alert-box" style="display: none;"></div>

            <!-- Login Form -->
            <form id="login-form" onsubmit="handleAuthSubmit(event, 'login')">
                <div class="form-group">
                    <label for="login-email">Email Address</label>
                    <input type="email" id="login-email" placeholder="customer@featherflow.com" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Log In</button>
                <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #718096;">
                    Are you an administrator? <a href="admin_login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Admin Login Portal</a>
                </p>
            </form>

            <!-- Registration Form -->
            <form id="register-form" onsubmit="handleAuthSubmit(event, 'register')" style="display: none;">
                <div class="form-group">
                    <label for="register-name">Full Name</label>
                    <input type="text" id="register-name" placeholder="e.g. Jane Smith" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email Address</label>
                    <input type="email" id="register-email" placeholder="e.g. jane@example.com" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" placeholder="Minimum 6 characters" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone Number</label>
                    <input type="text" id="register-phone" placeholder="e.g. +1234567890">
                </div>
                <div class="form-group">
                    <label for="register-address">Delivery Address</label>
                    <textarea id="register-address" rows="2" placeholder="Enter default delivery address"></textarea>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Create Account</button>
            </form>
        </div>
    </div>
