<?php
/**
 * Public Landing Page
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Next-Generation Modular Gaming Platform';

// Fetch active foundation games from MySQL for preview
$db = get_db();
$games = [];
try {
    $stmt = $db->query("SELECT id, title, slug, category, description, min_bet, max_bet, status FROM games ORDER BY sort_order ASC, id ASC");
    $games = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-24 pb-20">

    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 md:pt-20">
        <!-- Background Ambient Glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-3xl mx-auto space-y-6">
                
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-brand-500/30 text-xs font-semibold text-brand-400">
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                    <span>Production Architecture &bull; Pure PHP + MySQL</span>
                </div>

                <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight">
                    The Modern Foundation for <span class="bg-gradient-to-r from-brand-400 via-sky-300 to-blue-500 bg-clip-text text-transparent">Scalable Web Games</span>
                </h1>

                <p class="text-lg sm:text-xl text-slate-300 leading-relaxed max-w-2xl mx-auto font-normal">
                    Experience an enterprise gaming platform foundation. Engineered with strict MySQL transactional ledgers, role-isolated session security, responsive dark aesthetics, and a modular game engine architecture.
                </p>

                <!-- Actions -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <?php if (is_logged_in()): ?>
                        <a href="/dashboard" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-xl shadow-brand-500/25 transition-all text-center">
                            Open Your Dashboard &rarr;
                        </a>
                        <a href="/wallet" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 transition-all text-center">
                            Manage Wallet
                        </a>
                    <?php else: ?>
                        <a href="/register" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-xl shadow-brand-500/25 transition-all text-center">
                            Create Free Player Account
                        </a>
                        <a href="/login" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 transition-all text-center">
                            Sign In to Portal
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Verified Stack Badges -->
                <div class="pt-8 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 100% Real MySQL Persistence</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Zero Framework Bloat</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> CSRF & SQL Injection Protected</span>
                </div>

            </div>
        </div>
    </section>

    <!-- Foundation Modules Showcase -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-brand-400">System Architecture</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white mt-1">Modular Game Engines</h2>
            </div>
            <p class="text-sm text-slate-400 max-w-md">
                Our core foundation isolates game execution containers from account state, allowing future game modules to connect without modifying base ledger structures.
            </p>
        </div>

        <?php if (empty($games)): ?>
            <div class="text-center py-16 px-4 rounded-2xl bg-slate-900/40 border border-slate-800">
                <svg class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <h3 class="text-lg font-bold text-slate-300">No Game Modules Registered Yet</h3>
                <p class="text-sm text-slate-500 mt-1">Admin can register new engine foundations in the management portal.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($games as $game): ?>
                    <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 hover:border-brand-500/40 transition-all flex flex-col justify-between group">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold tracking-wide uppercase bg-slate-900 border border-slate-700 text-slate-300">
                                    <?= e($game['category']) ?>
                                </span>
                                <?php if ($game['status'] === 'active'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Ready
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">
                                        Standby
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-lg font-bold text-white group-hover:text-brand-300 transition-colors">
                                <?= e($game['title']) ?>
                            </h3>

                            <p class="text-xs text-slate-400 leading-relaxed">
                                <?= e($game['description'] ?: 'Configured game architecture foundation module.') ?>
                            </p>
                        </div>

                        <div class="pt-6 mt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                            <div>
                                <span class="text-slate-500">Limits:</span>
                                <span class="font-medium text-slate-200"><?= format_money($game['min_bet']) ?> &ndash; <?= format_money($game['max_bet']) ?></span>
                            </div>
                            <span class="font-mono text-[11px] text-brand-400">Slug: <?= e($game['slug']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Core Features Section -->
    <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-xs font-bold uppercase tracking-wider text-brand-400">Platform Capabilities</span>
            <h2 class="text-3xl font-black text-white mt-1">Engineered for Reliability</h2>
            <p class="text-sm text-slate-400 mt-2">
                Every component is backed by real relational database tables and strict transaction discipline.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Feature 1 -->
            <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-brand-950/80 border border-brand-800/50 flex items-center justify-center text-brand-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Strict Auth & Isolation</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Bcrypt hashed passwords, server-enforced PHP sessions, and separate administrative controls protect sensitive user accounts.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-sky-950/80 border border-sky-800/50 flex items-center justify-center text-sky-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Immutable Ledger</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Every wallet modification creates an audited transaction record in MySQL. Balances cannot change without a corresponding ledger entry.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-blue-950/80 border border-blue-800/50 flex items-center justify-center text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Two-Way Support</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Integrated ticketing system allows players to initiate support requests and converse directly with administrators in real time.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-950/80 border border-indigo-800/50 flex items-center justify-center text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Admin Update Engine</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Admin console features a GitHub-integrated release verification system with database migration runners and automated SQL backups.
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="architecture" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-8 sm:p-12 rounded-3xl bg-[#0b0f19] border border-slate-800 relative overflow-hidden">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-400">Step-by-Step Flow</span>
                <h2 class="text-3xl font-black text-white mt-1">How the Platform Operates</h2>
                <p class="text-sm text-slate-400 mt-2">
                    A streamlined pipeline designed for seamless player onboarding and controlled administrator oversight.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Step 1 -->
                <div class="space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/40 text-brand-400 flex items-center justify-center font-black text-lg">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-white">Create Player Profile</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Register with a unique username, secure password, and verified email. Your personal MySQL wallet is automatically provisioned with a clean zero balance.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/40 text-brand-400 flex items-center justify-center font-black text-lg">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-white">Submit Deposit Request</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Request wallet credits through manual transfer reference. System records a pending transaction awaiting verification in the admin console.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/40 text-brand-400 flex items-center justify-center font-black text-lg">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-white">Launch Future Games</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Once verified, balances are accessible to upcoming game modules. Receive platform announcements and contact the support desk whenever needed.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center py-16 px-6 rounded-3xl bg-gradient-to-b from-[#0e1424] to-[#07090e] border border-blue-900/40 space-y-6">
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                Ready to Experience the Foundation?
            </h2>
            <p class="text-slate-300 max-w-xl mx-auto text-sm sm:text-base">
                Join our platform today or access the administrative console to review real database statistics, wallet management, and ticket handling.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                <a href="/register" class="px-8 py-3.5 rounded-xl font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-500/30 transition-all">
                    Register Player Account
                </a>
                <a href="/admin/login" class="px-8 py-3.5 rounded-xl font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-700 transition-all">
                    Admin Portal Login
                </a>
            </div>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
