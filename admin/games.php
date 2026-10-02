<?php
/**
 * Admin Game Modules Foundation
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$editGameId = (int)($_GET['edit'] ?? 0);
$editingGame = null;

// Handle POST actions: add, edit, toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/games');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_game') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $category = trim($_POST['category'] ?? 'Arcade');
        $description = trim($_POST['description'] ?? '');
        $minBet = (float)($_POST['min_bet'] ?? 1.00);
        $maxBet = (float)($_POST['max_bet'] ?? 1000.00);
        $moduleIdentifier = trim($_POST['module_identifier'] ?? '');
        $configJson = trim($_POST['config_json'] ?? '{}');
        $status = in_array($_POST['status'], ['active', 'inactive', 'maintenance']) ? $_POST['status'] : 'inactive';

        // Auto slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        // Validate JSON
        json_decode($configJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $configJson = '{}';
        }

        if (empty($title)) {
            set_flash('error', 'Game title is required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO games (title, slug, category, description, min_bet, max_bet, module_identifier, config_json, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $slug, $category, $description, $minBet, $maxBet, $moduleIdentifier, $configJson, $status]);
                set_flash('success', "Game foundation module '{$title}' registered successfully.");
                redirect('/admin/games');
            } catch (Exception $e) {
                set_flash('error', 'Failed to register game: ' . $e->getMessage());
            }
        }

    } elseif ($action === 'update_game') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $category = trim($_POST['category'] ?? 'Arcade');
        $description = trim($_POST['description'] ?? '');
        $minBet = (float)($_POST['min_bet'] ?? 1.00);
        $maxBet = (float)($_POST['max_bet'] ?? 1000.00);
        $moduleIdentifier = trim($_POST['module_identifier'] ?? '');
        $configJson = trim($_POST['config_json'] ?? '{}');
        $status = in_array($_POST['status'], ['active', 'inactive', 'maintenance']) ? $_POST['status'] : 'inactive';

        try {
            $stmt = $db->prepare("
                UPDATE games
                SET title = ?, slug = ?, category = ?, description = ?, min_bet = ?, max_bet = ?,
                    module_identifier = ?, config_json = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $category, $description, $minBet, $maxBet, $moduleIdentifier, $configJson, $status, $gameId]);
            set_flash('success', "Game foundation '{$title}' updated successfully.");
            redirect('/admin/games');
        } catch (Exception $e) {
            set_flash('error', 'Update error: ' . $e->getMessage());
        }

    } elseif ($action === 'toggle_status') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $currentStatus = $_POST['current_status'] ?? 'inactive';
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

        $stmt = $db->prepare("UPDATE games SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $gameId]);
        set_flash('info', "Game status toggled to {$newStatus}.");
        redirect('/admin/games');
    }
}

// If editing
if ($editGameId > 0) {
    $stmtE = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmtE->execute([$editGameId]);
    $editingGame = $stmtE->fetch();
}

// Fetch all games
$games = [];
try {
    $stmt = $db->query("SELECT * FROM games ORDER BY id ASC");
    $games = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Games query error: ' . $e->getMessage());
}

$adminPageTitle = 'Game Foundation Engine';
$activeTab = 'games';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Game Engine Foundation</h1>
            <p class="text-xs text-slate-400 mt-0.5">Register, configure, and toggle game modules before future game logic deployment.</p>
        </div>
        <button type="button" data-modal-target="add-game-modal" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Register New Module
        </button>
    </div>

    <!-- If editing a game -->
    <?php if ($editingGame): ?>
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-amber-500/40 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h2 class="text-lg font-bold text-white">Edit Module: <?= e($editingGame['title']) ?></h2>
                <a href="/admin/games" class="text-xs text-slate-400 hover:text-white">Cancel &times;</a>
            </div>

            <form method="POST" action="/admin/games" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_game">
                <input type="hidden" name="game_id" value="<?= $editingGame['id'] ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Module Title</label>
                        <input type="text" name="title" required value="<?= e($editingGame['title']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">URL Identifier / Slug</label>
                        <input type="text" name="slug" required value="<?= e($editingGame['slug']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Category</label>
                        <input type="text" name="category" required value="<?= e($editingGame['category']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Engine Module Identifier</label>
                        <input type="text" name="module_identifier" value="<?= e($editingGame['module_identifier'] ?? '') ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono"
                            placeholder="mod_engine_v1">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Min Bet Limit</label>
                        <input type="number" step="0.01" name="min_bet" value="<?= $editingGame['min_bet'] ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Max Bet Limit</label>
                        <input type="number" step="0.01" name="max_bet" value="<?= $editingGame['max_bet'] ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                        <option value="active" <?= $editingGame['status'] === 'active' ? 'selected' : '' ?>>Active / Accessible</option>
                        <option value="inactive" <?= $editingGame['status'] === 'inactive' ? 'selected' : '' ?>>Inactive / Standby</option>
                        <option value="maintenance" <?= $editingGame['status'] === 'maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500"><?= e($editingGame['description']) ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Module JSON Config</label>
                    <textarea name="config_json" rows="3"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono"><?= e($editingGame['config_json']) ?></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400">
                        Update Game Module
                    </button>
                    <a href="/admin/games" class="px-4 py-2.5 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Games Foundation Directory Table -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-white">Registered Game Modules (<?= count($games) ?>)</h2>

        <?php if (empty($games)): ?>
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-500">No game modules configured yet. Click 'Register New Module' above.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Title / Slug</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Module Key</th>
                            <th class="p-4">Bet Limits</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($games as $g): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= e($g['title']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500"><?= e($g['slug']) ?></div>
                                </td>
                                <td class="p-4 font-medium text-slate-300"><?= e($g['category']) ?></td>
                                <td class="p-4 font-mono text-amber-400/90"><?= e($g['module_identifier'] ?: 'default') ?></td>
                                <td class="p-4 font-mono">
                                    <?= format_money($g['min_bet']) ?> &ndash; <?= format_money($g['max_bet']) ?>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $g['status'] === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : ($g['status'] === 'maintenance' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= e($g['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Status -->
                                        <form method="POST" action="/admin/games" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="game_id" value="<?= $g['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $g['status'] ?>">
                                            <button type="submit" class="px-2.5 py-1 rounded text-xs font-semibold <?= $g['status'] === 'active' ? 'text-amber-400 hover:bg-slate-800' : 'text-emerald-400 hover:bg-slate-800' ?>">
                                                <?= $g['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>

                                        <a href="/admin/games?edit=<?= $g['id'] ?>" class="px-2.5 py-1 rounded text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Add New Game Module -->
<div id="add-game-modal" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 w-full max-w-xl space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white">Register New Game Engine Module</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="/admin/games" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_game">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Module Title</label>
                    <input type="text" name="title" required placeholder="e.g. Arena Engine Beta"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Category</label>
                    <input type="text" name="category" required value="Strategy"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">URL Identifier / Slug</label>
                    <input type="text" name="slug" placeholder="e.g. arena-engine-beta"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Module Key</label>
                    <input type="text" name="module_identifier" placeholder="e.g. mod_arena_v2"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Min Bet Limit</label>
                    <input type="number" step="0.01" name="min_bet" value="1.00"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Max Bet Limit</label>
                    <input type="number" step="0.01" name="max_bet" value="1000.00"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                    <option value="inactive">Inactive / Standby</option>
                    <option value="active">Active / Production Ready</option>
                    <option value="maintenance">Under Maintenance</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Architecture details for this module..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Initial Config JSON</label>
                <textarea name="config_json" rows="2" placeholder='{"engine": "webgl", "max_players": 2}'
                    class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 font-mono">{}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" data-modal-close class="px-4 py-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400">Save Module</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
