<?php
/**
 * User Login
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_guest();

$error = '';
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid or expired session security token. Please try again.';
    } else {
        $loginInput = trim($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $error = 'Please enter both your username/email and password.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("
                SELECT id, name, username, email, password, status
                FROM users
                WHERE (username = ? OR email = ?)
                LIMIT 1
            ");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been deactivated. Please contact customer support.';
                } else {
                    // Update last login
                    $stmtUpdate = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                    $stmtUpdate->execute([$user['id']]);

                    // Regenerate session ID for security against session fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_name'] = $user['name'];

                    set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                    redirect('/dashboard');
                }
            } else {
                $error = 'Invalid credentials. Please verify your login details and try again.';
            }
        }
    }
}

$pageTitle = 'Player Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
        
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-black text-white">Player Portal Sign In</h1>
            <p class="text-xs text-slate-400">Access your gaming wallet, tickets, and account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?= render_flash() ?>

        <form method="POST" action="/login" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="login_input" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Username or Email</label>
                <input type="text" id="login_input" name="login_input" required value="<?= e($loginInput) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="Enter your username or email">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
                    <a href="/forgot-password" class="text-xs text-brand-400 hover:underline">Forgot password?</a>
                </div>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="Enter your password">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm mt-2">
                Sign In to Dashboard
            </button>
        </form>

        <div class="pt-4 border-t border-slate-800/80 text-center text-xs text-slate-400">
            Don't have an account yet? 
            <a href="/register" class="text-brand-400 hover:underline font-semibold">Create an account</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
