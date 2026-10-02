<?php
/**
 * Admin Authentication Middleware
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Administrator authentication required.');
        redirect('/admin/login');
    }

    $admin = current_admin();
    if (!$admin || $admin['status'] !== 'active') {
        logout_admin();
        set_flash('error', 'Admin account is deactivated or invalid.');
        redirect('/admin/login');
    }
}

function require_admin_guest(): void {
    if (is_admin_logged_in()) {
        redirect('/admin');
    }
}

function current_admin(): ?array {
    static $admin = null;

    if ($admin !== null) {
        return $admin;
    }

    if (!is_admin_logged_in()) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT id, username, name, email, status, last_login_at, created_at FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;

    return $admin;
}

function logout_admin(): void {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
}
