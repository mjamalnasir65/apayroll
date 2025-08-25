<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'apayroll');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'A-Payroll');
define('APP_URL', 'http://apayroll/');
define('APP_VERSION', '1.0.0');
define('APP_EMAIL', 'noreply@apayroll.com');

// Security Constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('SESSION_LIFETIME', 86400); // 24 hours

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('RECEIPT_PATH', UPLOAD_PATH . 'receipts/');
define('DOCUMENT_PATH', UPLOAD_PATH . 'documents/');
define('LOG_PATH', __DIR__ . '/../logs/');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'error.log');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Upload Configuration
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
define('UPLOAD_PATH', __DIR__ . '/../worker/uploads/');
define('API_PATH', __DIR__ . '/../api/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting - change to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
