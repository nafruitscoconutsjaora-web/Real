<?php
/**
 * Admin Profile & Password Change
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$profileError = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/profile');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) {
            $profileError = 'Admin name cannot be blank.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileError = 'Valid email address is required.';
        } else {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $admin['id']]);
            if ($stmt->fetch()) {
                $profileError = 'This email address is already registered to another admin.';
            } else {
                $stmtUp = $db->prepare("UPDATE admins SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                $stmtUp->execute([$name, $email, $admin['id']]);
                $_SESSION['admin_name'] = $name;

                set_flash('success', 'Admin profile updated.');
                redirect('/admin/profile');
            }
        }

    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmtP = $db->prepare("SELECT password FROM admins WHERE id = ?");
        $stmtP->execute([$admin['id']]);
        $currentHash = $stmtP->fetchColumn();

        if (!password_verify($currentPassword, (string)$currentHash)) {
            $passwordError = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 8) {
            $passwordError = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'The new passwords do not match.';
        } else {
            $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUp = $db->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newHashed, $admin['id']]);

            set_flash('success', 'Admin password successfully changed.');
            redirect('/admin/profile');
        }
    }
}

$adminPageTitle = 'Administrator Profile';
$activeTab = 'profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div>
        <h1 class="text-2xl font-black text-white">Administrator Profile & Security</h1>
        <p class="text-xs text-slate-400 mt-0.5">Manage administrative credentials and update password authentication.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Admin Profile -->
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Profile Credentials
                </h2>
            </div>

            <?php if (!empty($profileError)): ?>
                <div class="p-3.5 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs">
                    <?= e($profileError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/profile" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Admin Username</label>
                    <input type="text" disabled value="<?= e($admin['username']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900/50 border border-slate-800 text-slate-500 text-xs font-mono cursor-not-allowed">
                </div>

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase text-slate-300 mb-1">Display Name</label>
                    <input type="text" id="name" name="name" required value="<?= e($admin['name']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase text-slate-300 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= e($admin['email']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                    Save Profile
                </button>
            </form>
        </div>

        <!-- Admin Change Password -->
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Change Admin Password
                </h2>
            </div>

            <?php if (!empty($passwordError)): ?>
                <div class="p-3.5 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs">
                    <?= e($passwordError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/profile" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label for="current_password" class="block text-xs font-semibold uppercase text-slate-300 mb-1">Current Admin Password</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 placeholder:text-slate-600"
                        placeholder="Verify current password">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase text-slate-300 mb-1">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 placeholder:text-slate-600"
                        placeholder="Minimum 8 characters">
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-semibold uppercase text-slate-300 mb-1">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-brand-500 placeholder:text-slate-600"
                        placeholder="Repeat new password">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                    Update Password
                </button>
            </form>

            <div class="pt-4 border-t border-slate-800 text-center">
                <a href="/admin/logout" class="text-xs text-rose-400 hover:underline font-semibold flex items-center justify-center gap-1.5">
                    Sign Out of Admin Console
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
