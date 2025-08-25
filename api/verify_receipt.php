<?php
require_once '../includes/functions.php';
checkRole('employer');
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['log_id']) || !isset($data['status'])) {
    echo json_encode(['error' => 'Log ID and status are required']);
    exit;
}

if (!in_array($data['status'], ['received', 'disputed'])) {
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Get employer details
$stmt = $db->query(
    "SELECT employer_roc FROM employer WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$employer = $stmt->fetch();

// Verify the receipt belongs to this employer
$stmt = $db->query(
    "SELECT sl.*, w.employer_roc, w.full_name, u.email
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     JOIN users u ON w.user_id = u.user_id
     WHERE sl.log_id = ? AND w.employer_roc = ?",
    [$data['log_id'], $employer['employer_roc']]
);
$receipt = $stmt->fetch();

if (!$receipt) {
    echo json_encode(['error' => 'Receipt not found']);
    exit;
}

if ($receipt['status'] !== 'pending') {
    echo json_encode(['error' => 'Receipt has already been verified']);
    exit;
}

// Update receipt status
try {
    $pdo->beginTransaction();

    $stmt = $db->query(
        "UPDATE salarylog 
         SET status = ?, employer_note = ?, verified_at = NOW() 
         WHERE log_id = ?",
        [
            $data['status'],
            $data['note'] ?? null,
            $data['log_id']
        ]
    );

    // Send notification to worker
    $title = "Salary Receipt " . ($data['status'] === 'received' ? 'Approved' : 'Disputed');
    $message = "Your salary receipt for " . date('F Y', strtotime($receipt['month'])) . 
               " has been " . ($data['status'] === 'received' ? 'approved' : 'disputed') .
               " by your employer.";
    
    if ($data['status'] === 'disputed' && !empty($data['note'])) {
        $message .= "\n\nReason: " . $data['note'];
    }

    $stmt = $db->query(
        "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)",
        [
            $receipt['user_id'],
            $title,
            $message,
            'receipt_verification'
        ]
    );

    // Send email notification if configured
    if (defined('APP_EMAIL')) {
        $to = $receipt['email'];
        $subject = $title;
        $headers = "From: " . APP_EMAIL . "\r\n" .
                   "Reply-To: " . APP_EMAIL . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        mail($to, $subject, $message, $headers);
    }

    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'An error occurred while verifying the receipt']);
    exit;
}
