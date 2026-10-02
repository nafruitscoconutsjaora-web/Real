<?php
/**
 * Admin Logout
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

logout_admin();

set_flash('info', 'Admin session terminated.');
redirect('/admin/login');
