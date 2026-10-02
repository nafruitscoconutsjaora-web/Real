<?php
/**
 * Database Initialization & Seed Script
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

try {
    $db = get_db();

    // 1. Run schema.sql
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Schema file not found at: {$schemaFile}");
    }

    $sql = file_get_contents($schemaFile);
    // Split into individual statements
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->exec($query);
        }
    }

    // 2. Seed Default Site Settings
    $defaultSettings = [
        'site_name'        => 'NexusGaming',
        'logo_text'        => 'NEXUS GAMING',
        'site_tagline'     => 'Modular Web Gaming Architecture',
        'contact_email'    => 'support@nexusgaming.local',
        'contact_phone'    => '+1 (800) 555-NEXUS',
        'currency_symbol'  => '$',
        'min_deposit'      => '10.00',
        'max_deposit'      => '5000.00',
        'maintenance_mode' => '0',
        'footer_text'      => '© 2026 NexusGaming Platform. All rights reserved.',
        'deposit_instructions' => "Bank Transfer & Crypto instructions:\nSend to Account # NEXUS-8849-012\nOr Wallet 0x71C...b90f\nAfter transfer, submit transaction amount and reference number below for verification."
    ];

    $stmtCheckSetting = $db->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
    $stmtInsertSetting = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");

    foreach ($defaultSettings as $key => $val) {
        $stmtCheckSetting->execute([$key]);
        if (!$stmtCheckSetting->fetch()) {
            $stmtInsertSetting->execute([$key, $val, 'Default system setting for ' . $key]);
        }
    }

    // 3. Seed Default Admin
    $stmtCheckAdmin = $db->prepare("SELECT id FROM admins WHERE username = ?");
    $stmtCheckAdmin->execute(['admin']);
    if (!$stmtCheckAdmin->fetch()) {
        $adminPasswordHash = password_hash('AdminPassword123!', PASSWORD_BCRYPT);
        $stmtInsertAdmin = $db->prepare("
            INSERT INTO admins (username, name, email, password, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        $stmtInsertAdmin->execute(['admin', 'Super Administrator', 'admin@nexusgaming.local', $adminPasswordHash]);
        echo "Default admin created: username 'admin', password 'AdminPassword123!'\n";
    }

    // 4. Seed Initial Version
    $stmtCheckVer = $db->query("SELECT id FROM app_versions LIMIT 1");
    if (!$stmtCheckVer->fetch()) {
        $stmtInsertVer = $db->prepare("
            INSERT INTO app_versions (version, release_title, release_notes, github_repo, github_branch, last_checked_at, applied_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmtInsertVer->execute([
            'v1.0.0',
            'Foundation Release 1.0.0',
            'Initial production-ready gaming website foundation with pure PHP, MySQL persistence, dark theme, wallet foundation, ticket system, and admin update engine.',
            'nexus-gaming/core-platform',
            'main'
        ]);
    }

    // 5. Seed Initial Game Modules Foundation (no specific game names)
    $stmtCheckGames = $db->query("SELECT COUNT(*) as cnt FROM games");
    $gameCount = (int)$stmtCheckGames->fetch()['cnt'];
    if ($gameCount === 0) {
        $stmtInsertGame = $db->prepare("
            INSERT INTO games (title, slug, category, description, status, min_bet, max_bet, module_identifier, config_json, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $sampleGames = [
            [
                'Core Arena Module Alpha',
                'core-arena-module-alpha',
                'Strategy & Arena',
                'Modular turn-based competitive gaming engine foundation with real-time state synchronization support.',
                'active',
                1.00,
                500.00,
                'mod_core_arena_v1',
                json_encode(['engine' => 'canvas', 'max_players' => 4, 'tick_rate' => 60]),
                1
            ],
            [
                'Tactical Simulation Core',
                'tactical-simulation-core',
                'Simulation',
                'High-performance tactical calculation framework with server-side outcome determination architecture.',
                'active',
                2.00,
                1000.00,
                'mod_tactical_sim_v1',
                json_encode(['engine' => 'isometric', 'difficulty' => 'adaptive', 'multiplayer' => true]),
                2
            ],
            [
                'Arcade Physics Framework',
                'arcade-physics-framework',
                'Arcade Engine',
                'Rigid-body 2D arcade gaming engine ready for custom physics-based module integration.',
                'inactive',
                0.50,
                250.00,
                'mod_arcade_phys_v1',
                json_encode(['engine' => 'physics_2d', 'gravity' => 9.8, 'fps' => 60]),
                3
            ]
        ];

        foreach ($sampleGames as $game) {
            $stmtInsertGame->execute($game);
        }
    }

    // 6. Seed Initial Announcement
    $stmtCheckNotif = $db->query("SELECT COUNT(*) as cnt FROM notifications");
    $notifCount = (int)$stmtCheckNotif->fetch()['cnt'];
    if ($notifCount === 0) {
        $stmtInsertNotif = $db->prepare("
            INSERT INTO notifications (title, message, type, target_type, is_active, created_by, created_at)
            VALUES (?, ?, ?, 'all', 1, 1, NOW())
        ");
        $stmtInsertNotif->execute([
            'Welcome to the NexusGaming Platform',
            'Our modular gaming platform is now live! Explore your dashboard, test the wallet system, or contact our 24/7 support desk if you need any assistance.',
            'announcement'
        ]);
    }

    echo "Database initialization completed successfully.\n";

} catch (Throwable $e) {
    echo "Database init failed: " . $e->getMessage() . "\n";
    exit(1);
}
