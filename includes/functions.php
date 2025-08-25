<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function checkRole($allowedRoles) {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . 'auth/login.php');
        exit;
    }
    
    $userRole = getUserRole();
    if (!in_array($userRole, (array)$allowedRoles)) {
        http_response_code(403);
        die('Unauthorized access');
    }
}

// Utility Functions
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

function redirect($path) {
    header('Location: ' . APP_URL . $path);
    exit;
}

// API Response Functions
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function errorResponse($message, $code = 400) {
    jsonResponse(['error' => $message], $code);
}
