<?php
/**
 * User Notifications & Announcements
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

$notifications = [];
try {
    $stmt = $db->query("
        SELECT n.id, n.title, n.message, n.type, n.created_at,
               COALESCE(a.name, 'System Administrator') as author_name
        FROM notifications n
        LEFT JOIN admins a ON a.id = n.created_by
        WHERE n.is_active = 1
        ORDER BY n.id DESC
    ");
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Notifications query error: ' . $e->getMessage());
}

$pageTitle = 'Platform Announcements';
$activeNav = 'notifications';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 pb-16">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Platform Announcements</h2>
            <p class="text-xs text-slate-400 mt-1">Official system notices, scheduled maintenance alerts, and game updates.</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-900 border border-slate-800 text-slate-300">
            <?= count($notifications) ?> Active <?= count($notifications) === 1 ? 'Notice' : 'Notices' ?>
        </span>
    </div>

    <?= render_flash() ?>

    <?php if (empty($notifications)): ?>
        <!-- Clean Empty State -->
        <div class="p-16 rounded-3xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
            <h3 class="text-base font-bold text-slate-300">No Announcements at Present</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">There are currently no active system notifications published by administrators. Check back later for platform updates.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($notifications as $item): ?>
                <?php
                    $border = 'border-slate-800';
                    $badgeBg = 'bg-blue-950/80 text-blue-400 border-blue-800/60';
                    if ($item['type'] === 'warning') {
                        $badgeBg = 'bg-amber-950/80 text-amber-400 border-amber-800/60';
                    } elseif ($item['type'] === 'success') {
                        $badgeBg = 'bg-emerald-950/80 text-emerald-400 border-emerald-800/60';
                    } elseif ($item['type'] === 'announcement') {
                        $badgeBg = 'bg-sky-950/80 text-sky-400 border-sky-800/60';
                    }
                ?>
                <div class="p-6 rounded-2xl bg-[#0b0f19] border <?= $border ?> hover:border-slate-700 transition-colors space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?= $badgeBg ?>">
                                <?= e($item['type']) ?>
                            </span>
                            <h3 class="text-base font-bold text-white"><?= e($item['title']) ?></h3>
                        </div>
                        <span class="text-xs text-slate-500">
                            <?= format_date($item['created_at']) ?>
                        </span>
                    </div>

                    <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed pl-1">
                        <?= e($item['message']) ?>
                    </div>

                    <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-slate-500">
                        <span>Posted by: <?= e($item['author_name']) ?></span>
                        <span class="font-mono text-slate-600">ID: #<?= $item['id'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
