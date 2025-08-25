<?php
require_once '../includes/functions.php';
checkRole('employer');

$db = Database::getInstance();

// Get employer details
$stmt = $db->query(
    "SELECT e.*, u.email FROM employer e 
     JOIN users u ON e.user_id = u.user_id 
     WHERE e.user_id = ?", 
    [$_SESSION['user_id']]
);
$employer = $stmt->fetch();

// Get quick stats
$stmt = $db->query(
    "SELECT 
        COUNT(*) as total_receipts,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'disputed' THEN 1 ELSE 0 END) as disputed_count
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     WHERE w.employer_roc = ?",
    [$employer['employer_roc']]
);
$stats = $stmt->fetch();

// Get recent receipts
$stmt = $db->query(
    "SELECT 
        sl.*,
        w.full_name,
        w.worker_id,
        w.passport_no
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     WHERE w.employer_roc = ?
     ORDER BY sl.submitted_at DESC
     LIMIT 5",
    [$employer['employer_roc']]
);
$recentReceipts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-lg font-semibold"><?php echo htmlspecialchars($employer['company_name']); ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                    <a href="../auth/logout.php" class="text-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Receipts -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Receipts</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_receipts'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Verification -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Verification</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['pending_count'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="verify-receipts.php?status=pending" class="font-medium text-yellow-700 hover:text-yellow-900">
                            View pending →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Approved -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['approved_count'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disputed -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Disputed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['disputed_count'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Receipts -->
        <div class="mt-8">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Recent Receipts</h2>
                <a href="verify-receipts.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    View all
                </a>
            </div>

            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recentReceipts as $receipt): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-blue-600 truncate">
                                    <?php echo htmlspecialchars($receipt['full_name']); ?> 
                                    (<?php echo htmlspecialchars($receipt['passport_no']); ?>)
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $receipt['status'] === 'received' ? 'bg-green-100 text-green-800' : 
                                                ($receipt['status'] === 'disputed' ? 'bg-red-100 text-red-800' : 
                                                'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($receipt['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="text-sm text-gray-500">
                                        Month: <?php echo date('F Y', strtotime($receipt['month'])); ?>
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                        Amount: RM<?php echo number_format($receipt['expected_amount'], 2); ?>
                                    </p>
                                </div>
                                <div class="mt-2 text-sm text-gray-500 sm:mt-0">
                                    Uploaded: <?php echo date('d M Y', strtotime($receipt['submitted_at'])); ?>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="verify-receipt.php?id=<?php echo $receipt['log_id']; ?>" 
                                   class="text-sm text-blue-600 hover:text-blue-500">
                                    Review Receipt →
                                </a>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>

                    <?php if (empty($recentReceipts)): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6 text-center text-gray-500">
                            No receipts found
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 md:hidden">
            <div class="grid grid-cols-3 gap-4">
                <a href="verify-receipts.php?status=pending"
                   class="flex flex-col items-center justify-center p-2 text-yellow-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs mt-1">Pending</span>
                </a>
                <a href="workers.php"
                   class="flex flex-col items-center justify-center p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-xs mt-1">Workers</span>
                </a>
                <a href="reports.php"
                   class="flex flex-col items-center justify-center p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs mt-1">Reports</span>
                </a>
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
