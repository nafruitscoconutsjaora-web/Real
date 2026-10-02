<?php
/**
 * Admin Transactions & Ledger Management
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

// Filters
$statusFilter = trim($_GET['status'] ?? 'all');
$typeFilter = trim($_GET['type'] ?? 'all');

// Handle POST actions: approve, reject, or manual adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/transactions');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve_deposit') {
        $txId = (int)($_POST['tx_id'] ?? 0);
        $adminNotes = trim($_POST['admin_notes'] ?? 'Manual deposit verified and approved by admin.');

        try {
            $db->beginTransaction();

            // Fetch transaction with lock
            $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ? FOR UPDATE");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();

            if (!$tx) {
                throw new Exception('Transaction record not found.');
            }
            if ($tx['status'] !== 'pending') {
                throw new Exception("Transaction is already marked as {$tx['status']}.");
            }
            if ($tx['type'] !== 'deposit') {
                throw new Exception('Only deposit requests can be credited through this action.');
            }

            // Credit user wallet
            $stmtW = $db->prepare("UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?");
            $stmtW->execute([$tx['amount'], $tx['user_id']]);

            // Mark transaction completed
            $stmtTx = $db->prepare("
                UPDATE transactions
                SET status = 'completed', admin_notes = ?, processed_by = ?, processed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmtTx->execute([$adminNotes, $admin['id'], $txId]);

            $db->commit();
            set_flash('success', sprintf('Transaction %s approved! %s has been credited to the user wallet.', $tx['transaction_id'], format_money($tx['amount'])));
            redirect('/admin/transactions');

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            set_flash('error', 'Approval failed: ' . $e->getMessage());
            redirect('/admin/transactions');
        }

    } elseif ($action === 'reject_deposit') {
        $txId = (int)($_POST['tx_id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? 'Deposit rejected by administrator.');

        try {
            $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();

            if (!$tx || $tx['status'] !== 'pending') {
                throw new Exception('Transaction not eligible for rejection.');
            }

            $stmtTx = $db->prepare("
                UPDATE transactions
                SET status = 'rejected', admin_notes = ?, processed_by = ?, processed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmtTx->execute([$reason, $admin['id'], $txId]);

            set_flash('info', "Transaction {$tx['transaction_id']} was rejected.");
            redirect('/admin/transactions');

        } catch (Exception $e) {
            set_flash('error', 'Rejection failed: ' . $e->getMessage());
            redirect('/admin/transactions');
        }
    }
}

// Build query with filters
$query = "
    SELECT t.*, u.username as player_username, u.name as player_name, u.email as player_email,
           a.name as processor_name
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    LEFT JOIN admins a ON a.id = t.processed_by
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'completed', 'rejected'], true)) {
    $query .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter !== 'all' && in_array($typeFilter, ['deposit', 'withdrawal', 'manual_adjustment'], true)) {
    $query .= " AND t.type = ?";
    $params[] = $typeFilter;
}

$query .= " ORDER BY t.id DESC";

$transactions = [];
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Transactions query error: ' . $e->getMessage());
}

$adminPageTitle = 'Transaction Ledger Operations';
$activeTab = 'transactions';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <!-- Title & Filter Controls -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Financial Ledger & Transactions</h1>
            <p class="text-xs text-slate-400 mt-0.5">Approve incoming deposit requests, verify reference hashes, and audit all wallet activity.</p>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="/admin/transactions" class="flex flex-wrap items-center gap-2">
            <select name="status" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed / Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>

            <select name="type" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Types</option>
                <option value="deposit" <?= $typeFilter === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                <option value="withdrawal" <?= $typeFilter === 'withdrawal' ? 'selected' : '' ?>>Withdrawals</option>
                <option value="manual_adjustment" <?= $typeFilter === 'manual_adjustment' ? 'selected' : '' ?>>Manual Adjustments</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all">
                Filter
            </button>
            <?php if ($statusFilter !== 'all' || $typeFilter !== 'all'): ?>
                <a href="/admin/transactions" class="px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 transition-colors">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Transaction Records (<?= count($transactions) ?>)</h2>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-500">No transactions match the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Transaction ID</th>
                            <th class="p-4">Player</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Amount</th>
                            <th class="p-4">Payment Method / Reference</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Date</th>
                            <th class="p-4 text-right">Approval Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-white">
                                    <?= e($tx['transaction_id']) ?>
                                    <?php if ($tx['admin_notes']): ?>
                                        <div class="text-[10px] text-amber-400/90 font-sans mt-0.5">Note: <?= e($tx['admin_notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= e($tx['player_name']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500">@<?= e($tx['player_username']) ?></div>
                                </td>
                                <td class="p-4 uppercase text-[10px] font-semibold text-slate-400">
                                    <?= e(str_replace('_', ' ', $tx['type'])) ?>
                                </td>
                                <td class="p-4 font-bold text-sm <?= $tx['type'] === 'withdrawal' ? 'text-rose-400' : 'text-emerald-400' ?>">
                                    <?= $tx['type'] === 'withdrawal' ? '-' : '+' ?><?= format_money($tx['amount']) ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium text-slate-200"><?= e($tx['payment_method']) ?></div>
                                    <?php if ($tx['payment_reference']): ?>
                                        <div class="font-mono text-[11px] text-brand-400">Ref: <?= e($tx['payment_reference']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <?php if ($tx['status'] === 'completed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Approved
                                        </span>
                                    <?php elseif ($tx['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pending Review
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">
                                            Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap">
                                    <?= format_date($tx['created_at']) ?>
                                </td>
                                <td class="p-4 text-right">
                                    <?php if ($tx['status'] === 'pending' && $tx['type'] === 'deposit'): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Approve Form -->
                                            <form method="POST" action="/admin/transactions" class="inline" onsubmit="return confirm('Approve deposit of <?= format_money($tx['amount']) ?> for @<?= e($tx['player_username']) ?>? User wallet will be credited immediately.');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve_deposit">
                                                <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold text-emerald-300 bg-emerald-950 border border-emerald-800 hover:bg-emerald-900 transition-colors">
                                                    Approve & Credit
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="/admin/transactions" class="inline" onsubmit="return confirm('Reject this deposit request?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject_deposit">
                                                <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold text-rose-300 bg-rose-950 border border-rose-800 hover:bg-rose-900 transition-colors">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">
                                            <?= $tx['processor_name'] ? 'By ' . e($tx['processor_name']) : 'System Record' ?>
                                        </span>
                                    <?php endif; ?>
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
