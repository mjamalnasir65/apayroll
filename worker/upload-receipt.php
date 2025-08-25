<?php
require_once '../includes/functions.php';
checkRole('worker');

$db = Database::getInstance();
$error = '';
$success = '';

// Get worker details
$stmt = $db->query(
    "SELECT worker_id, monthly_salary FROM worker WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$worker = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyToken($_POST['csrf_token'] ?? '');
        
        $month = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_STRING);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        if (!$month || !$amount || !isset($_FILES['receipt'])) {
            throw new Exception('All fields are required');
        }

        // Validate month format and ensure it's not in the future
        $monthDate = new DateTime($month . '-01');
        $currentDate = new DateTime();
        if ($monthDate > $currentDate) {
            throw new Exception('Cannot upload receipt for future months');
        }

        // Check if receipt already exists for this month
        $stmt = $db->query(
            "SELECT log_id FROM salarylog WHERE worker_id = ? AND month = ?",
            [$worker['worker_id'], $month]
        );
        if ($stmt->fetch()) {
            throw new Exception('Receipt already uploaded for this month');
        }

        // Handle file upload
        $receipt = $_FILES['receipt'];
        if ($receipt['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file');
        }

        $fileExt = strtolower(pathinfo($receipt['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, PDF');
        }

        $newFilename = uniqid() . '.' . $fileExt;
        $uploadPath = UPLOAD_PATH . 'receipts/' . $newFilename;
        
        if (!is_dir(UPLOAD_PATH . 'receipts/')) {
            mkdir(UPLOAD_PATH . 'receipts/', 0755, true);
        }

        if (!move_uploaded_file($receipt['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload file');
        }

        // Create salary log
        $db->query(
            "INSERT INTO salarylog (worker_id, month, expected_amount, receipt_url, status) 
             VALUES (?, ?, ?, ?, 'pending')",
            [
                $worker['worker_id'],
                $month,
                $amount,
                '/worker/uploads/receipts/' . $newFilename
            ]
        );

        $success = 'Receipt uploaded successfully';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get last 3 months uploads
$stmt = $db->query(
    "SELECT month, expected_amount, status, submitted_at 
     FROM salarylog 
     WHERE worker_id = ? 
     ORDER BY month DESC 
     LIMIT 3",
    [$worker['worker_id']]
);
$recentUploads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Salary Receipt - <?php echo APP_NAME; ?></title>
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
                    <span class="ml-2 text-lg font-semibold">Upload Receipt</span>
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

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Month</label>
                        <input type="month" name="month" required max="<?php echo date('Y-m'); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Select the month for this salary receipt</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount Received (RM)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">RM</span>
                            </div>
                            <input type="number" name="amount" step="0.01" required
                                   value="<?php echo htmlspecialchars($worker['monthly_salary']); ?>"
                                   class="pl-12 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Receipt Image/PDF</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="receipt" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="receipt" name="receipt" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf" required>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Upload Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Uploads -->
        <?php if ($recentUploads): ?>
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Uploads</h3>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recentUploads as $upload): ?>
                    <li>
                        <div class="px-4 py-4 flex items-center sm:px-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-blue-600 truncate">
                                        <?php echo date('F Y', strtotime($upload['month'])); ?>
                                    </p>
                                    <div class="ml-2 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $upload['status'] === 'received' ? 'bg-green-100 text-green-800' : 
                                                    ($upload['status'] === 'disputed' ? 'bg-red-100 text-red-800' : 
                                                    'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($upload['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 flex justify-between">
                                    <div class="sm:flex">
                                        <p class="text-sm text-gray-500">
                                            RM<?php echo number_format($upload['expected_amount'], 2); ?>
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        <?php echo date('d M Y', strtotime($upload['submitted_at'])); ?>
                                    </p>
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

        // Preview selected file name
        document.getElementById('receipt').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = document.querySelector('label[for="receipt"]');
                label.innerHTML = `Selected: ${fileName}`;
            }
        });
    </script>
</body>
</html>
