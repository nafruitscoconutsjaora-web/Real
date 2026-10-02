<?php
/**
 * User Navigation & Header Panel Component (Non-Sticky)
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$user = current_user();
$activeNav = $activeNav ?? 'dashboard';

// Real-time unread/open metrics for badges from MySQL
$db = get_db();
$openTicketsCount = 0;
$activeNotifCount = 0;

try {
    $stmtT = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status != 'closed'");
    $stmtT->execute([$user['id']]);
    $openTicketsCount = (int)$stmtT->fetchColumn();

    $stmtN = $db->query("SELECT COUNT(*) FROM notifications WHERE is_active = 1");
    $activeNotifCount = (int)$stmtN->fetchColumn();
} catch (Exception $e) {}
?>

<!-- User Overview Bar (Strictly Non-Sticky) -->
<div class="w-full bg-[#0b0f19] border-b border-slate-800/80 py-6 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            
            <!-- User Profile Summary -->
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-600 via-sky-600 to-blue-800 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-brand-500/20">
                    <?= strtoupper(substr($user['name'] ?: $user['username'], 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-white"><?= e($user['name'] ?: $user['username']) ?></h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                            Active Player
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @<?= e($user['username']) ?> &bull; Member since <?= format_date($user['created_at'], false) ?>
                    </p>
                </div>
            </div>

            <!-- Wallet Balance Quick Card -->
            <div class="flex items-center gap-4 bg-slate-900/90 border border-slate-800 rounded-2xl p-4 sm:px-6">
                <div>
                    <div class="text-xs font-medium text-slate-400">Total Wallet Balance</div>
                    <div class="text-2xl font-black text-brand-400 tracking-tight">
                        <?= format_money($user['balance']) ?>
                    </div>
                </div>
                <div class="pl-4 border-l border-slate-800 flex gap-2">
                    <a href="/wallet" class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                        + Add Funds
                    </a>
                </div>
            </div>

        </div>

        <!-- User Section Sub-Navigation Tabs (Non-Sticky) -->
        <nav class="flex items-center gap-2 overflow-x-auto pt-6 mt-6 border-t border-slate-800/60 pb-1 scrollbar-none text-sm font-medium">
            <a href="/dashboard" class="flex items-center gap-2 px-4 py-2.5 rounded-xl whitespace-nowrap transition-all <?= $activeNav === 'dashboard' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            
            <a href="/wallet" class="flex items-center gap-2 px-4 py-2.5 rounded-xl whitespace-nowrap transition-all <?= $activeNav === 'wallet' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Wallet & Ledger
            </a>

            <a href="/notifications" class="flex items-center gap-2 px-4 py-2.5 rounded-xl whitespace-nowrap transition-all <?= $activeNav === 'notifications' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                Announcements
                <?php if ($activeNotifCount > 0): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/40">
                        <?= $activeNotifCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="/support" class="flex items-center gap-2 px-4 py-2.5 rounded-xl whitespace-nowrap transition-all <?= $activeNav === 'support' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Support Tickets
                <?php if ($openTicketsCount > 0): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">
                        <?= $openTicketsCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="/profile" class="flex items-center gap-2 px-4 py-2.5 rounded-xl whitespace-nowrap transition-all <?= $activeNav === 'profile' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profile & Security
            </a>

            <a href="/logout" class="ml-auto flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-950/30 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </a>
        </nav>
    </div>
</div>
