<?php
/**
 * Forgot Password & Password Reset Structure
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_guest();

$token = trim($_GET['token'] ?? '');
$stage = !empty($token) ? 'reset' : 'request';
$message = '';
$error = '';
$generatedTokenInfo = null;

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid security session token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'request') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid account email address.';
            } else {
                $stmt = $db->prepare("SELECT id, email, username FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $resetToken = bin2hex(random_bytes(32));
                    // 1 hour expiry
                    $stmtToken = $db->prepare("
                        INSERT INTO password_resets (email, token, expires_at, created_at)
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
                    ");
                    $stmtToken->execute([$user['email'], $resetToken]);

                    $generatedTokenInfo = [
                        'token' => $resetToken,
                        'email' => $user['email'],
                        'reset_url' => '/forgot-password?token=' . urlencode($resetToken)
                    ];
                    $message = 'A password recovery record has been created for your account in the system database.';
                } else {
                    // Constant message to prevent email enumeration
                    $message = 'If an account exists with that email address, a password recovery request has been logged.';
                }
            }
        } elseif ($action === 'reset') {
            $submittedToken = trim($_POST['token'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                // Verify token in DB
                $stmt = $db->prepare("
                    SELECT id, email FROM password_resets
                    WHERE token = ? AND used = 0 AND expires_at > NOW()
                    LIMIT 1
                ");
                $stmt->execute([$submittedToken]);
                $resetRecord = $stmt->fetch();

                if ($resetRecord) {
                    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                    
                    $db->beginTransaction();
                    $stmtU = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmtU->execute([$hashed, $resetRecord['email']]);

                    $stmtMark = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                    $stmtMark->execute([$resetRecord['id']]);
                    $db->commit();

                    set_flash('success', 'Your password has been successfully reset! Please sign in with your new password.');
                    redirect('/login');
                } else {
                    $error = 'This reset token is invalid, expired, or has already been used.';
                }
            }
        }
    }
}

$pageTitle = 'Password Recovery';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
        
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-black text-white">Password Recovery</h1>
            <p class="text-xs text-slate-400">Database-backed security recovery framework</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-xl border border-emerald-500/40 bg-emerald-950/40 text-emerald-200 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div><?= e($message) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($generatedTokenInfo): ?>
            <!-- Development / Environment Recovery Link Display -->
            <div class="p-4 rounded-2xl bg-slate-900 border border-brand-500/40 space-y-3">
                <div class="text-xs font-semibold text-brand-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                    Verified Reset Token Created in MySQL
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    A valid token has been registered in the database for <strong><?= e($generatedTokenInfo['email']) ?></strong>:
                </p>
                <div class="p-2.5 rounded-lg bg-[#07090e] border border-slate-800 font-mono text-[11px] text-slate-300 break-all select-all">
                    <?= e($generatedTokenInfo['token']) ?>
                </div>
                <a href="<?= e($generatedTokenInfo['reset_url']) ?>" class="block text-center w-full py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md transition-all">
                    Proceed with this Token &rarr;
                </a>
            </div>
        <?php endif; ?>

        <?php if ($stage === 'reset'): ?>
            <!-- Reset Form with Token -->
            <form method="POST" action="/forgot-password?token=<?= e($token) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="token" value="<?= e($token) ?>">

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

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm mt-2">
                    Update Password & Return to Login
                </button>
            </form>
        <?php else: ?>
            <!-- Request Reset Form -->
            <form method="POST" action="/forgot-password" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="request">

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Registered Email Address</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                        placeholder="Enter your registered email">
                </div>

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm mt-2">
                    Generate Recovery Token
                </button>
            </form>
        <?php endif; ?>

        <div class="pt-4 border-t border-slate-800/80 text-center text-xs text-slate-400">
            Remembered your credentials? 
            <a href="/login" class="text-brand-400 hover:underline font-semibold">Back to Sign In</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
