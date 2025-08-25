<?php
require_once '../includes/functions.php';

if (isLoggedIn()) {
    redirect('');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyToken($_POST['csrf_token'] ?? '');
        
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!in_array($role, ['worker', 'employer'])) {
            throw new Exception('Invalid role selected');
        }
        
        if (!$email || !$password) {
            throw new Exception('All fields are required');
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Check if email exists
        $stmt = $db->query("SELECT user_id FROM users WHERE email = ?", [$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already registered');
        }
        
        // Create user account
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->query(
            "INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, ?, 'active')",
            [$email, $passwordHash, $role]
        );
        
        $userId = $conn->lastInsertId();
        
        // Create role-specific profile
        if ($role === 'worker') {
            $db->query(
                "INSERT INTO worker (user_id, full_name, passport_no, nationality, subscription_status) 
                 VALUES (?, 'Pending', 'Pending', 'Pending', 'pending')",
                [$userId]
            );
        } else {
            $db->query(
                "INSERT INTO employer (user_id, company_name, employer_roc, address, sector) 
                 VALUES (?, 'Pending', 'Pending', 'Pending', 'construction')",
                [$userId]
            );
        }
        
        $conn->commit();
        $success = 'Registration successful! Please login to complete your profile.';
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
                <p class="text-gray-600 mt-2">Register as a worker or employer</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">
                        <a href="login.php" class="font-medium text-green-600 hover:text-green-500">
                            Click here to login
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Type</label>
                        <div class="mt-2 space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="role" value="worker" required class="text-blue-600">
                                <span class="ml-2">Worker</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="role" value="employer" required class="text-blue-600">
                                <span class="ml-2">Employer</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Register
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center">
                <a href="login.php" class="text-sm text-blue-600 hover:text-blue-500">
                    Already have an account? Sign in
                </a>
            </div>
        </div>
    </div>
</body>
</html>
