<?php
require_once __DIR__ . '/config.php';

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function logActivity($action, $details = null, $userId = null) {
    $db = Database::getInstance();
    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    
    $db->query(
        "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?)",
        [
            $userId,
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]
    );
}

function checkSessionTimeout() {
    if (!isset($_SESSION['LAST_ACTIVITY'])) {
        $_SESSION['LAST_ACTIVITY'] = time();
        return;
    }

    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME) {
        session_destroy();
        redirect('auth/login.php?timeout=1');
    }

    $_SESSION['LAST_ACTIVITY'] = time();
}

function validateFile($file, $maxSize = null) {
    $maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
    $errors = [];

    if ($file['size'] > $maxSize) {
        $errors[] = 'File too large. Maximum size is ' . formatBytes($maxSize);
    }

    if (!in_array($file['type'], ALLOWED_FILE_TYPES)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', array_map('mime2ext', ALLOWED_FILE_TYPES));
    }

    return $errors;
}

function secureFileName($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return sprintf(
        '%s_%s.%s',
        bin2hex(random_bytes(8)),
        time(),
        $ext
    );
}

function formatBytes($bytes) {
    if ($bytes > 1024*1024) {
        return round($bytes/1024/1024, 1) . ' MB';
    }
    if ($bytes > 1024) {
        return round($bytes/1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function mime2ext($mime) {
    $map = [
        'image/jpeg' => 'JPG',
        'image/png' => 'PNG',
        'application/pdf' => 'PDF'
    ];
    return $map[$mime] ?? $mime;
}

function createNotification($userId, $title, $message, $type = 'general') {
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)",
        [$userId, $title, $message, $type]
    );
}

function getUnreadNotifications($userId) {
    $db = Database::getInstance();
    $stmt = $db->query(
        "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC",
        [$userId]
    );
    return $stmt->fetchAll();
}

function markNotificationRead($notificationId, $userId) {
    $db = Database::getInstance();
    $db->query(
        "UPDATE notifications SET is_read = 1 
         WHERE id = ? AND user_id = ?",
        [$notificationId, $userId]
    );
}

function cleanupOldFiles() {
    // Clean up temporary upload files older than 24 hours
    $tempFiles = glob(UPLOAD_PATH . 'temp/*');
    foreach ($tempFiles as $file) {
        if (filemtime($file) < time() - 86400) {
            unlink($file);
        }
    }
}

function initUploadDirectories() {
    $dirs = [
        UPLOAD_PATH,
        RECEIPT_PATH,
        DOCUMENT_PATH,
        UPLOAD_PATH . 'temp',
        LOG_PATH
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Initialize upload directories on script load
initUploadDirectories();

// Check session timeout on every request
checkSessionTimeout();
