<?php
/**
 * User Dashboard
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$db = get_db();

// 1. Fetch real user statistics from MySQL
$totalTransactions = 0;
$totalDeposited = 0.00;
$openTicketsCount = 0;
$recentTransactions = [];
$latestAnnouncements = [];

try {
    // Total transactions count
    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalTransactions = (int)$stmt->fetchColumn();

    // Total completed deposits
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0.00) FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed'");
    $stmt->execute([$user['id']]);
    $totalDeposited = (float)$stmt->fetchColumn();

    // Open tickets
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status != 'closed'");
    $stmt->execute([$user['id']]);
    $openTicketsCount = (int)$stmt->fetchColumn();

    // Recent transactions (limit 5)
    $stmt = $db->prepare("
        SELECT id, transaction_id, type, amount, status, payment_method, payment_reference, created_at
        FROM transactions
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $recentTransactions = $stmt->fetchAll();

    // Latest active announcements (limit 3)
    $stmt = $db->query("
        SELECT id, title, message, type, created_at
        FROM notifications
        WHERE is_active = 1
        ORDER BY id DESC
        LIMIT 3
    ");
    $latestAnnouncements = $stmt->fetchAll();

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

    <!-- Real Statistics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Balance Card -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Available Balance</span>
                <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            </div>
            <div class="text-2xl font-black text-white"><?= format_money($user['balance']) ?></div>
            <div class="text-[11px] text-slate-500">Stored securely in MySQL ledger</div>
        </div>

        <!-- Total Deposited -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Total Verified Deposits</span>
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-emerald-400"><?= format_money($totalDeposited) ?></div>
            <div class="text-[11px] text-slate-500">Completed funding operations</div>
        </div>

        <!-- Total Transactions -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Total Ledger Records</span>
                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div class="text-2xl font-black text-white"><?= $totalTransactions ?></div>
            <div class="text-[11px] text-slate-500">Audited transaction rows</div>
        </div>

        <!-- Open Tickets -->
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="font-medium">Open Support Tickets</span>
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-amber-400"><?= $openTicketsCount ?></div>
            <div class="text-[11px] text-slate-500">Awaiting resolution or reply</div>
        </div>

    </div>

    <!-- Quick Shortcuts Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-[#0d1527] to-slate-900 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="space-y-1 text-center md:text-left">
            <h3 class="text-base font-bold text-white">Need to fund your gaming account?</h3>
            <p class="text-xs text-slate-400">Submit a deposit request with your payment reference for immediate administrator verification.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/wallet" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                Submit Deposit Request
            </a>
            <a href="/support" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 transition-all">
                Contact Desk
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Transactions (2 cols on large screens) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Recent Transactions
                </h2>
                <a href="/wallet" class="text-xs font-semibold text-brand-400 hover:underline">View All &rarr;</a>
            </div>

            <?php if (empty($recentTransactions)): ?>
                <!-- Clean Empty State -->
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-300">No Transactions Recorded Yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">You have not submitted any wallet deposits or adjustments yet. All future transactions will be recorded here.</p>
                    <a href="/wallet" class="inline-block mt-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 transition-all">
                        Make First Deposit
                    </a>
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

        <!-- Latest Announcements Preview (1 col) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Platform Notices
                </h2>
                <a href="/notifications" class="text-xs font-semibold text-brand-400 hover:underline">All Notices &rarr;</a>
            </div>

            <?php if (empty($latestAnnouncements)): ?>
                <!-- Clean Empty State -->
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                    <p class="text-xs text-slate-500">No active system announcements currently published.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($latestAnnouncements as $notif): ?>
                        <div class="p-4 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
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
