<?php
require_once '../includes/functions.php';
checkRole('worker');

$db = Database::getInstance();
$error = '';
$success = '';

// Get worker subscription details
$stmt = $db->query(
    "SELECT w.*, u.email, u.created_at as joined_date FROM worker w 
     JOIN users u ON w.user_id = u.user_id 
     WHERE w.user_id = ?", 
    [$_SESSION['user_id']]
);
$worker = $stmt->fetch();

// Get subscription history
$stmt = $db->query(
    "SELECT * FROM subscriptionpayment 
     WHERE worker_id = ? 
     ORDER BY paid_at DESC 
     LIMIT 5", 
    [$worker['worker_id']]
);
$subscriptionHistory = $stmt->fetchAll();

// Handle subscription activation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyToken($_POST['csrf_token'] ?? '');
        
        if ($worker['subscription_status'] === 'active') {
            throw new Exception('You already have an active subscription');
        }

        $conn = $db->getConnection();
        $conn->beginTransaction();

        // Create subscription payment record
        $db->query(
            "INSERT INTO subscriptionpayment (worker_id, amount, payment_method, wallet_ref) 
             VALUES (?, 60.00, 'wallet', ?)",
            [
                $worker['worker_id'],
                'DEMO-' . time() // Demo reference number
            ]
        );

        // Update worker subscription status
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+1 year'));
        
        $db->query(
            "UPDATE worker 
             SET subscription_status = 'active',
                 subscription_expiry = ?
             WHERE worker_id = ?",
            [$endDate, $worker['worker_id']]
        );

        $conn->commit();
        $_SESSION['sub_status'] = 'active'; // Update session
        $success = 'Subscription activated successfully! Valid until ' . date('d M Y', strtotime($endDate));
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Calculate subscription status details
$status = $worker['subscription_status'];
$daysLeft = 0;
$isExpired = false;

if ($status === 'active' && $worker['subscription_expiry']) {
    $expiryDate = new DateTime($worker['subscription_expiry']);
    $today = new DateTime();
    $daysLeft = $today->diff($expiryDate)->days;
    $isExpired = $today > $expiryDate;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <span class="ml-2 text-lg font-semibold">Subscription</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Subscription Status Card -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Subscription Status</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        <?php echo $status === 'active' ? 'bg-green-100 text-green-800' : 
                                ($status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800'); ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </div>

                <?php if ($status === 'active' && !$isExpired): ?>
                    <div class="mt-4 p-4 bg-blue-50 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Active Subscription</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Days remaining: <?php echo $daysLeft; ?></p>
                                    <p>Expires: <?php echo date('d M Y', strtotime($worker['subscription_expiry'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <div class="rounded-md bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Subscription Required</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Subscribe to access all features including:</p>
                                        <ul class="list-disc list-inside mt-2">
                                            <li>Unlimited salary receipt uploads</li>
                                            <li>Salary history export</li>
                                            <li>Priority support</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Subscribe Now - RM60/year
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subscription History -->
        <?php if ($subscriptionHistory): ?>
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Payment History</h3>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($subscriptionHistory as $payment): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-blue-600 truncate">
                                    Ref: <?php echo htmlspecialchars($payment['wallet_ref']); ?>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        RM<?php echo number_format($payment['amount'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($payment['payment_method']); ?>
                                    </p>
                                </div>
                                <div class="mt-2 text-sm text-gray-500 sm:mt-0">
                                    <?php echo date('d M Y', strtotime($payment['paid_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }
    </script>
</body>
</html>
