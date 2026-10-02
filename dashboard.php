<?php
/**
 * User Gaming Dashboard
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

// 1. Fetch real user and platform metrics from MySQL
$availableGamesCount = 0;
$totalTransactions = 0;
$recentTransactions = [];
$activeGames = [];
$latestAnnouncements = [];

try {
    // Count of active games
    $stmtG = $db->query("SELECT COUNT(*) FROM games WHERE status IN ('active', 'enabled')");
    $availableGamesCount = (int)$stmtG->fetchColumn();

    // Fetch up to 3 active games for quick play showcase
    $stmtActive = $db->query("
        SELECT id, title, slug, category, description, image, thumbnail_url
        FROM games
        WHERE status IN ('active', 'enabled')
        ORDER BY sort_order ASC, id DESC
        LIMIT 3
    ");
    $activeGames = $stmtActive->fetchAll();

    // Total transactions count
    $stmtTxCount = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmtTxCount->execute([$user['id']]);
    $totalTransactions = (int)$stmtTxCount->fetchColumn();

    // Recent transactions (limit 5)
    $stmtTx = $db->prepare("
        SELECT id, transaction_id, type, amount, status, payment_method, payment_reference, created_at
        FROM transactions
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmtTx->execute([$user['id']]);
    $recentTransactions = $stmtTx->fetchAll();

    // Latest active announcements (limit 3)
    $stmtNotif = $db->query("
        SELECT id, title, message, type, created_at
        FROM notifications
        WHERE is_active = 1
        ORDER BY id DESC
        LIMIT 3
    ");
    $latestAnnouncements = $stmtNotif->fetchAll();

} catch (Exception $e) {
    error_log('Dashboard data query error: ' . $e->getMessage());
}

$pageTitle = 'Player Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Player Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Wallet Balance Card -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Wallet Balance</span>
                <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            </div>
            <div class="text-2xl font-black text-brand-400"><?= format_money($user['balance']) ?></div>
            <div class="text-[11px] text-slate-500">Available to play</div>
        </div>

        <!-- Available Games Count -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Active Games</span>
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
            </div>
            <div class="text-2xl font-black text-white"><?= $availableGamesCount ?></div>
            <div class="text-[11px] text-slate-500">Ready in game library</div>
        </div>

        <!-- Total Transactions -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Total Transactions</span>
                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div class="text-2xl font-black text-white"><?= $totalTransactions ?></div>
            <div class="text-[11px] text-slate-500">Account records</div>
        </div>

        <!-- Announcements Count -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Platform Notices</span>
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
            <div class="text-2xl font-black text-amber-400"><?= count($latestAnnouncements) ?></div>
            <div class="text-[11px] text-slate-500">Recent announcements</div>
        </div>

    </div>

    <!-- Featured Games Section on Dashboard -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                Active Games
            </h2>
            <a href="/games" class="text-xs font-semibold text-brand-400 hover:underline">View All Games (<?= $availableGamesCount ?>) &rarr;</a>
        </div>

        <?php if (empty($activeGames)): ?>
            <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-400 font-medium">No games available right now.</p>
                <p class="text-[11px] text-slate-500">Games enabled by administrators will appear here.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($activeGames as $g): ?>
                    <?php $gImg = $g['image'] ?: $g['thumbnail_url']; ?>
                    <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 hover:border-brand-500/50 transition-all flex flex-col justify-between space-y-4 group">
                        <div class="space-y-3">
                            <div class="h-32 rounded-xl bg-slate-900 border border-slate-800 overflow-hidden flex items-center justify-center relative">
                                <?php if ($gImg): ?>
                                    <img src="<?= e($gImg) ?>" alt="<?= e($g['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div class="text-brand-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                                    </div>
                                <?php endif; ?>
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-950/80 text-slate-300">
                                    <?= e($g['category']) ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white group-hover:text-brand-300 transition-colors"><?= e($g['title']) ?></h3>
                                <p class="text-xs text-slate-400 line-clamp-1 mt-0.5"><?= e($g['description'] ?: 'Active game module') ?></p>
                            </div>
                        </div>

                        <a href="/games" class="w-full py-2 rounded-xl font-bold text-xs text-white bg-brand-600 hover:bg-brand-500 transition-all text-center block">
                            Play &rarr;
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity & Notices Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Transactions (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Recent Transactions
                </h2>
                <a href="/wallet" class="text-xs font-semibold text-brand-400 hover:underline">View Wallet &rarr;</a>
            </div>

            <?php if (empty($recentTransactions)): ?>
                <!-- Clean Empty State -->
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">No transactions yet.</p>
                    <a href="/wallet" class="inline-block text-xs font-semibold text-brand-400 hover:underline">Make a deposit to get started</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/60 font-semibold">
                            <tr>
                                <th class="p-4">Reference</th>
                                <th class="p-4">Type</th>
                                <th class="p-4">Amount</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <td class="p-4 font-mono font-medium text-slate-200">
                                        <?= e($tx['transaction_id']) ?>
                                    </td>
                                    <td class="p-4 capitalize">
                                        <?= e(str_replace('_', ' ', $tx['type'])) ?>
                                    </td>
                                    <td class="p-4 font-bold <?= $tx['type'] === 'withdrawal' ? 'text-rose-400' : 'text-emerald-400' ?>">
                                        <?= $tx['type'] === 'withdrawal' ? '-' : '+' ?><?= format_money($tx['amount']) ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($tx['status'] === 'completed'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                                                Completed
                                            </span>
                                        <?php elseif ($tx['status'] === 'pending'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-950/80 text-amber-400 border border-amber-800/60">
                                                Pending Review
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-950/80 text-rose-400 border border-rose-800/60">
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-slate-500 whitespace-nowrap">
                                        <?= format_date($tx['created_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Latest Announcements (1 col) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Announcements
                </h2>
                <a href="/notifications" class="text-xs font-semibold text-brand-400 hover:underline">All &rarr;</a>
            </div>

            <?php if (empty($latestAnnouncements)): ?>
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-1">
                    <p class="text-xs text-slate-400 font-medium">No notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($latestAnnouncements as $notif): ?>
                        <div class="p-4 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="px-2 py-0.5 rounded uppercase font-semibold text-[10px] bg-slate-900 border border-slate-700 text-brand-400">
                                    <?= e($notif['type']) ?>
                                </span>
                                <span class="text-slate-500"><?= format_date($notif['created_at'], false) ?></span>
                            </div>
                            <h3 class="text-sm font-bold text-white"><?= e($notif['title']) ?></h3>
                            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                <?= e($notif['message']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
