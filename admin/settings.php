<?php
/**
 * Admin System Settings
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/settings');
    }

    $settingsKeys = [
        'site_name',
        'logo_text',
        'site_tagline',
        'contact_email',
        'contact_phone',
        'currency_symbol',
        'min_deposit',
        'max_deposit',
        'deposit_instructions',
        'footer_text',
        'maintenance_mode'
    ];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");

        foreach ($settingsKeys as $key) {
            $val = trim($_POST[$key] ?? '');
            if ($key === 'maintenance_mode') {
                $val = !empty($_POST['maintenance_mode']) ? '1' : '0';
            }
            $stmt->execute([$key, $val]);
        }

        $db->commit();
        set_flash('success', 'Site configuration settings updated successfully in MySQL.');
        redirect('/admin/settings');

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Settings update error: ' . $e->getMessage());
        set_flash('error', 'Failed to save settings: ' . $e->getMessage());
        redirect('/admin/settings');
    }
}

// Reload current settings from DB
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$adminPageTitle = 'Platform System Settings';
$activeTab = 'settings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div>
        <h1 class="text-2xl font-black text-white">Platform System Settings</h1>
        <p class="text-xs text-slate-400 mt-0.5">Control dynamic website branding, contact parameters, currency symbol, and payment thresholds.</p>
    </div>

    <form method="POST" action="/admin/settings" class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
        <?= csrf_field() ?>

        <div class="space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-brand-400 border-b border-slate-800 pb-2">
                Branding & Identity
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Site Name</label>
                    <input type="text" name="site_name" required value="<?= e($settings['site_name'] ?? 'NexusGaming') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Loaded dynamically throughout site navigation and headers</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Logo Text</label>
                    <input type="text" name="logo_text" required value="<?= e($settings['logo_text'] ?? 'NEXUS GAMING') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Platform Tagline</label>
                <input type="text" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-brand-400 border-b border-slate-800 pb-2">
                Contact & Communication
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Contact Support Email</label>
                    <input type="email" name="contact_email" required value="<?= e($settings['contact_email'] ?? '') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Support Phone / Hotlines</label>
                    <input type="text" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-brand-400 border-b border-slate-800 pb-2">
                Financial Ledger Parameters
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Currency Symbol</label>
                    <input type="text" name="currency_symbol" required value="<?= e($settings['currency_symbol'] ?? '$') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 font-mono text-center">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Min Deposit Limit</label>
                    <input type="number" step="0.01" name="min_deposit" required value="<?= e($settings['min_deposit'] ?? '10.00') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Max Deposit Limit</label>
                    <input type="number" step="0.01" name="max_deposit" required value="<?= e($settings['max_deposit'] ?? '5000.00') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Funding Instructions Displayed to Players</label>
                <textarea name="deposit_instructions" rows="4"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 font-mono leading-relaxed"><?= e($settings['deposit_instructions'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-brand-400 border-b border-slate-800 pb-2">
                Footer & System Mode
            </h2>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Custom Footer Copyright & Text</label>
                <input type="text" name="footer_text" value="<?= e($settings['footer_text'] ?? '') ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300 font-semibold">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1') ? 'checked' : '' ?>
                        class="rounded bg-slate-900 border-slate-800 text-brand-500 focus:ring-0">
                    Enable System Maintenance Flag
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                Save Platform Settings
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
