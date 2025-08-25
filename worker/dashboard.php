<?php
require_once '../includes/functions.php';
checkRole('worker');

$db = Database::getInstance();
$stmt = $db->query(
    "SELECT w.*, u.email FROM worker w 
     JOIN users u ON w.user_id = u.user_id 
     WHERE w.user_id = ?", 
    [$_SESSION['user_id']]
);
$worker = $stmt->fetch();

// Redirect to profile completion if details are pending
if ($worker['full_name'] === 'Pending') {
    redirect('worker/complete-profile.php');
}

// Get salary logs
$stmt = $db->query(
    "SELECT * FROM salarylog 
     WHERE worker_id = ? 
     ORDER BY month DESC 
     LIMIT 6", 
    [$worker['worker_id']]
);
$salaryLogs = $stmt->fetchAll();

// Get subscription status
$hasActiveSubscription = $worker['subscription_status'] === 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-lg font-semibold"><?php echo APP_NAME; ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="window.location.href='profile.php'" class="p-2">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                    <a href="../auth/logout.php" class="text-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Subscription Status -->
        <?php if (!$hasActiveSubscription): ?>
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Your subscription is not active. 
                        <a href="subscription.php" class="font-medium underline">Subscribe now</a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Contract Details</h3>
                <div class="mt-2 space-y-2">
                    <p class="text-sm text-gray-600">Employer: <?php echo htmlspecialchars($worker['employer_name']); ?></p>
                    <p class="text-sm text-gray-600">Monthly Salary: RM<?php echo number_format($worker['monthly_salary'], 2); ?></p>
                    <p class="text-sm text-gray-600">Contract Until: <?php echo date('d M Y', strtotime($worker['expiry_date'])); ?></p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Documents Status</h3>
                <div class="mt-2 space-y-2">
                    <p class="text-sm text-gray-600">
                        Passport: 
                        <span class="text-green-600">✓ Uploaded</span>
                    </p>
                    <p class="text-sm text-gray-600">
                        Work Permit: 
                        <span class="text-green-600">✓ Uploaded</span>
                    </p>
                    <p class="text-sm text-gray-600">
                        Contract: 
                        <span class="text-green-600">✓ Uploaded</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Recent Salary Logs -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Salary Logs</h3>
                    <a href="salary-history.php" class="text-sm text-blue-600 hover:text-blue-500">View all</a>
                </div>
                
                <?php if (empty($salaryLogs)): ?>
                    <p class="text-gray-500 text-sm">No salary records found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($salaryLogs as $log): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm">
                                        <?php echo date('M Y', strtotime($log['month'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        RM<?php echo number_format($log['expected_amount'], 2); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $log['status'] === 'received' ? 'bg-green-100 text-green-800' : 
                                                    ($log['status'] === 'disputed' ? 'bg-red-100 text-red-800' : 
                                                    'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($log['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 md:hidden">
            <div class="grid grid-cols-3 gap-4">
                <button onclick="window.location.href='upload-receipt.php'"
                        class="flex flex-col items-center justify-center p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="text-xs mt-1">Upload</span>
                </button>
                <button onclick="window.location.href='salary-history.php'"
                        class="flex flex-col items-center justify-center p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-xs mt-1">History</span>
                </button>
                <button onclick="window.location.href='help.php'"
                        class="flex flex-col items-center justify-center p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs mt-1">Help</span>
                </button>
            </div>
        </div>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }
    </script>
</body>
</html>
