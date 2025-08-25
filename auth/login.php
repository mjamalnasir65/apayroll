<?php
require_once '../includes/functions.php';
require_once '../includes/security.php';

if (isLoggedIn()) {
    redirect('');
}

// Check for session timeout message
$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $error = '';
        
        if ($email && $password) {
            $db = Database::getInstance();
            
            // Check login attempts
            $stmt = $db->query(
                "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                FROM login_attempts 
                WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                [$email]
            );
            $attempts = $stmt->fetch();
            
            if ($attempts['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $error = 'Too many login attempts. Please try again later.';
                logActivity('login_blocked', ['email' => $email, 'reason' => 'max_attempts']);
            } else {
                $stmt = $db->query(
                    "SELECT u.*, COALESCE(w.subscription_status, 'none') as sub_status 
                     FROM users u 
                     LEFT JOIN worker w ON u.user_id = w.user_id 
                     WHERE u.email = ? AND u.status = 'active'",
                    [$email]
                );
                
                if ($user = $stmt->fetch()) {
                    if (password_verify($password, $user['password_hash'])) {
                        // Clear login attempts
                        $db->query(
                            "DELETE FROM login_attempts WHERE email = ?",
                            [$email]
                        );

                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['sub_status'] = $user['sub_status'];
                        $_SESSION['LAST_ACTIVITY'] = time();
                        
                        // Update last login and log activity
                        $db->query(
                            "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?",
                            [$user['user_id']]
                        );
                        
                        logActivity('login_success', null, $user['user_id']);
                        
                        // Check for notifications
                        $notifications = getUnreadNotifications($user['user_id']);
                        if (!empty($notifications)) {
                            $_SESSION['has_notifications'] = true;
                        }
                        
                        redirect('');
                    } else {
                        // Log failed attempt
                        $db->query(
                            "INSERT INTO login_attempts (email, ip_address, user_agent) VALUES (?, ?, ?)",
                            [$email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
                        );
                        logActivity('login_failed', ['email' => $email, 'reason' => 'invalid_password']);
                        $error = 'Invalid credentials';
                    }
                } else {
                    $error = 'Invalid credentials';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="text-center">
                <a href="register.php" class="text-sm text-blue-600 hover:text-blue-500">
                    Need an account? Register here
                </a>
            </div>
        </div>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js')
                .then(registration => console.log('ServiceWorker registered'))
                .catch(error => console.log('ServiceWorker registration failed:', error));
        }
    </script>
</body>
</html>