<?php
/**
 * User Logout
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

set_flash('info', 'You have been securely logged out.');
redirect('/login');
