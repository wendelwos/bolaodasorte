<?php
/**
 * Bolão da Sorte v2.0
 * Complete Single-Entry Point Application
 * 
 * Features:
 * - MySQL Database
 * - User Registration & Approval System
 * - Public Event Listing
 * - Participation Requests
 * - Admin Management
 * - Dark Mode
 * - CSRF Protection
 * - Password Hashing (bcrypt)
 * 
 * Folder Structure:
 * /bolaodasorte
 *   /assets
 *     /css/styles.css
 *     /js/app.js
 *     /images/logo.png, favicon.png
 *   /includes
 *     config.php, controllers.php, header.php, footer.php
 *   /views
 *     auth.php, dashboard.php, admin.php, event.php, betting.php
 *   index.php
 */

// Load configuration and controllers
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/controllers.php';

// Load views
require_once __DIR__ . '/views/auth.php';
require_once __DIR__ . '/views/dashboard.php';
require_once __DIR__ . '/views/admin.php';
require_once __DIR__ . '/views/event.php';
require_once __DIR__ . '/views/betting.php';
require_once __DIR__ . '/views/profile.php';
require_once __DIR__ . '/views/payment.php';

// Get current user
$user = getCurrentUser();

// Start output
require_once __DIR__ . '/includes/header.php';

// Route to correct view
if (!$user) {
    // Not logged in
    switch ($action) {
        case 'register':
            viewRegister();
            break;
        case 'public_events':
            viewPublicEvents($pdo, null);
            break;
        case 'forgot_password':
            viewForgotPassword();
            break;
        case 'reset_password':
            viewResetPassword($pdo);
            break;
        case 'login':
        default:
            viewLogin();
            break;
    }
} else {
    // Logged in
    switch ($action) {
        case 'dashboard':
            viewDashboard($pdo, $user);
            break;
        case 'public_events':
            viewPublicEvents($pdo, $user);
            break;
        case 'new_event':
            if ($user['is_admin']) viewNewEvent();
            else redirect('?action=dashboard');
            break;
        case 'manage_users':
            if ($user['is_admin']) viewManageUsers($pdo);
            else redirect('?action=dashboard');
            break;
        case 'pending_users':
            if ($user['is_admin']) viewPendingUsers($pdo);
            else redirect('?action=dashboard');
            break;
        case 'manage_requests':
            if ($user['is_admin']) viewManageRequests($pdo);
            else redirect('?action=dashboard');
            break;
        case 'manage_events':
            if ($user['is_admin']) viewManageEvents($pdo);
            else redirect('?action=dashboard');
            break;
        case 'manage_event':
            if ($user['is_admin'] && isset($_GET['id'])) viewManageEvent($pdo, (int)$_GET['id']);
            else redirect('?action=dashboard');
            break;
        case 'view_event':
            if (isset($_GET['id'])) viewEvent($pdo, $user, (int)$_GET['id']);
            else redirect('?action=dashboard');
            break;
        case 'profile':
            viewProfile($user);
            break;
        case 'payment':
            if (isset($_GET['event_id'])) viewPayment($pdo, $user, (int)$_GET['event_id']);
            else redirect('?action=dashboard');
            break;
        case 'my_bets':
            viewMyBets($pdo, $user);
            break;
        case 'manage_payments':
            if ($user['is_admin']) viewManagePayments($pdo);
            else redirect('?action=dashboard');
            break;
        default:
            redirect('?action=dashboard');
            break;
    }
}

require_once __DIR__ . '/includes/footer.php';