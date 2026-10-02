<?php
/**
 * Admin User Management
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$search = trim($_GET['q'] ?? '');
$viewUserId = (int)($_GET['view'] ?? 0);

// Handle POST actions: toggle status or manual wallet adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/users');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['status'] === 'active' ? 'active' : 'inactive';

        $stmtToggle = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmtToggle->execute([$newStatus, $targetUserId]);

        set_flash('success', "User account status updated to {$newStatus}.");
        redirect('/admin/users' . ($viewUserId ? "?view={$viewUserId}" : ''));

    } elseif ($action === 'wallet_adjustment') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $adjType = $_POST['adj_type'] === 'debit' ? 'debit' : 'credit';
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Admin manual adjustment');

        if ($amount <= 0) {
            set_flash('error', 'Adjustment amount must be greater than zero.');
            redirect("/admin/users?view={$targetUserId}");
        }

        try {
            $db->beginTransaction();

            // Fetch current balance
            $stmtW = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmtW->execute([$targetUserId]);
            $currentBal = (float)$stmtW->fetchColumn();

            if ($adjType === 'debit' && $currentBal < $amount) {
                $db->rollBack();
                set_flash('error', sprintf('Cannot debit %s: user only has %s available.', format_money($amount), format_money($currentBal)));
                redirect("/admin/users?view={$targetUserId}");
            }

            $newBal = ($adjType === 'credit') ? ($currentBal + $amount) : ($currentBal - $amount);

            // Update wallet
            $stmtUpW = $db->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE user_id = ?");
            $stmtUpW->execute([$newBal, $targetUserId]);

            // Insert audit transaction
            $txId = generate_transaction_id();
            $stmtTx = $db->prepare("
                INSERT INTO transactions (transaction_id, user_id, type, amount, status, payment_method, payment_reference, admin_notes, processed_by, processed_at, created_at)
                VALUES (?, ?, 'manual_adjustment', ?, 'completed', 'Administrative Ledger Action', ?, ?, ?, NOW(), NOW())
            ");
            $stmtTx->execute([
                $txId,
                $targetUserId,
                $amount,
                strtoupper($adjType) . ' Adjustment',
                $reason,
                $admin['id']
            ]);

            $db->commit();

            set_flash('success', sprintf('Successfully applied %s of %s to user wallet. Transaction ID: %s', $adjType, format_money($amount), $txId));
            redirect("/admin/users?view={$targetUserId}");

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Adjustment error: ' . $e->getMessage());
            set_flash('error', 'Wallet adjustment failed: ' . $e->getMessage());
            redirect("/admin/users?view={$targetUserId}");
        }
    }
}

// Fetch list of users with search filter
$users = [];
try {
    if (!empty($search)) {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.username, u.email, u.mobile, u.status, u.created_at, u.last_login_at,
                   COALESCE(w.balance, 0.00) as balance,
                   (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as tx_count
            FROM users u
            LEFT JOIN wallets w ON w.user_id = u.id
            WHERE u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ?
            ORDER BY u.id DESC
        ");
        $term = "%{$search}%";
        $stmt->execute([$term, $term, $term]);
    } else {
        $stmt = $db->query("
            SELECT u.id, u.name, u.username, u.email, u.mobile, u.status, u.created_at, u.last_login_at,
                   COALESCE(w.balance, 0.00) as balance,
                   (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as tx_count
            FROM users u
            LEFT JOIN wallets w ON w.user_id = u.id
            ORDER BY u.id DESC
        ");
    }
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Users query error: ' . $e->getMessage());
}

// If viewing a specific user, fetch full details & transactions
$detailedUser = null;
$userTransactions = [];
if ($viewUserId > 0) {
    $stmtD = $db->prepare("
        SELECT u.id, u.name, u.username, u.email, u.mobile, u.status, u.created_at, u.last_login_at,
               COALESCE(w.balance, 0.00) as balance
        FROM users u
        LEFT JOIN wallets w ON w.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmtD->execute([$viewUserId]);
    $detailedUser = $stmtD->fetch();

    if ($detailedUser) {
        $stmtT = $db->prepare("
            SELECT * FROM transactions
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 25
        ");
        $stmtT->execute([$viewUserId]);
        $userTransactions = $stmtT->fetchAll();
    }
}

$adminPageTitle = 'User & Wallet Management';
$activeTab = 'users';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Header & Search Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Player Accounts & Ledgers</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage player credentials, account statuses, and perform controlled wallet adjustments.</p>
        </div>

        <form method="GET" action="/admin/users" class="flex items-center gap-2">
            <div class="relative">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search username, email, name..."
                    class="w-64 px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder:text-slate-500 focus:border-brand-500 outline-none">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="/admin/users" class="px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 transition-colors">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- If viewing detailed user drilldown -->
    <?php if ($detailedUser): ?>
        <div class="p-6 rounded-3xl bg-[#0b0f19] border border-brand-500/40 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-brand-500/20 text-brand-400 flex items-center justify-center font-black text-lg border border-brand-500/40">
                        <?= strtoupper(substr($detailedUser['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-white"><?= e($detailedUser['name']) ?></h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $detailedUser['status'] === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800' ?>">
                                <?= e($detailedUser['status']) ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">@<?= e($detailedUser['username']) ?> &bull; <?= e($detailedUser['email']) ?> &bull; <?= e($detailedUser['mobile'] ?: 'No mobile') ?></p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Status Toggle Form -->
                    <form method="POST" action="/admin/users?view=<?= $detailedUser['id'] ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= $detailedUser['id'] ?>">
                        <input type="hidden" name="status" value="<?= $detailedUser['status'] === 'active' ? 'inactive' : 'active' ?>">
                        <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold <?= $detailedUser['status'] === 'active' ? 'bg-rose-950/80 text-rose-300 border border-rose-800 hover:bg-rose-900' : 'bg-emerald-950/80 text-emerald-300 border border-emerald-800 hover:bg-emerald-900' ?>">
                            <?= $detailedUser['status'] === 'active' ? 'Deactivate Account' : 'Activate Account' ?>
                        </button>
                    </form>

                    <a href="/admin/users" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700">
                        Close Profile
                    </a>
                </div>
            </div>

            <!-- Detailed Grid: Wallet Balance + Manual Adjustment Tool -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Current Balance Display -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                    <div class="text-xs text-slate-400 uppercase font-semibold">User Wallet Balance</div>
                    <div class="text-3xl font-black text-brand-400"><?= format_money($detailedUser['balance']) ?></div>
                    <div class="text-[11px] text-slate-500">Live balance in `wallets` table</div>
                </div>

                <!-- Controlled Adjustment Form -->
                <div class="md:col-span-2 p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Controlled Ledger Balance Adjustment
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Every adjustment writes an audited transaction record in MySQL. Balances are never modified silently.</p>
                    </div>

                    <form method="POST" action="/admin/users?view=<?= $detailedUser['id'] ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="wallet_adjustment">
                        <input type="hidden" name="user_id" value="<?= $detailedUser['id'] ?>">

                        <div>
                            <label class="block text-[10px] font-semibold uppercase text-slate-400 mb-1">Action Type</label>
                            <select name="adj_type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white outline-none">
                                <option value="credit">Credit (+) Add Funds</option>
                                <option value="debit">Debit (-) Deduct Funds</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold uppercase text-slate-400 mb-1">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required placeholder="10.00"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white outline-none font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold uppercase text-slate-400 mb-1">Audit Reason / Note</label>
                            <input type="text" name="reason" required placeholder="e.g. Compensation / manual payment"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white outline-none">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                                Apply & Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Transaction History -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-white">Audited Transactions for @<?= e($detailedUser['username']) ?></h3>
                <?php if (empty($userTransactions)): ?>
                    <p class="text-xs text-slate-500 italic p-4 bg-slate-900 rounded-xl">No transactions found for this player account.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-900 text-slate-400 uppercase font-semibold text-[10px]">
                                <tr>
                                    <th class="p-3">Ref ID</th>
                                    <th class="p-3">Type</th>
                                    <th class="p-3">Amount</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Method / Notes</th>
                                    <th class="p-3">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                <?php foreach ($userTransactions as $tx): ?>
                                    <tr class="hover:bg-slate-900/50">
                                        <td class="p-3 font-mono font-bold"><?= e($tx['transaction_id']) ?></td>
                                        <td class="p-3 uppercase text-[10px]"><?= e(str_replace('_', ' ', $tx['type'])) ?></td>
                                        <td class="p-3 font-bold <?= $tx['type'] === 'withdrawal' ? 'text-rose-400' : 'text-emerald-400' ?>">
                                            <?= format_money($tx['amount']) ?>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $tx['status'] === 'completed' ? 'bg-emerald-950 text-emerald-400' : ($tx['status'] === 'pending' ? 'bg-amber-950 text-amber-400' : 'bg-rose-950 text-rose-400') ?>">
                                                <?= e($tx['status']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-400">
                                            <div><?= e($tx['payment_method']) ?></div>
                                            <?php if ($tx['admin_notes']): ?>
                                                <div class="text-[10px] text-amber-400/80">Note: <?= e($tx['admin_notes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-slate-500 whitespace-nowrap"><?= format_date($tx['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Users Table -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Registered Users Directory (<?= count($users) ?>)</h2>
        </div>

        <?php if (empty($users)): ?>
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-500">No user accounts found matching your query.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">Name / Username</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Wallet Balance</th>
                            <th class="p-4">Transactions</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Registered</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono text-slate-500">#<?= $u['id'] ?></td>
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= e($u['name']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500">@<?= e($u['username']) ?></div>
                                </td>
                                <td class="p-4 text-slate-400"><?= e($u['email']) ?></td>
                                <td class="p-4 font-bold text-brand-400 text-sm"><?= format_money($u['balance']) ?></td>
                                <td class="p-4 text-slate-400"><?= (int)$u['tx_count'] ?> records</td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $u['status'] === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800' ?>">
                                        <?= e($u['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap"><?= format_date($u['created_at'], false) ?></td>
                                <td class="p-4 text-right">
                                    <a href="/admin/users?view=<?= $u['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-sm shadow-brand-600/30 transition-all inline-block">
                                        Manage & Wallet
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
