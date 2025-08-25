<?php
require_once '../includes/functions.php';
checkRole('employer');

$db = Database::getInstance();
$error = '';
$success = '';

// Get employer details
$stmt = $db->query(
    "SELECT employer_roc FROM employer WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$employer = $stmt->fetch();

// Build query conditions
$conditions = ["w.employer_roc = ?"];
$params = [$employer['employer_roc']];

// Handle filters
$status = $_GET['status'] ?? '';
if ($status && in_array($status, ['pending', 'received', 'disputed'])) {
    $conditions[] = "sl.status = ?";
    $params[] = $status;
}

$month = $_GET['month'] ?? '';
if ($month) {
    $conditions[] = "sl.month = ?";
    $params[] = $month;
}

$search = $_GET['search'] ?? '';
if ($search) {
    $conditions[] = "(w.full_name LIKE ? OR w.passport_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get receipts with pagination
$page = max(1, $_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->query(
    "SELECT 
        sl.*,
        w.full_name,
        w.worker_id,
        w.passport_no,
        w.monthly_salary
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     $whereClause
     ORDER BY sl.submitted_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);
$receipts = $stmt->fetchAll();

// Get total count for pagination
$stmt = $db->query(
    "SELECT COUNT(*) as total
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     $whereClause",
    $params
);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Receipts - <?php echo APP_NAME; ?></title>
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
                    <span class="ml-2 text-lg font-semibold">Verify Receipts</span>
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
                           placeholder="Worker name or passport"
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
                    <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($receipts as $receipt): ?>
                <li id="receipt-<?php echo $receipt['log_id']; ?>">
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
                                    Expected: RM<?php echo number_format($receipt['monthly_salary'], 2); ?>
                                </p>
                                <p class="mt-2 text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                    Received: RM<?php echo number_format($receipt['expected_amount'], 2); ?>
                                </p>
                            </div>
                            <div class="mt-2 text-sm text-gray-500 sm:mt-0">
                                Uploaded: <?php echo date('d M Y', strtotime($receipt['submitted_at'])); ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php if ($receipt['status'] === 'pending'): ?>
                        <div class="mt-4 flex space-x-3">
                            <button onclick="viewReceipt(<?php echo $receipt['log_id']; ?>)"
                                    class="flex-1 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View Receipt
                            </button>
                            <button onclick="verifyReceipt(<?php echo $receipt['log_id']; ?>, 'received')"
                                    class="flex-1 bg-green-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Approve
                            </button>
                            <button onclick="verifyReceipt(<?php echo $receipt['log_id']; ?>, 'disputed')"
                                    class="flex-1 bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Dispute
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="mt-4">
                            <button onclick="viewReceipt(<?php echo $receipt['log_id']; ?>)"
                                    class="w-full bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View Receipt
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>

                <?php if (empty($receipts)): ?>
                <li>
                    <div class="px-4 py-4 sm:px-6 text-center text-gray-500">
                        No receipts found
                    </div>
                </li>
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
                    <h3 class="text-lg font-medium text-gray-900">Verify Receipt</h3>
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

        async function verifyReceipt(logId, status) {
            if (!confirm('Are you sure you want to ' + (status === 'received' ? 'approve' : 'dispute') + ' this receipt?')) {
                return;
            }

            const note = status === 'disputed' ? prompt('Please enter a reason for dispute:') : '';
            
            try {
                const response = await fetch('../api/verify_receipt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        log_id: logId,
                        status: status,
                        note: note
                    })
                });

                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Update UI
                const receiptElement = document.getElementById(`receipt-${logId}`);
                if (receiptElement) {
                    receiptElement.remove();
                }
                
            } catch (error) {
                alert('Error updating receipt status');
            }
        }
    </script>
</body>
</html>
