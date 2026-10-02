<?php
/**
 * Public Landing Page - Game Platform
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$siteName = get_setting('site_name', APP_NAME);
$pageTitle = 'Play Online Games';

// Real MySQL Query: ONLY show enabled/active games
$db = get_db();
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
    error_log('Landing page games query error: ' . $e->getMessage());
}

$isAuth = is_logged_in();

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-16 pb-16">

    <!-- Compact Gaming Hero Section -->
    <section class="relative overflow-hidden pt-8 md:pt-14">
        <!-- Background Ambient Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-brand-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center space-y-5">
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                Play Instantly on <span class="bg-gradient-to-r from-brand-400 via-sky-300 to-blue-500 bg-clip-text text-transparent"><?= e($siteName) ?></span>
            </h1>

            <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto leading-relaxed">
                Explore our collection of online games, manage your wallet balance seamlessly, and jump straight into the action.
            </p>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <?php if ($isAuth): ?>
                    <a href="/dashboard" class="px-6 py-3 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all">
                        Go to Dashboard &rarr;
                    </a>
                    <a href="/games" class="px-6 py-3 rounded-xl font-semibold text-xs text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700 transition-all">
                        Browse Games
                    </a>
                <?php else: ?>
                    <a href="/register" class="px-6 py-3 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all">
                        Create Player Account
                    </a>
                    <a href="/login" class="px-6 py-3 rounded-xl font-semibold text-xs text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700 transition-all">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- MAIN SECTION: Available Games -->
    <section id="games" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800/80">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full bg-brand-500"></span>
                    Available Games
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Select a game to start playing or view details.</p>
            </div>
            <span class="text-xs font-mono text-slate-400">
                <?= count($games) ?> <?= count($games) === 1 ? 'game' : 'games' ?> online
            </span>
        </div>

        <?php if (empty($games)): ?>
            <!-- Strict Empty State when no games are enabled -->
            <div class="p-16 rounded-3xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-300">No games available right now.</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    New games added and enabled by the administration will appear here immediately.
                </p>
            </div>
        <?php else: ?>
            <!-- Real Games Grid from MySQL -->
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
                                    <?= e($game['description'] ?: 'Exciting game module on ' . $siteName) ?>
                                </p>
                            </div>

                            <!-- Card Action Button -->
                            <div class="pt-2">
                                <?php if ($isAuth): ?>
                                    <button type="button" data-modal-target="game-modal-<?= $game['id'] ?>" class="w-full py-2.5 rounded-xl font-bold text-xs text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all flex items-center justify-center gap-1.5">
                                        Play Now &rarr;
                                    </button>
                                <?php else: ?>
                                    <a href="/login" class="w-full py-2.5 rounded-xl font-bold text-xs text-slate-300 hover:text-white bg-slate-800/90 hover:bg-slate-700 transition-all text-center block">
                                        Login to Play &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Modal for Game Preview / Launch Ready (Respects auth without fake game logic) -->
                    <?php if ($isAuth): ?>
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
                                        Back to Games
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Compact "How It Works" Section -->
    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div class="text-center max-w-md mx-auto space-y-1">
                <h3 class="text-lg font-black text-white">How It Works</h3>
                <p class="text-xs text-slate-400">Get started in three quick steps</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="w-8 h-8 rounded-full bg-brand-600/20 text-brand-400 border border-brand-500/40 flex items-center justify-center font-bold text-xs mx-auto">1</div>
                    <h4 class="text-xs font-bold text-white">Create Account</h4>
                    <p class="text-[11px] text-slate-400">Register with your email and username in seconds.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="w-8 h-8 rounded-full bg-brand-600/20 text-brand-400 border border-brand-500/40 flex items-center justify-center font-bold text-xs mx-auto">2</div>
                    <h4 class="text-xs font-bold text-white">Fund Wallet</h4>
                    <p class="text-[11px] text-slate-400">Add funds using our simple deposit request system.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="w-8 h-8 rounded-full bg-brand-600/20 text-brand-400 border border-brand-500/40 flex items-center justify-center font-bold text-xs mx-auto">3</div>
                    <h4 class="text-xs font-bold text-white">Play Games</h4>
                    <p class="text-[11px] text-slate-400">Browse active games and join your favorite sessions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Compact Call-to-Action -->
    <?php if (!$isAuth): ?>
        <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="p-8 rounded-3xl bg-gradient-to-r from-blue-950/40 via-slate-900 to-blue-950/40 border border-blue-900/40 space-y-4">
                <h3 class="text-xl font-bold text-white">Ready to Start Playing?</h3>
                <p class="text-xs text-slate-300 max-w-md mx-auto">Sign up for free today and explore our catalog of active games.</p>
                <div class="pt-2 flex justify-center gap-3">
                    <a href="/register" class="px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                        Register Now
                    </a>
                    <a href="/login" class="px-6 py-2.5 rounded-xl font-semibold text-xs text-slate-300 bg-slate-800 hover:bg-slate-700 transition-all">
                        Sign In
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
