<?php
/**
 * Global Header Component
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$siteName = get_setting('site_name', APP_NAME);
$siteTagline = get_setting('site_tagline', 'Modular Web Gaming Architecture');
$logoText = get_setting('logo_text', 'NEXUS GAMING');
$currentUser = current_user();
$pageTitle = isset($pageTitle) ? e($pageTitle) . ' | ' . e($siteName) : e($siteName) . ' — ' . e($siteTagline);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="Production-ready game website foundation powered by PHP, MySQL, and Tailwind CSS with secure authentication, wallet management, support tickets, notifications, and modular admin control panel.">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="Production-ready game website foundation powered by PHP, MySQL, and Tailwind CSS.">
    <meta property="og:type" content="website">

    <!-- Compiled Local Tailwind CSS -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">

    <!-- Tailwind CSS CDN Engine for complete on-the-fly utilities and dark-blue theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#07090e',
                            900: '#0b0f19',
                            850: '#0e1320',
                            800: '#111827',
                            750: '#151d30',
                            700: '#1e293b',
                            600: '#334155'
                        },
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            glow: '#00d2ff'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Strict non-sticky styling as required */
        body {
            background-color: #07090e;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
    </style>
</head>
<body class="bg-[#07090e] text-slate-100 font-sans antialiased selection:bg-brand-500 selection:text-white">

    <?php if (!is_app_installed()): ?>
        <div class="w-full bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 px-4 py-2 text-xs font-bold text-center flex items-center justify-center gap-2">
            <span>Platform Setup: System installer is available to configure database and administrator credentials.</span>
            <a href="/install/" class="underline hover:text-black font-extrabold">Launch Setup Wizard &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- Header Navigation (Strictly Non-Sticky) -->
    <header class="w-full bg-[#0b0f19] border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo & Brand Name -->
                <div class="flex items-center gap-3">
                    <a href="/" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-700 via-brand-500 to-sky-400 flex items-center justify-center shadow-lg shadow-brand-500/20 group-hover:shadow-brand-500/40 transition-all">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-black tracking-wider text-white uppercase group-hover:text-brand-400 transition-colors">
                                <?= e($logoText) ?>
                            </span>
                            <span class="hidden sm:block text-[10px] font-semibold tracking-widest uppercase text-brand-400/80">Foundation</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <a href="/" class="text-slate-300 hover:text-brand-400 transition-colors">Home</a>
                    <a href="/#features" class="text-slate-300 hover:text-brand-400 transition-colors">Features</a>
                    <a href="/#architecture" class="text-slate-300 hover:text-brand-400 transition-colors">How It Works</a>
                    <?php if ($currentUser): ?>
                        <a href="/dashboard" class="text-slate-300 hover:text-brand-400 transition-colors">Dashboard</a>
                        <a href="/wallet" class="text-slate-300 hover:text-brand-400 transition-colors">Wallet</a>
                        <a href="/notifications" class="text-slate-300 hover:text-brand-400 transition-colors">Announcements</a>
                        <a href="/support" class="text-slate-300 hover:text-brand-400 transition-colors">Support</a>
                    <?php endif; ?>
                </nav>

                <!-- Auth Buttons / User Balance -->
                <div class="hidden md:flex items-center gap-4">
                    <?php if ($currentUser): ?>
                        <div class="flex items-center gap-3 bg-slate-900/90 border border-slate-800 px-3.5 py-1.5 rounded-xl">
                            <div class="text-xs text-slate-400">Balance:</div>
                            <div class="text-sm font-bold text-brand-400"><?= format_money($currentUser['balance']) ?></div>
                        </div>
                        <a href="/profile" class="flex items-center gap-2 text-sm text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 px-3.5 py-2 rounded-xl transition-all">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span><?= e($currentUser['username']) ?></span>
                        </a>
                        <a href="/logout" class="text-xs text-rose-400 hover:text-rose-300 font-semibold px-2 py-1">Logout</a>
                    <?php else: ?>
                        <a href="/login" class="text-sm font-semibold text-slate-300 hover:text-white px-4 py-2 rounded-xl hover:bg-slate-800/60 transition-all">
                            Sign In
                        </a>
                        <a href="/register" class="text-sm font-semibold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 px-5 py-2.5 rounded-xl shadow-lg shadow-brand-500/25 transition-all">
                            Create Account
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden items-center gap-3">
                    <?php if ($currentUser): ?>
                        <div class="text-xs font-bold text-brand-400 bg-slate-900 border border-slate-800 px-2.5 py-1 rounded-lg">
                            <?= format_money($currentUser['balance']) ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" id="mobile-menu-btn" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors" aria-expanded="false" aria-label="Toggle navigation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Navigation Menu (Non-Sticky Flow) -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-800/80 bg-[#0b0f19] px-4 pt-3 pb-6 space-y-2">
            <a href="/" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Home</a>
            <a href="/#features" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Features</a>
            <a href="/#architecture" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">How It Works</a>
            
            <?php if ($currentUser): ?>
                <div class="pt-2 border-t border-slate-800/60 my-2"></div>
                <div class="px-3 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">User Account</div>
                <a href="/dashboard" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Dashboard</a>
                <a href="/wallet" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Wallet (<?= format_money($currentUser['balance']) ?>)</a>
                <a href="/notifications" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Announcements</a>
                <a href="/support" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Support Tickets</a>
                <a href="/profile" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800/50">Profile Settings</a>
                <a href="/logout" class="block px-3 py-2 rounded-lg text-base font-semibold text-rose-400 hover:bg-slate-800/50">Log Out</a>
            <?php else: ?>
                <div class="pt-3 border-t border-slate-800 flex flex-col gap-2">
                    <a href="/login" class="text-center w-full py-2.5 text-sm font-semibold text-slate-200 bg-slate-800/90 rounded-xl">Sign In</a>
                    <a href="/register" class="text-center w-full py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-xl">Create Account</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="w-full">
