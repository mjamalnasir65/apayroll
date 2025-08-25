<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'worker':
            redirect('worker/dashboard.php');
            break;
        case 'employer':
            redirect('employer/dashboard.php');
            break;
        case 'admin':
            redirect('admin/dashboard.php');
            break;
    }
}

redirect('auth/login.php');
