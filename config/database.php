<?php
/**
 * Database Configuration & Connection (MySQL via PDO)
 * NexusGaming Platform
 */

declare(strict_types=1);

if (file_exists(__DIR__ . '/db_credentials.php')) {
    require_once __DIR__ . '/db_credentials.php';
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'game_platform');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'game_user');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'GamePass123!#');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt root connection fallback if user was just created
            try {
                $pdo = new PDO($dsn, 'root', '', $options);
            } catch (PDOException $e2) {
                error_log('Database connection error: ' . $e->getMessage());
                die('<div style="background:#07090e;color:#ef4444;padding:2rem;font-family:sans-serif;text-align:center;">
                    <h2>Database Connection Error</h2>
                    <p style="color:#94a3b8;">Unable to connect to MySQL database. Please ensure MariaDB/MySQL is running.</p>
                </div>');
            }
        }
    }

    return $pdo;
}
