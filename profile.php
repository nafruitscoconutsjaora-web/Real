<?php
/**
 * User Profile & Security Settings
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

$profileError = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security session token expired. Please try again.');
        redirect('/profile');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');

        if (empty($name)) {
            $profileError = 'Full name cannot be blank.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileError = 'A valid email address is required.';
        } else {
            // Check email uniqueness if modified
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $profileError = 'This email address is already in use by another player.';
            } else {
                $stmtU = $db->prepare("UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ?");
                $stmtU->execute([$name, $email, $mobile ?: null, $user['id']]);
                $_SESSION['user_name'] = $name;

                set_flash('success', 'Your profile details have been successfully updated in MySQL.');
                redirect('/profile');
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Fetch current password hash from DB
        $stmtP = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmtP->execute([$user['id']]);
        $currentHash = $stmtP->fetchColumn();

        if (!password_verify($currentPassword, (string)$currentHash)) {
            $passwordError = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 8) {
            $passwordError = 'New password must be at least 8 characters in length.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'The new passwords do not match.';
        } else {
            $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUp = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUp->execute([$newHashed, $user['id']]);

            set_flash('success', 'Your password has been changed securely.');
            redirect('/profile');
        }
    }
}

$pageTitle = 'Player Profile & Security';
$activeNav = 'profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Profile Info Form -->
        <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Profile Details
                </h2>
                <p class="text-xs text-slate-400 mt-1">Manage your identity and contact information.</p>
            </div>

            <?php if (!empty($profileError)): ?>
                <div class="p-3.5 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs">
                    <?= e($profileError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/profile" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Username</label>
                    <input type="text" disabled value="<?= e($user['username']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900/50 border border-slate-800 text-slate-500 text-sm cursor-not-allowed font-mono">
                    <span class="text-[11px] text-slate-500 mt-1 block">Account username is immutable</span>
                </div>

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= e($user['name']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all">
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= e($user['email']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all">
                </div>

                <div>
                    <label for="mobile" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Mobile Number</label>
                    <input type="text" id="mobile" name="mobile" value="<?= e($user['mobile'] ?? '') ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all"
                        placeholder="+1 555-0199">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all text-xs">
                    Save Profile Changes
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Change Password
                </h2>
                <p class="text-xs text-slate-400 mt-1">Update your password using secure bcrypt hashing.</p>
            </div>

            <?php if (!empty($passwordError)): ?>
                <div class="p-3.5 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs">
                    <?= e($passwordError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/profile" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label for="current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                        placeholder="Verify current password">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                        placeholder="At least 8 characters">
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                        placeholder="Repeat new password">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-all text-xs">
                    Update Security Password
                </button>
            </form>

            <div class="pt-4 border-t border-slate-800 text-center">
                <a href="/logout" class="text-xs text-rose-400 hover:underline font-semibold flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Terminate Session & Sign Out
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
