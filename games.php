<?php
/**
 * User Games Directory
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

// Real MySQL Query: Only active/enabled games
$games = [];
try {
    $stmt = $db->query("
        SELECT id, title, slug, category, description, image, thumbnail_url, status
        FROM games
        WHERE status IN ('active', 'enabled')
        ORDER BY sort_order ASC, id DESC
    ");
    $games = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Games query error: ' . $e->getMessage());
}

$pageTitle = 'Available Games';
$activeNav = 'games';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <div class="flex items-center justify-between pb-2 border-b border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                Available Games
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Select any active game to play or view module details.</p>
        </div>
        <span class="text-xs font-mono text-slate-400">
            <?= count($games) ?> <?= count($games) === 1 ? 'game' : 'games' ?> ready
        </span>
    </div>

    <?= render_flash() ?>

    <?php if (empty($games)): ?>
        <!-- Clean Empty State when no enabled games -->
        <div class="p-16 rounded-3xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-300">No games available right now.</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                Games enabled by administrators will be listed here. Please check back soon.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($games as $game): ?>
                <?php
                    $gameImg = $game['image'] ?: $game['thumbnail_url'];
                ?>
                <div class="rounded-2xl bg-[#0b0f19] border border-slate-800 hover:border-brand-500/50 transition-all flex flex-col justify-between overflow-hidden group shadow-lg">
                    
                    <!-- Game Thumbnail -->
                    <div class="relative w-full h-44 bg-gradient-to-tr from-slate-900 via-[#11192e] to-slate-900 flex items-center justify-center overflow-hidden border-b border-slate-800">
                        <?php if ($gameImg): ?>
                            <img src="<?= e($gameImg) ?>" alt="<?= e($game['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden absolute inset-0 items-center justify-center text-brand-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center gap-2 text-brand-400">
                                <div class="w-12 h-12 rounded-xl bg-brand-500/10 border border-brand-500/30 flex items-center justify-center">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                                    </svg>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= e($game['category']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Category badge overlay -->
                        <div class="absolute top-3 left-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-slate-950/80 backdrop-blur-md border border-slate-700 text-slate-200">
                                <?= e($game['category']) ?>
                            </span>
                        </div>

                        <!-- Active status badge -->
                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950/90 text-emerald-400 border border-emerald-800/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-white group-hover:text-brand-300 transition-colors">
                                <?= e($game['title']) ?>
                            </h3>
                            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                <?= e($game['description'] ?: 'Active game module on ' . get_setting('site_name', APP_NAME)) ?>
                            </p>
                        </div>

                        <!-- Card Action Button -->
                        <div class="pt-2">
                            <button type="button" data-modal-target="game-modal-<?= $game['id'] ?>" class="w-full py-2.5 rounded-xl font-bold text-xs text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all flex items-center justify-center gap-1.5">
                                Play Game &rarr;
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Modal for Game Preview / Launch Ready -->
                <div id="game-modal-<?= $game['id'] ?>" class="modal-overlay fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
                    <div class="p-6 rounded-3xl bg-[#0b0f19] border border-slate-800 w-full max-w-md space-y-5 shadow-2xl text-center">
                        <div class="w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/30 flex items-center justify-center text-brand-400 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="space-y-1">
                            <div class="text-[11px] font-bold uppercase text-brand-400 tracking-wider"><?= e($game['category']) ?></div>
                            <h3 class="text-xl font-bold text-white"><?= e($game['title']) ?></h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed pt-1">
                                <?= e($game['description'] ?: 'Game module registered in platform catalog.') ?>
                            </p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300">
                            <span class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold mb-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Module Registered & Active
                            </span>
                            <p class="text-[11px] text-slate-500">Interactive gameplay logic integration will be attached to this module in the next phase.</p>
                        </div>
                        <div class="pt-2 flex items-center justify-center gap-3">
                            <button type="button" data-modal-close class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800">
                                Close
                            </button>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
