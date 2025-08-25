<?php
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';
require_once '../includes/subscription_check.php';
checkRole('worker');

$db = Database::getInstance();

// Get worker details
$stmt = $db->query(
    "SELECT worker_id, full_name FROM worker WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$worker = $stmt->fetch();

// Handle filters
$filters = [];
$params = [$worker['worker_id']];
$conditions = ["worker_id = ?"];

$status = $_GET['status'] ?? '';
if ($status && in_array($status, ['pending', 'received', 'disputed'])) {
    $conditions[] = "status = ?";
    $params[] = $status;
    $filters['status'] = $status;
}

$month = $_GET['month'] ?? '';
if ($month) {
    $conditions[] = "month = ?";
    $params[] = $month;
    $filters['month'] = $month;
}

$search = $_GET['search'] ?? '';
if ($search) {
    $conditions[] = "(receipt_file LIKE ? OR employer_note LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $filters['search'] = $search;
}

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count
$stmt = $db->query(
    "SELECT COUNT(*) as total FROM salarylog $whereClause",
    $params
);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

// Get receipts
$stmt = $db->query(
    "SELECT * FROM salarylog $whereClause ORDER BY submitted_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);
$receipts = $stmt->fetchAll();

// Get available years and months for filter
$stmt = $db->query(
    "SELECT DISTINCT month FROM salarylog WHERE worker_id = ? ORDER BY month DESC",
    [$worker['worker_id']]
);
$availableMonths = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary History - <?php echo APP_NAME; ?></title>
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
                    <span class="ml-2 text-lg font-semibold">Salary History</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by filename or note"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>Approved</option>
                        <option value="disputed" <?php echo $status === 'disputed' ? 'selected' : ''; ?>>Disputed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Month</label>
                    <select name="month" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Months</option>
                        <?php foreach ($availableMonths as $availableMonth): ?>
                        <option value="<?php echo $availableMonth; ?>" <?php echo $month === $availableMonth ? 'selected' : ''; ?>>
                            <?php echo date('F Y', strtotime($availableMonth)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit"
                            class="w-full md:w-auto px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Receipts List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($receipts)): ?>
                <li class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                              d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-1">No salary receipts found</p>
                    <p class="mt-1 text-sm">Upload a new receipt from your dashboard.</p>
                </li>
                <?php else: ?>
                <?php foreach ($receipts as $receipt): ?>
                <li>
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-blue-600 truncate">
                                Salary for <?php echo date('F Y', strtotime($receipt['month'])); ?>
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
                                    Amount: RM<?php echo number_format($receipt['expected_amount'], 2); ?>
                                </p>
                                <p class="mt-2 text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                    Uploaded: <?php echo date('d M Y', strtotime($receipt['submitted_at'])); ?>
                                </p>
                            </div>
                            <?php if ($receipt['verified_at']): ?>
                            <div class="mt-2 text-sm text-gray-500 sm:mt-0">
                                Verified: <?php echo date('d M Y', strtotime($receipt['verified_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($receipt['employer_note']): ?>
                        <div class="mt-2 text-sm text-gray-500">
                            <strong>Note:</strong> <?php echo htmlspecialchars($receipt['employer_note']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <button onclick="viewReceipt(<?php echo $receipt['log_id']; ?>)"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" 
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Receipt
                            </button>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing <span class="font-medium"><?php echo $offset + 1; ?></span>
                to <span class="font-medium"><?php echo min($offset + $perPage, $total); ?></span>
                of <span class="font-medium"><?php echo $total; ?></span> results
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&month=<?php echo urlencode($month); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-3 py-1 border rounded text-sm text-gray-700 hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&month=<?php echo urlencode($month); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-3 py-1 border rounded text-sm text-gray-700 hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">View Receipt</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }

        const modal = document.getElementById('modal');
        const modalContent = document.getElementById('modalContent');

        function closeModal() {
            modal.classList.add('hidden');
        }

        async function viewReceipt(logId) {
            try {
                const response = await fetch(`../api/get_receipt.php?id=${logId}`);
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }

                modalContent.innerHTML = `
                    <div class="space-y-4">
                        <img src="${data.receipt_url}" alt="Receipt" class="w-full">
                        ${data.employer_note ? `
                            <div class="mt-4 p-4 bg-gray-50 rounded-md">
                                <h4 class="text-sm font-medium text-gray-900">Employer Note:</h4>
                                <p class="mt-1 text-sm text-gray-500">${data.employer_note}</p>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                modal.classList.remove('hidden');
            } catch (error) {
                alert('Error loading receipt');
            }
        }
    </script>
</body>
</html>
