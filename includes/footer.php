<?php
/**
 * Global Footer Component
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$siteName = get_setting('site_name', APP_NAME);
$footerText = get_setting('footer_text', '© 2026 NexusGaming Platform. Built with PHP, MySQL & Tailwind CSS.');
$contactEmail = get_setting('contact_email', 'support@nexusgaming.local');
?>
    </main>

    <!-- Global Footer (Non-Sticky Flow) -->
    <footer class="w-full bg-[#0b0f19] border-t border-slate-800/80 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
                <!-- Brand Info -->
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white uppercase tracking-wider"><?= e($siteName) ?></span>
                    </div>
                    <p class="text-sm text-slate-400 max-w-md leading-relaxed">
                        Production-grade modular gaming foundation built entirely with pure PHP, Tailwind CSS, and MySQL persistence. Engineered for scalable game engine integrations and real-time wallet operations.
                    </p>
                    <div class="flex items-center gap-3 text-xs text-slate-400">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-800 border border-slate-700 font-mono text-brand-400">
                            PHP 8.2 + MySQL
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-800 border border-slate-700 font-mono text-sky-400">
                            Tailwind CSS
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-950/60 border border-emerald-800/60 text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> DB Online
                        </span>
                    </div>
                </div>

                <!-- Navigation -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300 mb-4">Navigation</h3>
                    <ul class="space-y-2.5 text-sm text-slate-400">
                        <li><a href="/" class="hover:text-brand-400 transition-colors">Platform Home</a></li>
                        <li><a href="/#features" class="hover:text-brand-400 transition-colors">Core Features</a></li>
                        <li><a href="/#architecture" class="hover:text-brand-400 transition-colors">Architecture</a></li>
                        <li><a href="/support" class="hover:text-brand-400 transition-colors">Support Desk</a></li>
                    </ul>
                </div>

                <!-- Portal Links & Admin -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300 mb-4">Portals</h3>
                    <ul class="space-y-2.5 text-sm text-slate-400">
                        <li><a href="/dashboard" class="hover:text-brand-400 transition-colors">Player Dashboard</a></li>
                        <li><a href="/wallet" class="hover:text-brand-400 transition-colors">Player Wallet</a></li>
                        <li><a href="/notifications" class="hover:text-brand-400 transition-colors">Platform Notices</a></li>
                        <li><a href="/admin/login" class="text-slate-400 hover:text-amber-400 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Admin Console
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Line -->
            <div class="pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p><?= e($footerText) ?></p>
                <div class="flex items-center gap-4">
                    <span>Contact: <a href="mailto:<?= e($contactEmail) ?>" class="text-brand-400 hover:underline"><?= e($contactEmail) ?></a></span>
                    <span>&bull;</span>
                    <span>Version <?= e(APP_VERSION) ?></span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Plain JavaScript Assets -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
