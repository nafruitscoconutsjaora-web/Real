<?php
/**
 * Admin Panel Footer Component
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
$siteName = get_setting('site_name', APP_NAME);
?>
    </main>

    <!-- Admin Footer (Strictly Non-Sticky) -->
    <footer class="w-full bg-[#0b0f19] border-t border-slate-800/80 mt-auto py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <strong><?= e($siteName) ?></strong> &bull; Administrative Control Panel &bull; Pure PHP + MySQL
            </div>
            <div class="flex items-center gap-4">
                <span>Database Engine: <span class="text-emerald-400 font-mono">MariaDB 10.11 / InnoDB</span></span>
                <span>&bull;</span>
                <span>System Version: <span class="text-amber-400 font-mono"><?= e(APP_VERSION) ?></span></span>
            </div>
        </div>
    </footer>

    <!-- Plain JS Assets -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
