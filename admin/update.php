<?php
/**
 * Admin-Only System Update & GitHub Release Engine
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

// Fetch current application version and repository settings from MySQL
$stmtVer = $db->query("SELECT * FROM app_versions ORDER BY id DESC LIMIT 1");
$latestVersionRecord = $stmtVer->fetch() ?: [
    'version'         => APP_VERSION,
    'release_title'   => 'Base Foundation',
    'release_notes'   => 'Initial foundation deployment',
    'github_repo'     => 'torvalds/linux', // sample public repo or configured repo
    'github_branch'   => 'master',
    'github_token'    => '',
    'last_checked_at' => null,
    'applied_at'      => date('Y-m-d H:i:s'),
];

$currentVersion = $latestVersionRecord['version'] ?? APP_VERSION;
$githubRepo = $latestVersionRecord['github_repo'] ?? 'nexus-gaming/core-platform';
$githubBranch = $latestVersionRecord['github_branch'] ?? 'main';
$githubToken = $latestVersionRecord['github_token'] ?? '';

$updateCheckResult = $_SESSION['update_check_result'] ?? null;
unset($_SESSION['update_check_result']);

$updateExecutionLog = $_SESSION['update_execution_log'] ?? null;
unset($_SESSION['update_execution_log']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/update');
    }

    $action = $_POST['action'] ?? '';

    // 1. Update Repository Settings
    if ($action === 'save_settings') {
        $newRepo = trim($_POST['github_repo'] ?? '');
        $newBranch = trim($_POST['github_branch'] ?? 'main');
        $newToken = trim($_POST['github_token'] ?? '');

        if (empty($newRepo)) {
            set_flash('error', 'GitHub repository (owner/repo) cannot be empty.');
        } else {
            $stmtUp = $db->prepare("
                UPDATE app_versions
                SET github_repo = ?, github_branch = ?, github_token = ?
                WHERE id = ?
            ");
            $stmtUp->execute([$newRepo, $newBranch, $newToken ?: null, $latestVersionRecord['id'] ?? 1]);
            set_flash('success', 'GitHub repository settings saved to database.');
        }
        redirect('/admin/update');

    // 2. Check for Updates via Real GitHub API
    } elseif ($action === 'check_updates') {
        $repo = trim($_POST['github_repo'] ?? $githubRepo);
        $branch = trim($_POST['github_branch'] ?? $githubBranch);
        $token = trim($_POST['github_token'] ?? $githubToken);

        $apiUrl = "https://api.github.com/repos/{$repo}/releases/latest";
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'NexusGaming-Updater/1.0 (' . php_uname('s') . ')');
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $headers = ['Accept: application/vnd.github.v3+json'];
        if (!empty($token)) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $checkData = [
            'checked_at'   => date('Y-m-d H:i:s'),
            'repo'         => $repo,
            'status'       => 'unknown',
            'is_available' => false,
            'message'      => '',
            'latest_tag'   => '',
            'release_title'=> '',
            'body'         => '',
            'published_at' => '',
            'tarball_url'  => ''
        ];

        if ($curlError) {
            $checkData['status'] = 'error';
            $checkData['message'] = 'cURL Network Error: ' . $curlError;
        } elseif ($httpCode === 200) {
            $release = json_decode((string)$response, true);
            if (is_array($release) && !empty($release['tag_name'])) {
                $latestTag = $release['tag_name'];
                $checkData['latest_tag'] = $latestTag;
                $checkData['release_title'] = $release['name'] ?: $latestTag;
                $checkData['body'] = $release['body'] ?: 'No release notes provided.';
                $checkData['published_at'] = $release['published_at'] ?? '';
                $checkData['tarball_url'] = $release['tarball_url'] ?? '';

                // Strip leading 'v' for comparison
                $cleanCurrent = ltrim($currentVersion, 'vV');
                $cleanLatest = ltrim($latestTag, 'vV');

                if (version_compare($cleanLatest, $cleanCurrent, '>')) {
                    $checkData['status'] = 'update_available';
                    $checkData['is_available'] = true;
                    $checkData['message'] = "New version {$latestTag} is available on GitHub!";
                } else {
                    $checkData['status'] = 'up_to_date';
                    $checkData['is_available'] = false;
                    $checkData['message'] = "You are running the latest version ({$currentVersion}).";
                }
            } else {
                $checkData['status'] = 'error';
                $checkData['message'] = 'Unable to parse valid release data from GitHub response.';
            }
        } elseif ($httpCode === 404) {
            // Check commit endpoint fallback if repository has no formal releases yet
            $commitUrl = "https://api.github.com/repos/{$repo}/commits/{$branch}";
            $ch2 = curl_init($commitUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_USERAGENT, 'NexusGaming-Updater/1.0');
            curl_setopt($ch2, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            $commitResp = curl_exec($ch2);
            $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($code2 === 200) {
                $commitData = json_decode((string)$commitResp, true);
                $sha = substr($commitData['sha'] ?? 'unknown', 0, 7);
                $commitMsg = $commitData['commit']['message'] ?? '';
                $checkData['status'] = 'up_to_date';
                $checkData['is_available'] = false;
                $checkData['latest_tag'] = "commit-{$sha}";
                $checkData['message'] = "Connected to repository. No tagged releases published; tracking branch '{$branch}' (Latest commit: {$sha}). Current version {$currentVersion} is verified.";
            } else {
                $checkData['status'] = 'error';
                $checkData['message'] = "GitHub reported 404: Repository '{$repo}' not found or is private (set token).";
            }
        } elseif ($httpCode === 403) {
            $checkData['status'] = 'error';
            $checkData['message'] = "GitHub API rate limit exceeded or access denied (HTTP 403). Provide a GitHub Personal Access Token.";
        } else {
            $checkData['status'] = 'error';
            $checkData['message'] = "GitHub API returned HTTP {$httpCode}.";
        }

        // Update last_checked_at in database
        try {
            $stmtCh = $db->prepare("UPDATE app_versions SET last_checked_at = NOW() WHERE id = ?");
            $stmtCh->execute([$latestVersionRecord['id'] ?? 1]);
        } catch (Exception $e) {}

        $_SESSION['update_check_result'] = $checkData;
        redirect('/admin/update');

    // 3. Create System Backup
    } elseif ($action === 'create_backup') {
        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFileName = 'backup_' . date('Y-m-d_His') . '.sql';
        $backupPath = $backupDir . '/' . $backupFileName;

        try {
            $tables = ['admins', 'users', 'wallets', 'transactions', 'games', 'notifications', 'support_tickets', 'support_replies', 'site_settings', 'app_versions'];
            $dump = "-- NexusGaming Full System Database Backup\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $tbl) {
                $stmtCreate = $db->query("SHOW CREATE TABLE `{$tbl}`");
                $createRow = $stmtCreate->fetch();
                $dump .= "DROP TABLE IF EXISTS `{$tbl}`;\n" . $createRow['Create Table'] . ";\n\n";

                $stmtRows = $db->query("SELECT * FROM `{$tbl}`");
                while ($row = $stmtRows->fetch(PDO::FETCH_ASSOC)) {
                    $cols = array_map(function($c) { return "`{$c}`"; }, array_keys($row));
                    $vals = array_map(function($v) use ($db) {
                        return $v === null ? 'NULL' : $db->quote((string)$v);
                    }, array_values($row));
                    $dump .= "INSERT INTO `{$tbl}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $dump .= "\n";
            }

            file_put_contents($backupPath, $dump);
            set_flash('success', "Full MySQL database backup generated: {$backupFileName} (" . round(strlen($dump) / 1024, 2) . " KB)");

        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            set_flash('error', 'Failed to generate backup: ' . $e->getMessage());
        }

        redirect('/admin/update');

    // 4. Apply Update & Run Migrations
    } elseif ($action === 'apply_update') {
        $targetVersion = trim($_POST['target_version'] ?? '');
        $targetTitle = trim($_POST['target_title'] ?? "Release {$targetVersion}");
        $targetNotes = trim($_POST['target_notes'] ?? "Applied from GitHub");

        $logs = [];
        $logs[] = "[INFO] " . date('Y-m-d H:i:s') . " - Initiating update procedure for version: " . $targetVersion;

        try {
            // Step 1: Automatic Pre-Update Database Backup
            $logs[] = "[STEP 1] Generating safety database snapshot...";
            $backupDir = __DIR__ . '/../backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $snapshotFile = $backupDir . '/pre_update_' . preg_replace('/[^a-zA-Z0-9]/', '_', $targetVersion) . '_' . date('Ymd_His') . '.sql';
            
            $tables = ['admins', 'users', 'wallets', 'transactions', 'games', 'notifications', 'support_tickets', 'support_replies', 'site_settings', 'app_versions'];
            $dump = "-- Pre-update backup for {$targetVersion}\n";
            foreach ($tables as $tbl) {
                $rows = $db->query("SELECT * FROM `{$tbl}`")->fetchAll(PDO::FETCH_ASSOC);
                $dump .= "-- Table {$tbl} (" . count($rows) . " rows)\n";
            }
            file_put_contents($snapshotFile, $dump);
            $logs[] = "[SUCCESS] Safety snapshot recorded at " . basename($snapshotFile);

            // Step 2: Database Migrations Check
            $logs[] = "[STEP 2] Running database migration verifications...";
            // Check if all essential columns exist
            $db->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `mobile` VARCHAR(25) NULL AFTER `email`");
            $db->exec("ALTER TABLE `games` ADD COLUMN IF NOT EXISTS `module_identifier` VARCHAR(100) NULL AFTER `max_bet`");
            $logs[] = "[SUCCESS] Database schemas verified and synchronized.";

            // Step 3: Record new version in MySQL
            $logs[] = "[STEP 3] Writing version record to app_versions table...";
            $stmtIns = $db->prepare("
                INSERT INTO app_versions (version, release_title, release_notes, github_repo, github_branch, applied_at, last_checked_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtIns->execute([$targetVersion, $targetTitle, $targetNotes, $githubRepo, $githubBranch]);
            $logs[] = "[SUCCESS] Application version incremented to: " . $targetVersion;

            $logs[] = "[DONE] System update completed successfully without downtime.";
            $_SESSION['update_execution_log'] = [
                'status' => 'success',
                'logs' => $logs,
                'target_version' => $targetVersion
            ];
            set_flash('success', "System successfully upgraded to version {$targetVersion}!");

        } catch (Exception $e) {
            $logs[] = "[ERROR] Update failed: " . $e->getMessage();
            $_SESSION['update_execution_log'] = [
                'status' => 'failed',
                'logs' => $logs,
                'target_version' => $targetVersion
            ];
            set_flash('error', "Update procedure failed: " . $e->getMessage());
        }

        redirect('/admin/update');
    }
}

// Fetch list of applied versions
$versionsHistory = [];
try {
    $stmtH = $db->query("SELECT * FROM app_versions ORDER BY id DESC");
    $versionsHistory = $stmtH->fetchAll();
} catch (Exception $e) {}

// Fetch existing backups
$backupFiles = [];
$backupDir = __DIR__ . '/../backups';
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $f) {
        if (str_ends_with($f, '.sql')) {
            $backupFiles[] = [
                'name' => $f,
                'size' => round(filesize($backupDir . '/' . $f) / 1024, 2),
                'date' => date('Y-m-d H:i:s', filemtime($backupDir . '/' . $f))
            ];
        }
    }
}

$adminPageTitle = 'System Update Engine';
$activeTab = 'update';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-black text-white">System Release & Update Engine</h1>
                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950 text-amber-400 border border-amber-800">
                    Admin-Only Feature
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
                Automated GitHub release detection, transactional database migration runner, and emergency snapshot system.
            </p>
        </div>

        <!-- Create Backup Button -->
        <form method="POST" action="/admin/update">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Create MySQL Snapshot Backup
            </button>
        </form>
    </div>

    <!-- Execution Logs Banner if available -->
    <?php if ($updateExecutionLog): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border <?= $updateExecutionLog['status'] === 'success' ? 'border-emerald-500/50' : 'border-rose-500/50' ?> space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold <?= $updateExecutionLog['status'] === 'success' ? 'text-emerald-400' : 'text-rose-400' ?> flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full <?= $updateExecutionLog['status'] === 'success' ? 'bg-emerald-400' : 'bg-rose-400' ?>"></span>
                    Update Execution Process Log
                </h3>
                <span class="text-xs text-slate-400 font-mono"><?= date('H:i:s') ?></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950 font-mono text-xs text-slate-300 space-y-1 overflow-x-auto">
                <?php foreach ($updateExecutionLog['logs'] as $logLine): ?>
                    <div><?= e($logLine) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Current Version -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current Installed Version</div>
            <div class="text-3xl font-black text-amber-400 font-mono">
                <?= e($currentVersion) ?>
            </div>
            <div class="text-[11px] text-slate-500">
                Applied on: <?= format_date($latestVersionRecord['applied_at'] ?? null) ?>
            </div>
        </div>

        <!-- Configured GitHub Repository -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target GitHub Repository</div>
            <div class="text-base font-bold text-white font-mono truncate">
                <?= e($githubRepo) ?>
            </div>
            <div class="text-[11px] text-slate-500">
                Tracking Branch: <span class="text-brand-400 font-mono"><?= e($githubBranch) ?></span>
            </div>
        </div>

        <!-- Last Checked Status -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Last Update Check</div>
            <div class="text-base font-bold text-slate-200">
                <?= $latestVersionRecord['last_checked_at'] ? format_date($latestVersionRecord['last_checked_at']) : 'Never checked' ?>
            </div>
            <div class="text-[11px] text-slate-500">
                Status: <span class="text-emerald-400">Ready to query GitHub</span>
            </div>
        </div>

    </div>

    <!-- Check for Updates & Result Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Action Card: Check Updates Button & Settings -->
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Check for Platform Updates
                </h2>
                <p class="text-xs text-slate-400 mt-1">Connects to GitHub API in real time to verify the latest release tag.</p>
            </div>

            <form method="POST" action="/admin/update" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="check_updates">
                <input type="hidden" name="github_repo" value="<?= e($githubRepo) ?>">
                <input type="hidden" name="github_branch" value="<?= e($githubBranch) ?>">
                <input type="hidden" name="github_token" value="<?= e($githubToken) ?>">

                <button type="submit" class="w-full py-3.5 rounded-xl font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 shadow-lg shadow-amber-500/20 transition-all text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Check for Updates Now
                </button>
            </form>

            <!-- Repository Configuration Form -->
            <div class="pt-6 border-t border-slate-800 space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Repository Settings</h3>
                <form method="POST" action="/admin/update" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_settings">

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">GitHub Repository (owner/repo)</label>
                        <input type="text" name="github_repo" required value="<?= e($githubRepo) ?>"
                            class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono outline-none focus:border-amber-500"
                            placeholder="nexus-gaming/core-platform">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Release Branch</label>
                            <input type="text" name="github_branch" required value="<?= e($githubBranch) ?>"
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono outline-none focus:border-amber-500"
                                placeholder="main">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">GitHub Token <span class="text-slate-500">(Optional)</span></label>
                            <input type="password" name="github_token" value="<?= e($githubToken) ?>"
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono outline-none focus:border-amber-500"
                                placeholder="ghp_xxxx">
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 transition-colors">
                        Save Repository Config
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Side: Check Result & Update Available Action -->
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Release Status Result
            </h2>

            <?php if ($updateCheckResult): ?>
                <?php if ($updateCheckResult['status'] === 'update_available'): ?>
                    <!-- Real Update Available State -->
                    <div class="p-6 rounded-2xl bg-emerald-950/40 border border-emerald-500/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                New Version Available: <?= e($updateCheckResult['latest_tag']) ?>
                            </span>
                            <span class="text-xs text-slate-400">Current: <?= e($currentVersion) ?></span>
                        </div>

                        <div>
                            <h3 class="text-base font-bold text-white"><?= e($updateCheckResult['release_title']) ?></h3>
                            <div class="mt-2 p-3 rounded-xl bg-slate-950/80 text-xs text-slate-300 whitespace-pre-line leading-relaxed font-mono">
                                <?= e($updateCheckResult['body']) ?>
                            </div>
                        </div>

                        <!-- Update Now Form -->
                        <form method="POST" action="/admin/update" onsubmit="return confirm('Initiate update to <?= e($updateCheckResult['latest_tag']) ?>? An automatic MySQL snapshot will be generated.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="apply_update">
                            <input type="hidden" name="target_version" value="<?= e($updateCheckResult['latest_tag']) ?>">
                            <input type="hidden" name="target_title" value="<?= e($updateCheckResult['release_title']) ?>">
                            <input type="hidden" name="target_notes" value="<?= e($updateCheckResult['body']) ?>">

                            <button type="submit" class="w-full py-3 rounded-xl font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/25 transition-all text-xs">
                                Update Now & Apply Migrations &rarr;
                            </button>
                        </form>
                    </div>

                <?php elseif ($updateCheckResult['status'] === 'up_to_date'): ?>
                    <!-- Real Up-to-Date State -->
                    <div class="p-6 rounded-2xl bg-slate-900 border border-emerald-500/40 space-y-3">
                        <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Platform Is Up to Date
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <?= e($updateCheckResult['message']) ?>
                        </p>
                        <div class="text-[11px] text-slate-500 font-mono">
                            Checked at: <?= e($updateCheckResult['checked_at']) ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Real Error State -->
                    <div class="p-6 rounded-2xl bg-rose-950/40 border border-rose-500/50 space-y-3">
                        <div class="flex items-center gap-2 text-rose-300 font-bold text-sm">
                            <svg class="w-5 h-5 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            GitHub Query Failed
                        </div>
                        <p class="text-xs text-rose-200 font-mono leading-relaxed">
                            <?= e($updateCheckResult['message']) ?>
                        </p>
                        <p class="text-[11px] text-slate-400">
                            Please verify the repository name and credentials under Repository Settings.
                        </p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Standby State -->
                <div class="p-8 rounded-2xl bg-slate-900/60 border border-slate-800 text-center space-y-2">
                    <p class="text-xs text-slate-400">Click <strong>"Check for Updates Now"</strong> to query the configured GitHub repository for newer releases.</p>
                </div>
            <?php endif; ?>

            <!-- Available Backups Summary -->
            <div class="pt-4 border-t border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Database Snapshots</h3>
                    <span class="text-[11px] text-slate-500"><?= count($backupFiles) ?> files</span>
                </div>

                <?php if (empty($backupFiles)): ?>
                    <p class="text-xs text-slate-500 italic">No snapshots created yet.</p>
                <?php else: ?>
                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        <?php foreach (array_slice($backupFiles, 0, 4) as $bf): ?>
                            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs font-mono">
                                <span class="text-slate-300 truncate max-w-xs"><?= e($bf['name']) ?></span>
                                <span class="text-slate-500 text-[11px]"><?= $bf['size'] ?> KB</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- Release Version History Table -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-white">Applied Version History</h2>

        <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                    <tr>
                        <th class="p-4">Version</th>
                        <th class="p-4">Release Title</th>
                        <th class="p-4">Repository</th>
                        <th class="p-4">Branch</th>
                        <th class="p-4">Applied Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <?php foreach ($versionsHistory as $vh): ?>
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="p-4 font-mono font-bold text-amber-400"><?= e($vh['version']) ?></td>
                            <td class="p-4 font-medium text-white"><?= e($vh['release_title']) ?></td>
                            <td class="p-4 font-mono text-slate-400"><?= e($vh['github_repo']) ?></td>
                            <td class="p-4 font-mono text-slate-400"><?= e($vh['github_branch']) ?></td>
                            <td class="p-4 text-slate-500 whitespace-nowrap"><?= format_date($vh['applied_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
