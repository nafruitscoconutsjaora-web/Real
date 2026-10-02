<?php
/**
 * Admin Console Login
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin_guest();

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please provide both admin username and password.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, username, name, email, password, status FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ($admin['status'] !== 'active') {
                    $error = 'This administrative account is disabled.';
                } else {
                    $stmtUp = $db->prepare("UPDATE admins SET last_login_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$admin['id']]);

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];

                    set_flash('success', 'Authenticated as ' . $admin['name']);
                    redirect('/admin');
                }
            } else {
                $error = 'Invalid administrative credentials.';
            }
        }
    }
}

$adminPageTitle = 'Administrator Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-amber-500/30 shadow-2xl space-y-6">
        
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/40 flex items-center justify-center text-amber-400 mx-auto font-black text-xl">
                A
            </div>
            <h1 class="text-2xl font-black text-white">Administrator Console</h1>
            <p class="text-xs text-slate-400">Restricted operational access to NexusGaming core</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?= render_flash() ?>

        <form method="POST" action="/admin/login" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Admin Username</label>
                <input type="text" id="username" name="username" required value="<?= e($username) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="admin">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="Enter admin password">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 shadow-lg shadow-amber-500/20 transition-all text-sm mt-2">
                Authorize Administrator Access
            </button>
        </form>

        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] text-slate-400 space-y-1">
            <div class="font-semibold text-amber-400">Default Installation Credentials:</div>
            <div>Username: <code class="text-white font-mono bg-slate-800 px-1 py-0.5 rounded">admin</code></div>
            <div>Password: <code class="text-white font-mono bg-slate-800 px-1 py-0.5 rounded">AdminPassword123!</code></div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
