<?php
/**
 * User Authentication Middleware
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        redirect('/login');
    }

    // Verify user is active in DB
    $user = current_user();
    if (!$user || $user['status'] !== 'active') {
        logout_user();
        set_flash('error', 'Your account has been deactivated. Please contact support.');
        redirect('/login');
    }
}

function require_guest(): void {
    if (is_logged_in()) {
        redirect('/dashboard');
    }
}

function current_user(): ?array {
    static $user = null;

    if ($user !== null) {
        return $user;
    }

    if (!is_logged_in()) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.username, u.email, u.mobile, u.status, u.created_at, u.last_login_at,
               COALESCE(w.balance, 0.00) as balance
        FROM users u
        LEFT JOIN wallets w ON w.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function logout_user(): void {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_username']);
    unset($_SESSION['user_name']);
    // Do not destroy entire session if admin is logged in simultaneously in different tab, but clear user credentials
}
