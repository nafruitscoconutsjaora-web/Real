<?php
/**
 * Browser-based Interactive Installer & Setup Wizard
 * NexusGaming Platform
 */

declare(strict_types=1);

// Prevent session collisions and start fresh session for installer
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}

$lockFile = __DIR__ . '/../config/installed.lock';
$isInstalled = file_exists($lockFile);

// Helper function to escape strings
function esc(?string $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------------------------------------------
// AJAX ENDPOINT: Test Database Connection (Step 3 Real Test)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_db') {
    header('Content-Type: application/json; charset=utf-8');

    if ($isInstalled) {
        echo json_encode([
            'success' => false,
            'message' => 'Security Error: Application is already installed and locked.'
        ]);
        exit;
    }

    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? 'game_platform');
    $user = trim($_POST['db_user'] ?? 'game_user');
    $pass = $_POST['db_pass'] ?? '';

    try {
        // First try connecting to MySQL server directly
        $dsnWithoutDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $pdo = new PDO($dsnWithoutDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 4,
        ]);

        $serverVer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        // Check if database exists
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$name]);
        $hasDb = (bool)$stmt->fetchColumn();

        $msg = "Connection Successful! Verified MySQL / MariaDB (v{$serverVer}). ";
        if ($hasDb) {
            $msg .= "Database '{$name}' exists and is ready.";
        } else {
            $msg .= "Database '{$name}' does not exist yet; installer will create it automatically.";
        }

        echo json_encode([
            'success' => true,
            'server_version' => $serverVer,
            'has_database'   => $hasDb,
            'message'        => $msg
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Connection Failed: ' . $e->getMessage()
        ]);
    }
    exit;
}

// -------------------------------------------------------------
// REINSTALLATION PROTECTION
// -------------------------------------------------------------
if ($isInstalled && (!isset($_GET['step']) || (int)$_GET['step'] !== 7 || empty($_SESSION['just_installed']))) {
    $lockData = [];
    if (file_exists($lockFile)) {
        $lockData = json_decode((string)file_get_contents($lockFile), true) ?: [];
    }
    $installedAt = $lockData['installed_at'] ?? 'Previously completed';
    $installedVer = $lockData['version'] ?? '1.0.0';
    $siteName = $lockData['site_name'] ?? 'NexusGaming';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Installed | <?= esc($siteName) ?></title>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #07090e; color: #f1f5f9; min-height: 100vh; display: flex; flex-direction: column; }
    </style>
</head>
<body class="bg-[#07090e] text-slate-100 flex items-center justify-center p-4">
    <div class="max-w-lg w-full p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl text-center space-y-6">
        
        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/40 flex items-center justify-center text-amber-400 mx-auto text-3xl font-black">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>

        <div class="space-y-2">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-950/80 text-amber-400 border border-amber-800/60">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                Reinstallation Protection Active
            </div>
            <h1 class="text-2xl font-black text-white">Application Already Installed</h1>
            <p class="text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
                <strong><?= esc($siteName) ?></strong> has already been configured and initialized. The installer has been permanently locked to safeguard existing database records.
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 text-xs text-slate-400 space-y-1.5 text-left font-mono">
            <div class="flex justify-between">
                <span class="text-slate-500">Installed Version:</span>
                <span class="text-white font-bold"><?= esc($installedVer) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Installation Date:</span>
                <span class="text-slate-300"><?= esc($installedAt) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Lock File Status:</span>
                <span class="text-emerald-400 font-bold">LOCKED (config/installed.lock)</span>
            </div>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/" class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-sky-600 hover:bg-sky-500 transition-colors">
                Visit Website &rarr;
            </a>
            <a href="/admin/login" class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-bold text-xs text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors">
                Admin Console Login &rarr;
            </a>
        </div>

    </div>
</body>
</html>
<?php
    exit;
}

// -------------------------------------------------------------
// INSTALLER MULTI-STEP LOGIC
// -------------------------------------------------------------
$step = (int)($_GET['step'] ?? 1);
if ($step < 1 || $step > 7) {
    $step = 1;
}

