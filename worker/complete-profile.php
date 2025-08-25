<?php
require_once '../includes/functions.php';
checkRole('worker');

$db = Database::getInstance();
$error = '';
$success = '';

// Get worker details
$stmt = $db->query(
    "SELECT w.*, u.email FROM worker w 
     JOIN users u ON w.user_id = u.user_id 
     WHERE w.user_id = ?", 
    [$_SESSION['user_id']]
);
$worker = $stmt->fetch();

// If profile is already completed, redirect to dashboard
if ($worker['full_name'] !== 'Pending') {
    redirect('worker/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyToken($_POST['csrf_token'] ?? '');
        
        // Validate inputs
        $requiredFields = [
            'full_name', 'passport_no', 'nationality', 'dob', 'gender',
            'mobile_number', 'address', 'wallet_id', 'wallet_brand',
            'employer_name', 'employer_roc', 'sector', 'contract_start',
            'monthly_salary'
        ];
        
        $data = [];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required");
            }
            $data[$field] = filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING);
        }
        
        // Validate file uploads
        $requiredFiles = ['passport', 'permit', 'photo', 'contract'];
        $uploadedFiles = [];
        
        foreach ($requiredFiles as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("All documents must be uploaded");
            }
            
            $uploadedFile = $_FILES[$file];
            $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Invalid file type for {$file}");
            }
            
            // Generate unique filename
            $newFilename = uniqid() . '.' . $fileExt;
            $uploadPath = UPLOAD_PATH . $newFilename;
            
            // Move file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to upload {$file}");
            }
            
            $uploadedFiles[$file] = '/worker/uploads/' . $newFilename;
        }
        
        // Update worker profile
        $stmt = $db->query(
            "UPDATE worker SET 
                full_name = ?,
                passport_no = ?,
                nationality = ?,
                dob = ?,
                gender = ?,
                mobile_number = ?,
                address = ?,
                wallet_id = ?,
                wallet_brand = ?,
                employer_name = ?,
                employer_roc = ?,
                sector = ?,
                contract_start = ?,
                monthly_salary = ?,
                copy_passport = ?,
                copy_permit = ?,
                photo = ?,
                copy_contract = ?
             WHERE user_id = ?",
            [
                $data['full_name'],
                $data['passport_no'],
                $data['nationality'],
                $data['dob'],
                $data['gender'],
                $data['mobile_number'],
                $data['address'],
                $data['wallet_id'],
                $data['wallet_brand'],
                $data['employer_name'],
                $data['employer_roc'],
                $data['sector'],
                $data['contract_start'],
                $data['monthly_salary'],
                $uploadedFiles['passport'],
                $uploadedFiles['permit'],
                $uploadedFiles['photo'],
                $uploadedFiles['contract'],
                $_SESSION['user_id']
            ]
        );
        
        redirect('worker/dashboard.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-900">Complete Your Profile</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Please provide your details to complete registration
                </p>
            </div>
        </div>

        <div class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                    <!-- Personal Information -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="full_name" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Passport Number</label>
                                <input type="text" name="passport_no" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nationality</label>
                                <input type="text" name="nationality" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                <input type="date" name="dob" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender</label>
                                <select name="gender" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
                                <input type="tel" name="mobile_number" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" rows="3" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <!-- Wallet Information -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Wallet Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Wallet ID</label>
                                <input type="text" name="wallet_id" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Wallet Brand</label>
                                <select name="wallet_brand" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Wallet</option>
                                    <option value="m1pay">M1Pay</option>
                                    <option value="finexus">Finexus</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Information -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Employment Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employer Name</label>
                                <input type="text" name="employer_name" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employer ROC</label>
                                <input type="text" name="employer_roc" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sector</label>
                                <select name="sector" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Sector</option>
                                    <option value="construction">Construction</option>
                                    <option value="manufacturing">Manufacturing</option>
                                    <option value="services">Services</option>
                                    <option value="plantation">Plantation</option>
                                    <option value="agriculture">Agriculture</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contract Start Date</label>
                                <input type="date" name="contract_start" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Monthly Salary (RM)</label>
                                <input type="number" name="monthly_salary" step="0.01" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Document Upload -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Required Documents</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Passport Copy</label>
                                <input type="file" name="passport" accept=".jpg,.jpeg,.png,.pdf" required
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Work Permit</label>
                                <input type="file" name="permit" accept=".jpg,.jpeg,.png,.pdf" required
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Photo</label>
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png" required
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employment Contract</label>
                                <input type="file" name="contract" accept=".pdf" required
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Complete Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }
    </script>
</body>
</html>
