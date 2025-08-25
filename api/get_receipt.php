<?php
require_once '../includes/functions.php';
checkRole('employer');
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Receipt ID is required']);
    exit;
}

$db = Database::getInstance();

// Get employer details
$stmt = $db->query(
    "SELECT employer_roc FROM employer WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$employer = $stmt->fetch();

// Get receipt details
$stmt = $db->query(
    "SELECT sl.*, w.employer_roc
     FROM salarylog sl
     JOIN worker w ON sl.worker_id = w.worker_id
     WHERE sl.log_id = ? AND w.employer_roc = ?",
    [$_GET['id'], $employer['employer_roc']]
);
$receipt = $stmt->fetch();

if (!$receipt) {
    echo json_encode(['error' => 'Receipt not found']);
    exit;
}

// Get the receipt file URL
$receipt_url = "../uploads/receipts/" . $receipt['receipt_file'];

if (!file_exists($receipt_url)) {
    echo json_encode(['error' => 'Receipt file not found']);
    exit;
}

echo json_encode([
    'receipt_url' => $receipt_url,
    'employer_note' => $receipt['employer_note'],
    'success' => true
]);
