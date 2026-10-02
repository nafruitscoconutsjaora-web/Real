<?php
/**
 * Admin Panel Navigation Component (Non-Sticky)
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/auth.php';

$activeTab = $activeTab ?? 'dashboard';

// Fetch quick counts for badges from MySQL
$db = get_db();
$pendingDeposits = 0;
$openTickets = 0;

try {
    $stmtP = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
    $pendingDeposits = (int)$stmtP->fetchColumn();

    $stmtT = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'");
    $openTickets = (int)$stmtT->fetchColumn();
} catch (Exception $e) {}
?>

<!-- Admin Navigation Bar (Strictly Non-Sticky) -->
<div class="w-full bg-[#0b0f19] border-b border-slate-800/80 mb-8 py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-1 overflow-x-auto text-xs font-semibold scrollbar-none pb-1">
            
            <a href="/admin" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'dashboard' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>

            <a href="/admin/games" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'games' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                Games
            </a>

            <a href="/admin/users" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'users' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Users & Wallets
            </a>

            <a href="/admin/transactions" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'transactions' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Transactions
                <?php if ($pendingDeposits > 0): ?>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-rose-500 text-white animate-pulse">
                        <?= $pendingDeposits ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="/admin/notifications" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'notifications' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                Announcements
            </a>

            <a href="/admin/tickets" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'tickets' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Support Desk
                <?php if ($openTickets > 0): ?>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/40">
                        <?= $openTickets ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="/admin/settings" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'settings' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Settings
            </a>

            <a href="/admin/update" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all <?= $activeTab === 'update' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                System Update
            </a>

            <a href="/admin/profile" class="flex items-center gap-2 px-3.5 py-2 rounded-xl whitespace-nowrap transition-all ml-auto <?= $activeTab === 'profile' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Admin Profile
            </a>

        </nav>
    </div>
</div>
