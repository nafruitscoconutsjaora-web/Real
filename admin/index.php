<?php
/**
 * Admin Gaming Platform Dashboard
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

// 1. Fetch Real MySQL Platform Statistics
$totalUsers = 0;
$activeUsers = 0;
$totalGames = 0;
$activeGames = 0;
$totalDeposits = 0.00;
$openTicketsCount = 0;

$recentUsers = [];
$recentTransactions = [];
$openTickets = [];

try {
    // Total Users
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = (int)$stmt->fetchColumn();

    // Active Users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $activeUsers = (int)$stmt->fetchColumn();

    // Total Games
    $stmt = $db->query("SELECT COUNT(*) FROM games");
    $totalGames = (int)$stmt->fetchColumn();

    // Active Games
    $stmt = $db->query("SELECT COUNT(*) FROM games WHERE status IN ('active', 'enabled')");
    $activeGames = (int)$stmt->fetchColumn();

    // Total Approved Deposits
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0.00) FROM transactions WHERE type = 'deposit' AND status = 'completed'");
    $totalDeposits = (float)$stmt->fetchColumn();

    // Open Tickets
    $stmt = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'");
    $openTicketsCount = (int)$stmt->fetchColumn();

    // Recent 5 Users
    $stmt = $db->query("
        SELECT u.id, u.name, u.username, u.email, u.status, u.created_at,
               COALESCE(w.balance, 0.00) as balance
        FROM users u
        LEFT JOIN wallets w ON w.user_id = u.id
        ORDER BY u.id DESC
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();

    // Recent 5 Transactions
    $stmt = $db->query("
        SELECT t.id, t.transaction_id, t.type, t.amount, t.status, t.payment_method, t.created_at,
               u.username as player_username
        FROM transactions t
        JOIN users u ON u.id = t.user_id
        ORDER BY t.id DESC
        LIMIT 5
    ");
    $recentTransactions = $stmt->fetchAll();

    // Open Support Tickets (limit 5)
    $stmt = $db->query("
        SELECT t.id, t.ticket_number, t.subject, t.priority, t.status, t.created_at,
               u.username as player_username
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id
        WHERE t.status != 'closed'
        ORDER BY t.id DESC
        LIMIT 5
    ");
    $openTickets = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Admin dashboard query error: ' . $e->getMessage());
}

$adminPageTitle = 'Platform Dashboard';
$activeTab = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Platform Dashboard</h1>
            <p class="text-xs text-slate-400 mt-0.5">Overview of active players, game catalog, financial volume, and pending support inquiries.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/games" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors">
                Manage Games &rarr;
            </a>
        </div>
    </div>

    <!-- Metrics Cards (Real Statistics as requested) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        
        <!-- Total Users -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Total Users</div>
            <div class="text-2xl font-black text-white"><?= number_format($totalUsers) ?></div>
            <div class="text-[11px] text-slate-500">Registered players</div>
        </div>

        <!-- Active Users -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Active Users</div>
            <div class="text-2xl font-black text-emerald-400"><?= number_format($activeUsers) ?></div>
            <div class="text-[11px] text-slate-500">Status = Active</div>
        </div>

        <!-- Total Games -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Total Games</div>
            <div class="text-2xl font-black text-white"><?= number_format($totalGames) ?></div>
            <div class="text-[11px] text-slate-500">In database catalog</div>
        </div>

        <!-- Active Games -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Active Games</div>
            <div class="text-2xl font-black text-brand-400"><?= number_format($activeGames) ?></div>
            <div class="text-[11px] text-slate-500">Live on game catalog</div>
        </div>

        <!-- Total Deposits -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Total Deposits</div>
            <div class="text-2xl font-black text-sky-400"><?= format_money($totalDeposits) ?></div>
            <div class="text-[11px] text-slate-500">Completed volume</div>
        </div>

        <!-- Open Support Tickets -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-1.5">
            <div class="text-[11px] text-slate-400 uppercase font-semibold tracking-wider">Open Tickets</div>
            <div class="text-2xl font-black text-amber-400"><?= number_format($openTicketsCount) ?></div>
            <div class="text-[11px] text-slate-500">Pending reply</div>
        </div>

    </div>

    <!-- Quick Operations Toolbar -->
    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-3">
            <span class="font-semibold text-slate-300">Quick Links:</span>
            <a href="/admin/games" class="text-amber-400 hover:underline">Game Catalog &rarr;</a>
            <span class="text-slate-700">&bull;</span>
            <a href="/admin/transactions" class="text-amber-400 hover:underline">Pending Deposits &rarr;</a>
            <span class="text-slate-700">&bull;</span>
            <a href="/admin/tickets" class="text-amber-400 hover:underline">Support Inquiries &rarr;</a>
        </div>
        <div class="text-slate-500 font-mono text-[11px]">
            Server Time: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Recent Users Table -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Recent Users
                </h2>
                <a href="/admin/users" class="text-xs text-amber-400 hover:underline">All Users &rarr;</a>
            </div>

            <?php if (empty($recentUsers)): ?>
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-1">
                    <p class="text-xs text-slate-500 font-medium">No users yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                            <tr>
                                <th class="p-3.5">User</th>
                                <th class="p-3.5">Email</th>
                                <th class="p-3.5">Balance</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <?php foreach ($recentUsers as $u): ?>
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <td class="p-3.5">
                                        <div class="font-bold text-white"><?= e($u['name']) ?></div>
                                        <div class="font-mono text-[11px] text-slate-500">@<?= e($u['username']) ?></div>
                                    </td>
                                    <td class="p-3.5 text-slate-400"><?= e($u['email']) ?></td>
                                    <td class="p-3.5 font-bold text-brand-400"><?= format_money($u['balance']) ?></td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase <?= $u['status'] === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800' ?>">
                                            <?= e($u['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-slate-500 whitespace-nowrap"><?= format_date($u['created_at'], false) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions Table -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Recent Transactions
                </h2>
                <a href="/admin/transactions" class="text-xs text-amber-400 hover:underline">All Transactions &rarr;</a>
            </div>

            <?php if (empty($recentTransactions)): ?>
                <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-1">
                    <p class="text-xs text-slate-500 font-medium">No transactions yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                            <tr>
                                <th class="p-3.5">Ref / User</th>
                                <th class="p-3.5">Type</th>
                                <th class="p-3.5">Amount</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <td class="p-3.5">
                                        <div class="font-mono font-bold text-white"><?= e($tx['transaction_id']) ?></div>
                                        <div class="text-[11px] text-slate-500">@<?= e($tx['player_username']) ?></div>
                                    </td>
                                    <td class="p-3.5 uppercase text-[10px] font-semibold text-slate-400"><?= e(str_replace('_', ' ', $tx['type'])) ?></td>
                                    <td class="p-3.5 font-bold <?= $tx['type'] === 'withdrawal' ? 'text-rose-400' : 'text-emerald-400' ?>">
                                        <?= format_money($tx['amount']) ?>
                                    </td>
                                    <td class="p-3.5">
                                        <?php if ($tx['status'] === 'completed'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Approved</span>
                                        <?php elseif ($tx['status'] === 'pending'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800">Pending</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3.5 text-slate-500 whitespace-nowrap"><?= format_date($tx['created_at'], false) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Open Tickets Quick List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Open Support Tickets
            </h2>
            <a href="/admin/tickets" class="text-xs text-amber-400 hover:underline">All Tickets &rarr;</a>
        </div>

        <?php if (empty($openTickets)): ?>
            <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-1">
                <p class="text-xs text-slate-500 font-medium">No support tickets yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($openTickets as $ticket): ?>
                    <a href="/admin/tickets?open=<?= $ticket['id'] ?>" class="p-4 rounded-2xl bg-[#0b0f19] border border-slate-800 hover:border-amber-500/40 transition-colors space-y-2 block">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono text-amber-400 font-bold"><?= e($ticket['ticket_number']) ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                                <?= e($ticket['priority']) ?>
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-white line-clamp-1"><?= e($ticket['subject']) ?></h3>
                        <div class="text-[11px] text-slate-500 flex items-center justify-between">
                            <span>From: @<?= e($ticket['player_username']) ?></span>
                            <span><?= format_date($ticket['created_at'], false) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
