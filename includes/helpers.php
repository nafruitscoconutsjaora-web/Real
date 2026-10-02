<?php
/**
 * Global Helpers & Security Utilities
 * NexusGaming Platform
 */

declare(strict_types=1);

/**
 * Check if the application is already installed and locked
 */
function is_app_installed(): bool {
    return file_exists(__DIR__ . '/../config/installed.lock');
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Safe session startup
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Escape HTML output securely against XSS
 */
function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF token input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(get_csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verify_csrf_token(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Set a session flash message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear session flash messages
 */
function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages HTML
 */
function render_flash(): string {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="space-y-3 mb-6">';
    foreach ($messages as $msg) {
        $type = $msg['type'];
        $text = e($msg['message']);

        if ($type === 'success') {
            $border = 'border-emerald-500/40 bg-emerald-950/40 text-emerald-200';
            $icon = '<svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        } elseif ($type === 'error') {
            $border = 'border-rose-500/40 bg-rose-950/40 text-rose-200';
            $icon = '<svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        } elseif ($type === 'warning') {
            $border = 'border-amber-500/40 bg-amber-950/40 text-amber-200';
            $icon = '<svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        } else {
            $border = 'border-blue-500/40 bg-blue-950/40 text-blue-200';
            $icon = '<svg class="w-5 h-5 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }

        $html .= sprintf(
            '<div class="flex items-center gap-3 p-4 rounded-xl border text-sm font-medium %s">%s<div>%s</div></div>',
            $border,
            $icon,
            $text
        );
    }
    $html .= '</div>';
    return $html;
}

/**
 * Fetch dynamic setting from MySQL
 */
function get_setting(string $key, string $default = ''): string {
    static $settings_cache = null;

    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $db = get_db();
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // DB might be setting up
        }
    }

    return $settings_cache[$key] ?? $default;
}

/**
 * Update or insert dynamic setting
 */
function set_setting(string $key, string $value): void {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
    $stmt->execute([$key, $value]);
}

/**
 * Currency formatter
 */
function format_money(float|string $amount): string {
    $sym = get_setting('currency_symbol', DEFAULT_CURRENCY);
    return $sym . number_format((float)$amount, 2);
}

/**
 * Date formatter
 */
function format_date(?string $datetime, bool $show_time = true): string {
    if (!$datetime) {
        return '—';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '—';
    }
    return $show_time ? date('M j, Y h:i A', $timestamp) : date('M j, Y', $timestamp);
}

/**
 * Generate unique transaction reference ID
 */
function generate_transaction_id(): string {
    return 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
}

/**
 * Generate unique support ticket number
 */
function generate_ticket_number(): string {
    return 'TKT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Redirect utility
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