$error = '';
$success = '';

// Default values stored in installer session
if (!isset($_SESSION['installer_data'])) {
    // Detect host URL
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
    $autoSiteUrl = "{$scheme}://{$host}";

    $_SESSION['installer_data'] = [
        'db_host'      => '127.0.0.1',
        'db_port'      => '3306',
        'db_name'      => 'game_platform',
        'db_user'      => 'game_user',
        'db_pass'      => 'GamePass123!#',
        'site_name'    => 'NexusGaming',
        'site_url'     => $autoSiteUrl,
        'timezone'     => 'UTC',
        'admin_name'   => 'Super Administrator',
        'admin_username' => 'admin',
        'admin_email'  => 'admin@nexusgaming.local',
    ];
}

// Handle Form Submissions per Step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int)($_POST['step'] ?? 1);

    if ($postedStep === 3) {
        // Save database parameters
        $_SESSION['installer_data']['db_host'] = trim($_POST['db_host'] ?? '127.0.0.1');
        $_SESSION['installer_data']['db_port'] = trim($_POST['db_port'] ?? '3306');
        $_SESSION['installer_data']['db_name'] = trim($_POST['db_name'] ?? 'game_platform');
        $_SESSION['installer_data']['db_user'] = trim($_POST['db_user'] ?? 'game_user');
        $_SESSION['installer_data']['db_pass'] = $_POST['db_pass'] ?? '';

        if (empty($_SESSION['installer_data']['db_host']) || empty($_SESSION['installer_data']['db_name']) || empty($_SESSION['installer_data']['db_user'])) {
            $error = 'Database Host, Name, and Username are required.';
        } else {
            header('Location: /install/?step=4');
            exit;
        }

    } elseif ($postedStep === 4) {
        // Save website settings
        $_SESSION['installer_data']['site_name'] = trim($_POST['site_name'] ?? 'NexusGaming');
        $_SESSION['installer_data']['site_url']  = trim($_POST['site_url'] ?? 'http://localhost:3000');
        $_SESSION['installer_data']['timezone']  = trim($_POST['timezone'] ?? 'UTC');

        if (empty($_SESSION['installer_data']['site_name'])) {
            $error = 'Site Name is required.';
        } else {
            header('Location: /install/?step=5');
            exit;
        }

    } elseif ($postedStep === 5) {
        // Save and validate admin account
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminUser = trim($_POST['admin_username'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_password'] ?? '';
        $adminConfirm = $_POST['admin_password_confirm'] ?? '';

        $_SESSION['installer_data']['admin_name'] = $adminName;
        $_SESSION['installer_data']['admin_username'] = $adminUser;
        $_SESSION['installer_data']['admin_email'] = $adminEmail;

        if (empty($adminName)) {
            $error = 'Admin display name is required.';
        } elseif (empty($adminUser) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $adminUser)) {
            $error = 'Admin username must be 3-30 alphanumeric characters.';
        } elseif (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'A valid admin email is required.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Admin password must be at least 8 characters long.';
        } elseif ($adminPass !== $adminConfirm) {
            $error = 'Passwords do not match.';
        } else {
            $_SESSION['installer_data']['admin_pass'] = $adminPass;
            header('Location: /install/?step=6');
            exit;
        }

    } elseif ($postedStep === 6) {
        // STEP 6: REAL INSTALLATION EXECUTION
        $cfg = $_SESSION['installer_data'];

        try {
            // 1. Connect to MySQL server
            $dsnWithoutDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $cfg['db_host'], $cfg['db_port']);
            $pdo = new PDO($dsnWithoutDb, $cfg['db_user'], $cfg['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 2. Create database if it does not exist
            $safeDbName = str_replace('`', '``', $cfg['db_name']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 3. Connect to target database
            $dsnWithDb = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'], $cfg['db_port'], $cfg['db_name']);
            $db = new PDO($dsnWithDb, $cfg['db_user'], $cfg['db_pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // 4. Save Database Credentials to config/db_credentials.php
            $credFile = __DIR__ . '/../config/db_credentials.php';
            $credContent = "<?php\n"
                . "/** Database Credentials generated by Installer */\n"
                . "declare(strict_types=1);\n\n"
                . "define('DB_HOST', " . var_export($cfg['db_host'], true) . ");\n"
                . "define('DB_PORT', " . var_export($cfg['db_port'], true) . ");\n"
                . "define('DB_NAME', " . var_export($cfg['db_name'], true) . ");\n"
                . "define('DB_USER', " . var_export($cfg['db_user'], true) . ");\n"
                . "define('DB_PASS', " . var_export($cfg['db_pass'], true) . ");\n"
                . "define('DB_CHARSET', 'utf8mb4');\n";
            file_put_contents($credFile, $credContent);

            // 5. Execute database schema
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($queries as $q) {
                    if (!empty($q)) {
                        $db->exec($q);
                    }
                }
            }

            // 6. Insert / Update Configured Site Settings
            $settings = [
                'site_name'        => $cfg['site_name'],
                'logo_text'        => strtoupper($cfg['site_name']),
                'site_url'         => $cfg['site_url'],
                'timezone'         => $cfg['timezone'],
                'site_tagline'     => 'Play Online Games & Tournaments',
                'contact_email'    => $cfg['admin_email'],
                'contact_phone'    => '+1 (800) 555-NEXUS',
                'currency_symbol'  => '$',
                'min_deposit'      => '10.00',
                'max_deposit'      => '5000.00',
                'maintenance_mode' => '0',
                'footer_text'      => '© 2026 ' . $cfg['site_name'] . '. All rights reserved.',
                'deposit_instructions' => "Bank Transfer & Crypto instructions:\nSend to Account # NEXUS-8849-012\nOr Wallet 0x71C...b90f\nAfter transfer, submit transaction amount and reference number below for verification."
            ];

            $stmtSetting = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value, description, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            foreach ($settings as $k => $v) {
                $stmtSetting->execute([$k, $v, 'Configured via Installer']);
            }

            // 7. Create or Update First Admin Account
            $adminPassHash = password_hash($cfg['admin_pass'], PASSWORD_BCRYPT);
            
            $stmtCheckAdmin = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmtCheckAdmin->execute([$cfg['admin_username'], $cfg['admin_email']]);
            $existingAdmin = $stmtCheckAdmin->fetch();

            if ($existingAdmin) {
                $stmtUpAdmin = $db->prepare("
                    UPDATE admins
                    SET name = ?, username = ?, email = ?, password = ?, status = 'active', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpAdmin->execute([$cfg['admin_name'], $cfg['admin_username'], $cfg['admin_email'], $adminPassHash, $existingAdmin['id']]);
            } else {
                $stmtInsAdmin = $db->prepare("
                    INSERT INTO admins (name, username, email, password, status, created_at)
                    VALUES (?, ?, ?, ?, 'active', NOW())
                ");
                $stmtInsAdmin->execute([$cfg['admin_name'], $cfg['admin_username'], $cfg['admin_email'], $adminPassHash]);
            }

            // 8. Set Initial Application Version in app_versions
            $stmtCheckVer = $db->query("SELECT id FROM app_versions LIMIT 1");
            if (!$stmtCheckVer->fetch()) {
                $stmtVer = $db->prepare("
                    INSERT INTO app_versions (version, release_title, release_notes, github_repo, github_branch, applied_at, last_checked_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmtVer->execute([
                    'v1.0.0',
                    'Production Foundation Release',
                    'Initialized through web installer setup wizard.',
                    'nexus-gaming/core-platform',
                    'main'
                ]);
            }

            // 9. Create Foundation Game Entries if none exist
            $gamesCount = (int)$db->query("SELECT COUNT(*) FROM games")->fetchColumn();
            if ($gamesCount === 0) {
                $stmtG = $db->prepare("
                    INSERT INTO games (title, slug, category, description, status, min_bet, max_bet, module_identifier, config_json, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtG->execute(['Core Arena Module Alpha', 'core-arena-module-alpha', 'Strategy & Arena', 'Modular turn-based competitive gaming engine foundation.', 'active', 1.00, 500.00, 'mod_core_arena_v1', '{}', 1]);
                $stmtG->execute(['Tactical Simulation Core', 'tactical-simulation-core', 'Simulation', 'High-performance tactical calculation framework.', 'active', 2.00, 1000.00, 'mod_tactical_sim_v1', '{}', 2]);
                $stmtG->execute(['Arcade Physics Framework', 'arcade-physics-framework', 'Arcade Engine', 'Rigid-body 2D arcade gaming engine ready for custom physics modules.', 'inactive', 0.50, 250.00, 'mod_arcade_phys_v1', '{}', 3]);
            }

            // 10. Create Welcome Announcement if none exists
            $notifCount = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
            if ($notifCount === 0) {
                $stmtN = $db->prepare("
                    INSERT INTO notifications (title, message, type, target_type, is_active, created_at)
                    VALUES (?, ?, 'announcement', 'all', 1, NOW())
                ");
                $stmtN->execute([
                    'Welcome to ' . $cfg['site_name'],
                    'Our gaming platform has been successfully initialized! Explore your dashboard, test the wallet ledger, or contact the 24/7 support desk.'
                ]);
            }

            // 11. MARK AS INSTALLED (CREATE LOCK FILE)
            $lockContent = json_encode([
                'installed'    => true,
                'installed_at' => date('Y-m-d H:i:s T'),
                'version'      => '1.0.0',
                'site_name'    => $cfg['site_name'],
                'site_url'     => $cfg['site_url'],
                'lock_id'      => bin2hex(random_bytes(16))
            ], JSON_PRETTY_PRINT);
            file_put_contents($lockFile, $lockContent);

            // Clean up session password
            unset($_SESSION['installer_data']['admin_pass']);
            $_SESSION['just_installed'] = true;

            // Redirect to completion page
            header('Location: /install/?step=7');
            exit;

        } catch (Throwable $e) {
            $error = 'Installation execution failed: ' . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// SERVER REQUIREMENTS DATA (For Step 2)
// -------------------------------------------------------------
$reqs = [
    'php_version' => [
        'name'     => 'PHP Version (>= 8.1.0)',
        'current'  => PHP_VERSION,
        'passed'   => version_compare(PHP_VERSION, '8.1.0', '>='),
        'critical' => true,
    ],
    'pdo' => [
        'name'     => 'PDO Extension',
        'current'  => extension_loaded('pdo') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('pdo'),
        'critical' => true,
    ],
    'pdo_mysql' => [
        'name'     => 'PDO MySQL Driver',
        'current'  => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('pdo_mysql'),
        'critical' => true,
    ],
    'mbstring' => [
        'name'     => 'MBString Extension',
        'current'  => extension_loaded('mbstring') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('mbstring'),
        'critical' => true,
    ],
    'curl' => [
        'name'     => 'cURL Extension',
        'current'  => extension_loaded('curl') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('curl'),
        'critical' => true,
    ],
    'json' => [
        'name'     => 'JSON Extension',
        'current'  => extension_loaded('json') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('json'),
        'critical' => true,
    ],
    'session' => [
        'name'     => 'Session Support',
        'current'  => extension_loaded('session') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('session'),
        'critical' => true,
    ],
    'openssl' => [
        'name'     => 'OpenSSL Extension',
        'current'  => extension_loaded('openssl') ? 'Enabled' : 'Missing',
        'passed'   => extension_loaded('openssl'),
        'critical' => true,
    ],
    'config_writable' => [
        'name'     => 'config/ Directory Writable',
        'current'  => is_writable(__DIR__ . '/../config') ? 'Writable' : 'Not Writable',
        'passed'   => is_writable(__DIR__ . '/../config'),
        'critical' => true,
    ],
    'backups_writable' => [
        'name'     => 'backups/ Directory Writable',
        'current'  => is_writable(__DIR__ . '/../backups') ? 'Writable' : 'Not Writable',
        'passed'   => is_writable(__DIR__ . '/../backups'),
        'critical' => false,
    ],
];

$allReqsPassed = true;
foreach ($reqs as $r) {
    if ($r['critical'] && !$r['passed']) {
        $allReqsPassed = false;
        break;
    }
}

$data = $_SESSION['installer_data'];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Installer &bull; Step <?= $step ?> of 7 | NexusGaming</title>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#07090e',
                            900: '#0b0f19',
                            850: '#0e1320',
                            800: '#111827',
                            700: '#1e293b'
                        },
                        brand: {
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #07090e;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body class="bg-[#07090e] text-slate-100 font-sans antialiased">

    <!-- Installer Top Header (Non-Sticky) -->
    <header class="w-full bg-[#0b0f19] border-b border-slate-800/80 py-5">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-brand-500 flex items-center justify-center text-white font-black shadow-md shadow-brand-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-base font-black tracking-wider text-white uppercase">NexusGaming</span>
                    <span class="ml-2 text-[10px] font-bold uppercase tracking-widest text-sky-400 bg-sky-950/80 px-2 py-0.5 rounded border border-sky-800/60">
                        Setup Wizard
                    </span>
                </div>
            </div>
            <div class="text-xs text-slate-400 font-mono">
                Step <span class="text-white font-bold"><?= $step ?></span> of 7
            </div>
        </div>
    </header>

    <!-- Step Progress Indicator (Non-Sticky Flow) -->
    <div class="w-full bg-[#0b0f19]/60 border-b border-slate-800/60 py-3">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between overflow-x-auto text-[11px] font-semibold gap-2 pb-1 scrollbar-none">
                <?php
                $stepLabels = [
                    1 => 'Welcome',
                    2 => 'Requirements',
                    3 => 'Database',
                    4 => 'Website',
                    5 => 'Admin',
                    6 => 'Install',
                    7 => 'Complete',
                ];
                foreach ($stepLabels as $sNum => $sLabel):
                    $isActive = ($step === $sNum);
                    $isPassed = ($step > $sNum);
                ?>
                    <div class="flex items-center gap-1.5 whitespace-nowrap <?= $isActive ? 'text-sky-400' : ($isPassed ? 'text-emerald-400' : 'text-slate-500') ?>">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold border <?= $isActive ? 'border-sky-400 bg-sky-950' : ($isPassed ? 'border-emerald-500 bg-emerald-950 text-emerald-400' : 'border-slate-800 bg-slate-900') ?>">
                            <?= $isPassed ? '✓' : $sNum ?>
                        </span>
                        <span><?= $sLabel ?></span>
                        <?php if ($sNum < 7): ?>
                            <span class="text-slate-700 ml-1">&rsaquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Installer Content -->
    <main class="w-full py-10 px-4 sm:px-6 max-w-4xl mx-auto flex-1">
        
        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-2xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs flex items-center gap-3 mb-6">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div><?= esc($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- STEP 1: WELCOME -->
        <?php if ($step === 1): ?>
            <div class="p-8 sm:p-12 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-8 text-center max-w-2xl mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-600 via-sky-500 to-blue-400 flex items-center justify-center text-white mx-auto shadow-xl shadow-brand-500/25">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                    </svg>
                </div>

                <div class="space-y-3">
                    <h1 class="text-3xl sm:text-4xl font-black text-white">Welcome to NexusGaming</h1>
                    <p class="text-sm text-slate-300 leading-relaxed max-w-lg mx-auto">
                        This wizard will guide you through verifying server requirements, establishing your MySQL transactional ledger connection, customizing site parameters, and creating your primary administrator credentials.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left pt-2">
                    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-1">
                        <div class="text-[11px] font-bold uppercase text-brand-400">Pure Stack</div>
                        <div class="text-xs text-slate-300">PHP 8.2 + MariaDB / MySQL + Tailwind CSS</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-1">
                        <div class="text-[11px] font-bold uppercase text-brand-400">Strict Ledgers</div>
                        <div class="text-xs text-slate-300">Real transaction audit logs & foreign keys</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-1">
                        <div class="text-[11px] font-bold uppercase text-brand-400">Protected</div>
                        <div class="text-xs text-slate-300">Automated lock file upon completion</div>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="/install/?step=2" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-xl shadow-brand-500/25 transition-all">
                        Continue to Server Checks &rarr;
                    </a>
                </div>
            </div>

        <!-- STEP 2: SERVER REQUIREMENTS -->
        <?php elseif ($step === 2): ?>
            <div class="p-8 sm:p-10 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-white">Server Requirements & Permissions</h2>
                    <p class="text-xs text-slate-400 mt-1">Verify that PHP extensions and writable directory permissions are satisfied before continuing.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 uppercase font-semibold text-[10px]">
                            <tr>
                                <th class="p-3.5">Prerequisite Check</th>
                                <th class="p-3.5">Detected State</th>
                                <th class="p-3.5 text-right">Verification Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300">
                            <?php foreach ($reqs as $r): ?>
                                <tr class="hover:bg-slate-900/40">
                                    <td class="p-3.5 font-medium text-white"><?= esc($r['name']) ?></td>
                                    <td class="p-3.5 font-mono text-slate-400"><?= esc((string)$r['current']) ?></td>
                                    <td class="p-3.5 text-right">
                                        <?php if ($r['passed']): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Passed
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">
                                                Failed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                    <a href="/install/?step=1" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900">
                        &larr; Back
                    </a>

                    <?php if ($allReqsPassed): ?>
                        <a href="/install/?step=3" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-500/25 transition-all">
                            Continue to Database Setup &rarr;
                        </a>
                    <?php else: ?>
                        <button disabled class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-500 bg-slate-800 cursor-not-allowed">
                            Cannot Proceed (Fix Errors)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        <!-- STEP 3: DATABASE SETUP -->
        <?php elseif ($step === 3): ?>
            <div class="p-8 sm:p-10 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-white">Database Setup</h2>
                    <p class="text-xs text-slate-400 mt-1">Configure your MySQL connection parameters. Test the connection in real time before continuing.</p>
                </div>

                <!-- Live Test Connection Feedback Area -->
                <div id="db-test-result" class="hidden p-4 rounded-xl text-xs flex items-center gap-3"></div>

                <form method="POST" action="/install/?step=3" id="db-form" class="space-y-4">
                    <input type="hidden" name="step" value="3">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Database Host</label>
                            <input type="text" id="db_host" name="db_host" required value="<?= esc($data['db_host']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Port</label>
                            <input type="number" id="db_port" name="db_port" required value="<?= esc($data['db_port']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Database Name</label>
                        <input type="text" id="db_name" name="db_name" required value="<?= esc($data['db_name']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        <span class="text-[11px] text-slate-500 mt-1 block">If this database does not exist, the installer will attempt to create it.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Database Username</label>
                            <input type="text" id="db_user" name="db_user" required value="<?= esc($data['db_user']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Database Password</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?= esc($data['db_pass']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        </div>
                    </div>

                    <!-- Real Connection Test Button -->
                    <div class="pt-2">
                        <button type="button" id="btn-test-db" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span id="btn-test-db-label">Test Database Connection</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-slate-800">
                        <a href="/install/?step=2" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900">
                            &larr; Back
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-500/25 transition-all">
                            Save & Continue &rarr;
                        </button>
                    </div>
                </form>
            </div>

            <!-- Plain JS AJAX Script for Live Database Test -->
            <script>
                document.getElementById('btn-test-db').addEventListener('click', async () => {
                    const btnLabel = document.getElementById('btn-test-db-label');
                    const feedback = document.getElementById('db-test-result');
                    
                    btnLabel.textContent = 'Testing connection...';
                    feedback.className = 'hidden';

                    const formData = new FormData();
                    formData.append('action', 'test_db');
                    formData.append('db_host', document.getElementById('db_host').value);
                    formData.append('db_port', document.getElementById('db_port').value);
                    formData.append('db_name', document.getElementById('db_name').value);
                    formData.append('db_user', document.getElementById('db_user').value);
                    formData.append('db_pass', document.getElementById('db_pass').value);

                    try {
                        const res = await fetch('/install/', {
                            method: 'POST',
                            body: formData
                        });
                        const json = await res.json();

                        feedback.classList.remove('hidden');
                        if (json.success) {
                            feedback.className = 'p-4 rounded-xl text-xs flex items-center gap-3 border border-emerald-500/40 bg-emerald-950/40 text-emerald-200';
                            feedback.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span><span>' + json.message + '</span>';
                        } else {
                            feedback.className = 'p-4 rounded-xl text-xs flex items-center gap-3 border border-rose-500/40 bg-rose-950/40 text-rose-200';
                            feedback.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-400 shrink-0"></span><span>' + json.message + '</span>';
                        }
                    } catch (err) {
                        feedback.classList.remove('hidden');
                        feedback.className = 'p-4 rounded-xl text-xs flex items-center gap-3 border border-rose-500/40 bg-rose-950/40 text-rose-200';
                        feedback.textContent = 'Request failed: ' + err.message;
                    } finally {
                        btnLabel.textContent = 'Test Database Connection';
                    }
                });
            </script>

        <!-- STEP 4: WEBSITE SETUP -->
        <?php elseif ($step === 4): ?>
            <div class="p-8 sm:p-10 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-white">Website Configuration</h2>
                    <p class="text-xs text-slate-400 mt-1">Configure dynamic site branding, public domain endpoint, and local timezone.</p>
                </div>

                <form method="POST" action="/install/?step=4" class="space-y-4">
                    <input type="hidden" name="step" value="4">

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Site Name</label>
                        <input type="text" name="site_name" required value="<?= esc($data['site_name']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Displayed on platform headers, titles, and notices.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Site URL</label>
                        <input type="url" name="site_url" required value="<?= esc($data['site_url']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Default Timezone</label>
                        <select name="timezone" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none">
                            <?php
                            $tzList = ['UTC', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney'];
                            foreach ($tzList as $tz):
                            ?>
                                <option value="<?= $tz ?>" <?= ($data['timezone'] === $tz) ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-slate-800">
                        <a href="/install/?step=3" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900">
                            &larr; Back
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-500/25 transition-all">
                            Save & Continue &rarr;
                        </button>
                    </div>
                </form>
            </div>

        <!-- STEP 5: CREATE ADMIN ACCOUNT -->
        <?php elseif ($step === 5): ?>
            <div class="p-8 sm:p-10 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-white">Create Primary Administrator</h2>
                    <p class="text-xs text-slate-400 mt-1">Initialize the first super-admin account to access the control panel.</p>
                </div>

                <form method="POST" action="/install/?step=5" class="space-y-4">
                    <input type="hidden" name="step" value="5">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Full Name</label>
                            <input type="text" name="admin_name" required value="<?= esc($data['admin_name']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Admin Username</label>
                            <input type="text" name="admin_username" required value="<?= esc($data['admin_username']) ?>"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Admin Email</label>
                        <input type="email" name="admin_email" required value="<?= esc($data['admin_email']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Password</label>
                            <input type="password" name="admin_password" required minlength="8"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none placeholder:text-slate-600"
                                placeholder="Minimum 8 characters">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Confirm Password</label>
                            <input type="password" name="admin_password_confirm" required minlength="8"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 text-white text-xs outline-none placeholder:text-slate-600"
                                placeholder="Repeat password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-slate-800">
                        <a href="/install/?step=4" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900">
                            &larr; Back
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-500/25 transition-all">
                            Proceed to Final Step &rarr;
                        </button>
                    </div>
                </form>
            </div>

        <!-- STEP 6: INSTALL EXECUTION -->
        <?php elseif ($step === 6): ?>
            <div class="p-8 sm:p-10 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-white">Review & Execute Installation</h2>
                    <p class="text-xs text-slate-400 mt-1">Review the parameters below before writing database schemas, settings, and lock state.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <h3 class="font-bold text-white uppercase text-[10px] tracking-wider text-brand-400">Database Parameters</h3>
                        <div class="font-mono text-slate-300 space-y-1">
                            <div>Host: <?= esc($data['db_host']) ?>:<?= esc($data['db_port']) ?></div>
                            <div>Database: <?= esc($data['db_name']) ?></div>
                            <div>User: <?= esc($data['db_user']) ?></div>
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <h3 class="font-bold text-white uppercase text-[10px] tracking-wider text-brand-400">Platform Identity</h3>
                        <div class="text-slate-300 space-y-1">
                            <div>Site Name: <strong><?= esc($data['site_name']) ?></strong></div>
                            <div>Site URL: <span class="font-mono text-slate-400"><?= esc($data['site_url']) ?></span></div>
                            <div>Timezone: <?= esc($data['timezone']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-xs space-y-2">
                    <h3 class="font-bold text-white uppercase text-[10px] tracking-wider text-brand-400">Primary Admin Account</h3>
                    <div class="text-slate-300 space-y-1">
                        <div>Name: <?= esc($data['admin_name']) ?></div>
                        <div>Username: <code class="bg-slate-950 px-1 py-0.5 rounded text-amber-400 font-mono"><?= esc($data['admin_username']) ?></code></div>
                        <div>Email: <?= esc($data['admin_email']) ?></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-blue-950/40 border border-blue-800/60 text-xs text-blue-200 flex items-start gap-3">
                    <svg class="w-5 h-5 text-sky-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        Clicking <strong>Install Now</strong> will execute `schema.sql`, write `config/db_credentials.php`, insert default site configurations, and establish `config/installed.lock` to permanently secure the installer.
                    </div>
                </div>

                <form method="POST" action="/install/?step=6" class="flex items-center justify-between pt-4 border-t border-slate-800">
                    <input type="hidden" name="step" value="6">
                    <a href="/install/?step=5" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900">
                        &larr; Back
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 shadow-lg shadow-emerald-500/25 transition-all">
                        Install Now & Synchronize Database &rarr;
                    </button>
                </form>
            </div>

        <!-- STEP 7: INSTALLATION COMPLETE -->
        <?php elseif ($step === 7):
            unset($_SESSION['just_installed']);
        ?>
            <div class="p-8 sm:p-12 rounded-3xl bg-[#0b0f19] border border-emerald-500/40 shadow-2xl space-y-8 text-center max-w-2xl mx-auto">
                
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/40 flex items-center justify-center text-emerald-400 mx-auto text-3xl font-black">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase bg-emerald-950 text-emerald-400 border border-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Platform Successfully Initialized
                    </div>
                    <h1 class="text-3xl font-black text-white">Installation Complete!</h1>
                    <p class="text-xs text-slate-300 leading-relaxed max-w-md mx-auto">
                        Your NexusGaming platform has been fully installed with MySQL tables, site branding, and the primary administrator account. The installer is now locked.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-xs space-y-2 text-left font-mono">
                    <div class="flex justify-between items-center py-1 border-b border-slate-800/80">
                        <span class="text-slate-400 font-sans">Website URL:</span>
                        <a href="/" class="text-sky-400 hover:underline"><?= esc($data['site_url']) ?>/</a>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-800/80">
                        <span class="text-slate-400 font-sans">Admin Console URL:</span>
                        <a href="/admin/login" class="text-amber-400 hover:underline"><?= esc($data['site_url']) ?>/admin/login</a>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-400 font-sans">Installed Version:</span>
                        <span class="text-white font-bold">v1.0.0</span>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-xs text-white bg-sky-600 hover:bg-sky-500 shadow-lg shadow-sky-600/30 transition-all text-center">
                        Visit Public Website &rarr;
                    </a>
                    <a href="/admin/login" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-xs text-slate-950 bg-amber-500 hover:bg-amber-400 shadow-lg shadow-amber-500/20 transition-all text-center">
                        Sign In to Admin Console &rarr;
                    </a>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- Footer (Strictly Non-Sticky) -->
    <footer class="w-full bg-[#0b0f19] border-t border-slate-800/80 py-6 mt-auto">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center text-xs text-slate-500">
            NexusGaming Platform &bull; Modular Architecture &bull; Pure PHP, Tailwind CSS &amp; MySQL
        </div>
    </footer>

</body>
</html>
