<?php
/**
 * Admin Announcements & Notifications
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$editId = (int)($_GET['edit'] ?? 0);
$editingItem = null;

// Handle POST actions: create, update, delete, toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/notifications');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = in_array($_POST['type'], ['info', 'warning', 'success', 'announcement']) ? $_POST['type'] : 'info';
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($message)) {
            set_flash('error', 'Both title and message are required.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO notifications (title, message, type, target_type, is_active, created_by, created_at)
                VALUES (?, ?, ?, 'all', ?, ?, NOW())
            ");
            $stmt->execute([$title, $message, $type, $isActive, $admin['id']]);
            set_flash('success', 'Announcement published successfully.');
            redirect('/admin/notifications');
        }

    } elseif ($action === 'update') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = in_array($_POST['type'], ['info', 'warning', 'success', 'announcement']) ? $_POST['type'] : 'info';
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("
            UPDATE notifications
            SET title = ?, message = ?, type = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $message, $type, $isActive, $notifId]);
        set_flash('success', 'Announcement updated.');
        redirect('/admin/notifications');

    } elseif ($action === 'toggle_active') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        $current = (int)($_POST['current_active'] ?? 0);
        $newVal = $current === 1 ? 0 : 1;

        $stmt = $db->prepare("UPDATE notifications SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newVal, $notifId]);
        set_flash('info', 'Announcement active state toggled.');
        redirect('/admin/notifications');

    } elseif ($action === 'delete') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notifId]);
        set_flash('info', 'Announcement deleted.');
        redirect('/admin/notifications');
    }
}

if ($editId > 0) {
    $stmtE = $db->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmtE->execute([$editId]);
    $editingItem = $stmtE->fetch();
}

$items = [];
try {
    $stmt = $db->query("SELECT * FROM notifications ORDER BY id DESC");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Notifications query error: ' . $e->getMessage());
}

$adminPageTitle = 'Platform Announcements';
$activeTab = 'notifications';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">System Announcements</h1>
            <p class="text-xs text-slate-400 mt-0.5">Publish alerts, game release notices, and maintenance messages displayed to active players.</p>
        </div>
        <button type="button" data-modal-target="create-notif-modal" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create Announcement
        </button>
    </div>

    <!-- Edit Form if editing -->
    <?php if ($editingItem): ?>
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-amber-500/40 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h2 class="text-lg font-bold text-white">Edit Announcement #<?= $editingItem['id'] ?></h2>
                <a href="/admin/notifications" class="text-xs text-slate-400 hover:text-white">Cancel &times;</a>
            </div>

            <form method="POST" action="/admin/notifications" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="notif_id" value="<?= $editingItem['id'] ?>">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Title</label>
                    <input type="text" name="title" required value="<?= e($editingItem['title']) ?>"
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Notice Type</label>
                        <select name="type" class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                            <option value="info" <?= $editingItem['type'] === 'info' ? 'selected' : '' ?>>Info</option>
                            <option value="announcement" <?= $editingItem['type'] === 'announcement' ? 'selected' : '' ?>>Announcement</option>
                            <option value="warning" <?= $editingItem['type'] === 'warning' ? 'selected' : '' ?>>Warning / Maintenance</option>
                            <option value="success" <?= $editingItem['type'] === 'success' ? 'selected' : '' ?>>Success / Event</option>
                        </select>
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300 font-semibold">
                            <input type="checkbox" name="is_active" value="1" <?= $editingItem['is_active'] ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-800 text-amber-500 focus:ring-0">
                            Active (Visible to Players)
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Announcement Message</label>
                    <textarea name="message" rows="4" required
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500"><?= e($editingItem['message']) ?></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400">
                        Update Announcement
                    </button>
                    <a href="/admin/notifications" class="px-4 py-2.5 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Announcements Directory -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-white">All Platform Announcements (<?= count($items) ?>)</h2>

        <?php if (empty($items)): ?>
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-500">No announcements published yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Title</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Created Date</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= e($item['title']) ?></div>
                                    <div class="text-[11px] text-slate-400 line-clamp-1 mt-0.5"><?= e($item['message']) ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-900 border border-slate-700 text-brand-400">
                                        <?= e($item['type']) ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $item['is_active'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-500' ?>">
                                        <?= $item['is_active'] ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap"><?= format_date($item['created_at']) ?></td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Status -->
                                        <form method="POST" action="/admin/notifications" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="notif_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="current_active" value="<?= $item['is_active'] ?>">
                                            <button type="submit" class="px-2.5 py-1 rounded text-xs font-semibold <?= $item['is_active'] ? 'text-amber-400' : 'text-emerald-400' ?> hover:bg-slate-800">
                                                <?= $item['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>

                                        <a href="/admin/notifications?edit=<?= $item['id'] ?>" class="px-2.5 py-1 rounded text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700">
                                            Edit
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="/admin/notifications" class="inline" onsubmit="return confirm('Delete this announcement permanently?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notif_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1 rounded text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-950/40">
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

<!-- Modal: Create Announcement -->
<div id="create-notif-modal" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 w-full max-w-lg space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white">Create Platform Announcement</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="/admin/notifications" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Notice Title</label>
                <input type="text" name="title" required placeholder="e.g. Scheduled Engine Maintenance"
                    class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Notice Type</label>
                    <select name="type" class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                        <option value="announcement">Announcement</option>
                        <option value="info">General Info</option>
                        <option value="warning">Warning / Alert</option>
                        <option value="success">Success / Event</option>
                    </select>
                </div>

                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300 font-semibold">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded bg-slate-900 border-slate-800 text-amber-500 focus:ring-0">
                        Active Immediately
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Notice Content</label>
                <textarea name="message" rows="4" required placeholder="Enter announcement body text for players..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-amber-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" data-modal-close class="px-4 py-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400">Publish Announcement</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
