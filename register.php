<?php
/**
 * User Registration
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_guest();

$errors = [];
$name = '';
$username = '';
$email = '';
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid or expired session security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters containing only letters, numbers, and underscores.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $db = get_db();
            
            // Check for existing username
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'This username is already taken. Please choose another.';
            }

            // Check for existing email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmtUser = $db->prepare("
                        INSERT INTO users (name, username, email, mobile, password, status, created_at)
                        VALUES (?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    $stmtUser->execute([$name, $username, $email, $mobile ?: null, $hashedPassword]);
                    $userId = (int)$db->lastInsertId();

                    // Automatically create associated wallet with zero balance
                    $stmtWallet = $db->prepare("
                        INSERT INTO wallets (user_id, balance, created_at)
                        VALUES (?, 0.00, NOW())
                    ");
                    $stmtWallet->execute([$userId]);

                    $db->commit();

                    // Log user in
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_username'] = $username;
                    $_SESSION['user_name'] = $name;

                    set_flash('success', 'Your account has been created successfully! Welcome to ' . get_setting('site_name', APP_NAME) . '.');
                    redirect('/dashboard');

                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Registration failure: ' . $e->getMessage());
                    $errors[] = 'Registration failed due to a database error. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="p-8 rounded-3xl bg-[#0b0f19] border border-slate-800 shadow-2xl space-y-6">
        
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-black text-white">Create Player Account</h1>
            <p class="text-xs text-slate-400">Join NexusGaming with full MySQL ledger integration</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="p-4 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs space-y-1">
                <?php foreach ($errors as $error): ?>
                    <p class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span>
                        <?= e($error) ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= render_flash() ?>

        <form method="POST" action="/register" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Full Name</label>
                <input type="text" id="name" name="name" required value="<?= e($name) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="e.g. Alex Morgan">
            </div>

            <div>
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Username</label>
                <input type="text" id="username" name="username" required value="<?= e($username) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="e.g. alex_player">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= e($email) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="alex@example.com">
            </div>

            <div>
                <label for="mobile" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Mobile Number <span class="text-slate-500 font-normal lowercase">(optional)</span></label>
                <input type="text" id="mobile" name="mobile" value="<?= e($mobile) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="+1 555-0192">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="Minimum 8 characters">
            </div>

            <div>
                <label for="confirm_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                    placeholder="Repeat password">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm mt-2">
                Register & Initialize Wallet
            </button>
        </form>

        <div class="pt-4 border-t border-slate-800/80 text-center text-xs text-slate-400">
            Already have an account? 
            <a href="/login" class="text-brand-400 hover:underline font-semibold">Sign In here</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
