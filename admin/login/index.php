<?php
session_start();

// Include database configuration
require_once '../../config/config.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../dashboard/');
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Sanitize username to prevent SQL injection
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        
        // Check if admin exists but is suspended
        $admin_status = checkAdminStatus($username);
        if ($admin_status === 'suspended') {
            // Log failed login attempt for suspended admin
            logLoginAttempt($username, false, $_SERVER['REMOTE_ADDR'] ?? '');
            $error_message = 'Your account has been suspended. Please contact the administrator.';
        } else {
            // Attempt to authenticate user and get user data
            $user_data = authenticateUser($username, $password);
            if ($user_data) {
                // Regenerate session ID to prevent session fixation
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_login_time'] = time();
                $_SESSION['admin_id'] = $user_data['id'];
                
                // Log successful login
                logLoginAttempt($username, true, $_SERVER['REMOTE_ADDR'] ?? '', $user_data['id']);
                
                // Redirect to dashboard
                header('Location: ../dashboard/');
                exit();
            } else {
                // Log failed login
                logLoginAttempt($username, false, $_SERVER['REMOTE_ADDR'] ?? '');
                
                $error_message = 'Invalid username or password.';
            }
        }
    }
}

/**
 * Check admin status
 * @param string $username
 * @return string|false Returns status ('active', 'suspended') or false if not found
 */
function checkAdminStatus($username) {
    try {
        $mysqli = getMysqliConnection();
        
        // Check if admin exists and get status
        $stmt = $mysqli->prepare("SELECT status FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user['status'];
        }
        
        $stmt->close();
        return false;
        
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Authenticate user against database
 * @param string $username
 * @param string $password
 * @return array|false Returns user data array on success, false on failure
 */
function authenticateUser($username, $password) {
    try {
        $mysqli = getMysqliConnection();
        
        // Prepare statement to prevent SQL injection - only allow active admins
        $stmt = $mysqli->prepare("SELECT id, username, password, status FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password using password_verify (assuming passwords are hashed with password_hash)
            if (password_verify($password, $user['password'])) {
                $stmt->close();
                return $user; // Return user data instead of just true
            }
        }
        
        $stmt->close();
        return false;
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log login attempts
 * @param string $username
 * @param bool $success
 * @param string $ip_address
 */
function logLoginAttempt($username, $success, $ip_address, $admin_id = 0) {
    try {
        $mysqli = getMysqliConnection();
        $stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        
        // Use provided admin_id or get it from database if not provided
        if ($success && $admin_id == 0) {
            // Get admin ID for successful login
            $user_stmt = $mysqli->prepare("SELECT id FROM admin_users WHERE username = ?");
            $user_stmt->bind_param("s", $username);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result->num_rows === 1) {
                $admin_id = $user_result->fetch_assoc()['id'];
            }
            $user_stmt->close();
        }
        
        $action = $success ? 'Login successful' : 'Login failed';
        $details = "Username: $username, IP: $ip_address";
        
        $stmt->bind_param("iss", $admin_id, $action, $details);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error logging login attempt: " . $e->getMessage());
    }
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Big Deal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Big Deal brand theme (black, white, red) */
            --primary-color: #000000; /* black */
            --primary-dark: #111111;
            --secondary-color: #e31b23; /* logo red */
            --accent-color: #e31b23;
            --success-color: #12b981;
            --danger-color: #e31b23;
            --warning-color: #f59e0b;
            --dark-color: #0b0b0b;
            --light-color: #ffffff;
            --border-color: #e5e7eb;
            --text-primary: #0f172a; /* slate-900 */
            --text-secondary: #6b7280; /* slate-500 */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.06);
            --shadow-md: 0 6px 16px rgb(0 0 0 / 0.08);
            --shadow-lg: 0 12px 24px rgb(0 0 0 / 0.12);
            --shadow-xl: 0 20px 40px rgb(0 0 0 / 0.14);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(1200px 600px at -20% -10%, #fafafa 0%, #ffffff 60%),
                radial-gradient(900px 500px at 120% 120%, #f6f7f9 10%, #ffffff 70%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Minimal brand background stripes */
        .bg-shapes {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(135deg, rgba(227,27,35,0.06) 0 25%, transparent 25% 50%, rgba(0,0,0,0.04) 50% 75%, transparent 75% 100%);
            background-size: 28px 28px;
            mask-image: radial-gradient(closest-side, #000 60%, transparent 100%);
        }

        .login-container {
            background: var(--light-color);
            border-radius: 18px;
            box-shadow: var(--shadow-xl);
            padding: 1.5rem;
            width: 100%;
            max-width: 380px;
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-color);
            transform: translateY(0);
            transition: box-shadow .25s ease, transform .25s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 18px 18px 0 0;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-img {
            width: 110px;
            height: auto;
            display: block;
            margin: 0 auto 0.75rem;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            object-fit: contain;
            background: #fff;
            padding: 6px;
        }

        .logo-title {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .logo-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 1.1rem;
            position: relative;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
            background: #fafafa;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(227, 27, 35, 0.12);
            transform: translateY(-1px);
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.1rem;
            transition: color .2s ease, transform .2s ease;
            z-index: 2;
        }

        .form-control:focus ~ .input-icon {
            color: var(--secondary-color);
            transform: translateY(-50%) scale(1.08);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color .2s ease, background .2s ease, transform .2s ease;
            padding: 0.5rem;
            border-radius: 50%;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--text-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 0.95rem 1rem;
            background: var(--primary-color);
            color: #fff;
            border: 1px solid var(--primary-dark);
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: .2px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.14), transparent);
            transform: translateX(-100%);
            transition: transform .45s ease;
        }

        .btn-login:hover::before { transform: translateX(100%); }

        .btn-login:hover { background: var(--secondary-color); border-color: var(--secondary-color); box-shadow: var(--shadow-lg); transform: translateY(-2px); }

        .btn-login:active { transform: translateY(0); }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            border: none;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger { background: #fef2f2; color: var(--danger-color); }

        .alert-success { background: #f0fdf4; color: var(--success-color); }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .forgot-password a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .security-info {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
        }

        .security-info i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .security-info span {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Loading animation */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .logo-title {
                font-size: 1.75rem;
            }
            
            .form-control {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }
            
            .input-icon {
                left: 0.875rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="logo-section">
            <img src="../../assets/logo.jpg" alt="Big Deal" class="logo-img" />
            <h1 class="logo-title">Admin Panel</h1>
            <p class="logo-subtitle">Big Deal Management System</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required 
                           autocomplete="username" 
                           placeholder="Enter your username"
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password" 
                           placeholder="Enter your password">
                    <button type="button" 
                            class="password-toggle" 
                            onclick="togglePassword()" 
                            title="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="forgot-password">
            <a href="#" onclick="showForgotPassword()">Forgot Password?</a>
        </div>

        
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleBtn.className = 'fas fa-eye';
            }
        }

        function showForgotPassword() {
            alert('For security reasons, please contact your system administrator to reset your password.');
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            // Show loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });

        // Auto-hide alerts after 6 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 6000);
            });

            // Add input focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add ripple effect to login button
            const loginBtn = document.getElementById('loginBtn');
            
            loginBtn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>
</body>
</html>
