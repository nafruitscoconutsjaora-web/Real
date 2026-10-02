<?php
/**
 * Admin Game Management
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$editGameId = (int)($_GET['edit'] ?? 0);
$editingGame = null;

// Handle POST actions: add, edit, toggle, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/games');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_game') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $category = trim($_POST['category'] ?? 'Arcade');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($title)) {
            set_flash('error', 'Game name is required.');
            redirect('/admin/games');
        }

        // Generate clean URL slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        if (empty($baseSlug)) {
            $baseSlug = 'game-' . time();
        }
        $slug = $baseSlug;

        // Ensure slug uniqueness
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM games WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $slug = $baseSlug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO games (title, slug, category, description, image, thumbnail_url, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$title, $slug, $category, $description, $image, $image, $status]);
            set_flash('success', "Game '{$title}' added successfully.");
            redirect('/admin/games');
        } catch (Exception $e) {
            set_flash('error', 'Failed to add game: ' . $e->getMessage());
            redirect('/admin/games');
        }

    } elseif ($action === 'update_game') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $category = trim($_POST['category'] ?? 'Arcade');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'inactive';

        if (empty($title)) {
            set_flash('error', 'Game name cannot be empty.');
            redirect('/admin/games?edit=' . $gameId);
        }

        try {
            $stmt = $db->prepare("
                UPDATE games
                SET title = ?, category = ?, description = ?, image = ?, thumbnail_url = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $category, $description, $image, $image, $status, $gameId]);
            set_flash('success', "Game '{$title}' updated successfully.");
            redirect('/admin/games');
        } catch (Exception $e) {
            set_flash('error', 'Update error: ' . $e->getMessage());
            redirect('/admin/games?edit=' . $gameId);
        }

    } elseif ($action === 'toggle_status') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $currentStatus = $_POST['current_status'] ?? 'inactive';
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

        $stmt = $db->prepare("UPDATE games SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $gameId]);

        $statusText = ($newStatus === 'active') ? 'enabled and is now visible to users' : 'disabled and hidden from user listings';
        set_flash('info', "Game has been {$statusText}.");
        redirect('/admin/games');

    } elseif ($action === 'delete_game') {
        $gameId = (int)($_POST['game_id'] ?? 0);

        try {
            // Fetch game title for flash notice
            $stmtG = $db->prepare("SELECT title FROM games WHERE id = ?");
            $stmtG->execute([$gameId]);
            $gameTitle = $stmtG->fetchColumn() ?: 'Game';

            $stmtDel = $db->prepare("DELETE FROM games WHERE id = ?");
            $stmtDel->execute([$gameId]);

            set_flash('success', "Game '{$gameTitle}' has been permanently deleted from the database.");
            redirect('/admin/games');
        } catch (Exception $e) {
            set_flash('error', 'Failed to delete game: ' . $e->getMessage());
            redirect('/admin/games');
        }
    }
}

// If editing a game
if ($editGameId > 0) {
    $stmtE = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmtE->execute([$editGameId]);
    $editingGame = $stmtE->fetch();
}

// Fetch all games from MySQL
$games = [];
$totalGamesCount = 0;
$activeGamesCount = 0;
$inactiveGamesCount = 0;

try {
    $stmt = $db->query("SELECT * FROM games ORDER BY id DESC");
    $games = $stmt->fetchAll();

    $totalGamesCount = count($games);
    foreach ($games as $g) {
        if ($g['status'] === 'active' || $g['status'] === 'enabled') {
            $activeGamesCount++;
        } else {
            $inactiveGamesCount++;
        }
    }
} catch (Exception $e) {
    error_log('Games query error: ' . $e->getMessage());
}

$adminPageTitle = 'Game Management';
$activeTab = 'games';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-brand-500"></span>
                Game Management
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">
                Add, edit, enable, disable, and remove games stored in MySQL.
            </p>
        </div>
        <button type="button" data-modal-target="add-game-modal" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Add New Game
        </button>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1">
            <div class="text-xs text-slate-400 font-medium">Total Games in MySQL</div>
            <div class="text-2xl font-black text-white"><?= $totalGamesCount ?></div>
            <div class="text-[11px] text-slate-500">Registered in database</div>
        </div>

        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1">
            <div class="text-xs text-emerald-400 font-medium flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Active / Visible
            </div>
            <div class="text-2xl font-black text-emerald-400"><?= $activeGamesCount ?></div>
            <div class="text-[11px] text-slate-500">Shown on landing page & /games</div>
        </div>

        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1">
            <div class="text-xs text-slate-400 font-medium flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-500"></span> Inactive / Hidden
            </div>
            <div class="text-2xl font-black text-slate-400"><?= $inactiveGamesCount ?></div>
            <div class="text-[11px] text-slate-500">Hidden from user side</div>
        </div>
    </div>

    <!-- Edit Game Form (Shown when edit parameter is present) -->
    <?php if ($editingGame): ?>
        <div class="p-6 sm:p-8 rounded-3xl bg-[#0b0f19] border border-brand-500/50 shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-400"></span>
                        Edit Game: <?= e($editingGame['title']) ?>
                    </h2>
                    <p class="text-xs text-slate-400">Modify game details and control visibility.</p>
                </div>
                <a href="/admin/games" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                    Close Edit &times;
                </a>
            </div>

            <form method="POST" action="/admin/games" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_game">
                <input type="hidden" name="game_id" value="<?= $editingGame['id'] ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Name *</label>
                        <input type="text" name="title" required value="<?= e($editingGame['title']) ?>"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Category</label>
                        <input type="text" name="category" value="<?= e($editingGame['category']) ?>" placeholder="e.g. Action, Arcade, Strategy, RPG, Sports"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Image URL</label>
                        <input type="text" name="image" value="<?= e($editingGame['image'] ?: $editingGame['thumbnail_url']) ?>" placeholder="https://example.com/game-image.jpg or /assets/images/game.jpg"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-mono transition-all">
                        <p class="text-[11px] text-slate-500 mt-1">Provide an image URL or path for the game card thumbnail.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Status</label>
                        <select name="status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 transition-all">
                            <option value="active" <?= in_array($editingGame['status'], ['active', 'enabled']) ? 'selected' : '' ?>>Active / Enabled (Visible to users)</option>
                            <option value="inactive" <?= in_array($editingGame['status'], ['inactive', 'maintenance']) ? 'selected' : '' ?>>Inactive / Disabled (Hidden from users)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                        placeholder="Short description of the gameplay and rules..."><?= e($editingGame['description']) ?></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                        Save Changes
                    </button>
                    <a href="/admin/games" class="px-4 py-2.5 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Games Management Table -->
    <div class="space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                All Games (<?= count($games) ?>)
            </h2>
            <span class="text-xs text-slate-400">Stored in real MySQL database</span>
        </div>

        <?php if (empty($games)): ?>
            <div class="p-16 rounded-3xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                </div>
                <h3 class="text-base font-bold text-slate-300">No games configured yet.</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Click "Add New Game" above to add your first game to the platform.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Game</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Description</th>
                            <th class="p-4">Visibility Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($games as $g): ?>
                            <?php 
                                $isActive = in_array($g['status'], ['active', 'enabled']);
                                $imgSrc = $g['image'] ?: $g['thumbnail_url'];
                            ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                            <?php if ($imgSrc): ?>
                                                <img src="<?= e($imgSrc) ?>" alt="<?= e($g['title']) ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="hidden items-center justify-center text-brand-400 w-full h-full">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-brand-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-sm"><?= e($g['title']) ?></div>
                                            <div class="font-mono text-[11px] text-slate-500"><?= e($g['slug']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 font-medium text-slate-300">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                                        <?= e($g['category']) ?>
                                    </span>
                                </td>

                                <td class="p-4 text-slate-400 max-w-xs truncate">
                                    <?= e($g['description'] ?: 'No description provided.') ?>
                                </td>

                                <td class="p-4">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-950/80 text-emerald-400 border border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active (Visible)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-900 text-slate-400 border border-slate-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Inactive (Hidden)
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Status Button -->
                                        <form method="POST" action="/admin/games" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="game_id" value="<?= $g['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $isActive ? 'active' : 'inactive' ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $isActive ? 'text-amber-400 hover:text-amber-300 hover:bg-amber-950/40 border border-amber-900/60' : 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-950/40 border border-emerald-900/60' ?>" title="<?= $isActive ? 'Hide game from players' : 'Make game visible to players' ?>">
                                                <?= $isActive ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <a href="/admin/games?edit=<?= $g['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700">
                                            Edit
                                        </a>

                                        <!-- Delete Game Form with Confirmation -->
                                        <form method="POST" action="/admin/games" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete the game &quot;<?= addslashes(e($g['title'])) ?>&quot;? This cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_game">
                                            <input type="hidden" name="game_id" value="<?= $g['id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border border-rose-900/60 transition-colors">
                                                Delete
                                            </button>
                                        </form>
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

<!-- Modal: Add New Game -->
<div id="add-game-modal" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="p-6 sm:p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 w-full max-w-xl space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-500"></span>
                    Add New Game
                </h2>
                <p class="text-xs text-slate-400">Register a new game into MySQL.</p>
            </div>
            <button type="button" data-modal-close class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form method="POST" action="/admin/games" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_game">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Name *</label>
                    <input type="text" name="title" required placeholder="e.g. Cyber Strike 2088"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Category</label>
                    <select name="category" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 transition-all">
                        <option value="Action">Action</option>
                        <option value="Arcade" selected>Arcade</option>
                        <option value="Strategy">Strategy</option>
                        <option value="RPG">RPG</option>
                        <option value="Sports">Sports</option>
                        <option value="Racing">Racing</option>
                        <option value="Puzzle">Puzzle</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Initial Status</label>
                    <select name="status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 transition-all">
                        <option value="active" selected>Active / Enabled (Immediately visible)</option>
                        <option value="inactive">Inactive / Disabled (Draft / hidden)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Image URL</label>
                    <input type="text" name="image" placeholder="https://images.unsplash.com/... or /assets/images/game.jpg"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-mono transition-all">
                    <p class="text-[11px] text-slate-500 mt-1">Direct URL to the game's banner or cover art.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Game Description</label>
                <textarea name="description" rows="3" placeholder="Brief summary of gameplay, objectives, and features..."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" data-modal-close class="px-4 py-2.5 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                    Add Game
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
