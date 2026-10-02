<?php
/**
 * Admin Panel Header Component
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/auth.php';

$siteName = get_setting('site_name', APP_NAME);
$admin = current_admin();
$adminPageTitle = isset($adminPageTitle) ? e($adminPageTitle) . ' | Admin Console' : 'Administration Console | ' . e($siteName);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $adminPageTitle ?></title>

    <!-- Compiled Local Tailwind CSS -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">

    <!-- Tailwind CDN Engine for complete utilities & styling -->
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
        /* Strict non-sticky styling */
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
<body class="bg-[#07090e] text-slate-100 font-sans antialiased">

    <?php if (!is_app_installed()): ?>
        <div class="w-full bg-gradient-to-r from-sky-600 via-brand-500 to-sky-600 text-white px-4 py-2 text-xs font-bold text-center flex items-center justify-center gap-2">
            <span>Setup Notice: Initial installation has not been locked.</span>
            <a href="/install/" class="underline hover:text-sky-100 font-extrabold">Open Setup Wizard &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- Top Admin Bar (Non-Sticky) -->
    <header class="w-full bg-[#0b0f19] border-b border-slate-800/90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Admin Brand -->
                <div class="flex items-center gap-3">
                    <a href="/admin" class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-brand-600 to-sky-400 flex items-center justify-center text-white font-black shadow-md shadow-brand-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                        </div>
                        <div>
                            <span class="text-base font-black tracking-wider text-white uppercase"><?= e($siteName) ?></span>
                            <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-brand-950/80 text-brand-400 border border-brand-800/60">
                                Admin Console
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Admin Status & User info -->
                <?php if ($admin): ?>
                    <div class="flex items-center gap-4">
                        <a href="/" target="_blank" class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            View Public Site
                        </a>
                        <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>
                        <div class="text-xs text-slate-300">
                            <span class="text-slate-500">Admin:</span> <strong><?= e($admin['name']) ?></strong>
                        </div>
                        <a href="/admin/logout" class="text-xs font-semibold text-rose-400 hover:text-rose-300 px-2 py-1">
                            Sign Out
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </header>

    <main class="w-full">
