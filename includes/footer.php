<?php
/**
 * Global Footer Component - Simple & Gaming Focused
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$siteName = get_setting('site_name', APP_NAME);
$contactEmail = get_setting('contact_email', 'support@nexusgaming.local');
?>
    </main>

    <!-- Global Footer (Non-Sticky Flow) -->
    <footer class="w-full bg-[#0b0f19] border-t border-slate-800/80 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8 border-b border-slate-800/60">
                <!-- Brand Info -->
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                        </svg>
                    </div>
                    <span class="text-base font-bold text-white uppercase tracking-wider"><?= e($siteName) ?></span>
                </div>

                <!-- Basic Navigation -->
                <nav class="flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400 font-medium">
                    <a href="/" class="hover:text-brand-400 transition-colors">Home</a>
                    <a href="/games" class="hover:text-brand-400 transition-colors">Games</a>
                    <a href="/support" class="hover:text-brand-400 transition-colors">Support</a>
                    <button type="button" data-modal-target="modal-terms" class="hover:text-brand-400 transition-colors">Terms of Service</button>
                    <button type="button" data-modal-target="modal-privacy" class="hover:text-brand-400 transition-colors">Privacy Policy</button>
                    <a href="/admin/login" class="hover:text-amber-400 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Admin
                    </a>
                </nav>
            </div>

            <!-- Bottom Line -->
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
                <div>
                    Support: <a href="mailto:<?= e($contactEmail) ?>" class="text-slate-400 hover:text-brand-400 transition-colors"><?= e($contactEmail) ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Terms Modal -->
    <div id="modal-terms" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 w-full max-w-lg space-y-4 shadow-2xl text-left">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Terms of Service</h3>
                <button type="button" data-modal-close class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <div class="text-xs text-slate-300 space-y-2 leading-relaxed max-h-72 overflow-y-auto pr-1">
                <p>By creating an account and participating on <?= e($siteName) ?>, you agree to the following conditions:</p>
                <p>1. <strong>Account Responsibility:</strong> Each player is responsible for safeguarding their login credentials and account access.</p>
                <p>2. <strong>Fair Play:</strong> Any attempted exploitation or interference with platform game listings, wallet transactions, or server operations is strictly prohibited.</p>
                <p>3. <strong>Wallet Management:</strong> Balances credited to user accounts reflect verified funding transactions and may only be utilized for platform activities.</p>
            </div>
            <div class="pt-2 text-right">
                <button type="button" data-modal-close class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700">Close</button>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="modal-privacy" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 w-full max-w-lg space-y-4 shadow-2xl text-left">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Privacy Policy</h3>
                <button type="button" data-modal-close class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <div class="text-xs text-slate-300 space-y-2 leading-relaxed max-h-72 overflow-y-auto pr-1">
                <p><?= e($siteName) ?> respects user privacy and data security:</p>
                <p>1. <strong>Data Collection:</strong> We collect only necessary account registration details (username, name, email) required to provide gaming services.</p>
                <p>2. <strong>Account Protection:</strong> Passwords are cryptographically hashed and never stored in plain text.</p>
                <p>3. <strong>Data Sharing:</strong> We do not sell or distribute personal player data to external marketing entities.</p>
            </div>
            <div class="pt-2 text-right">
                <button type="button" data-modal-close class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700">Close</button>
            </div>
        </div>
    </div>

    <!-- Plain JavaScript Assets -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
