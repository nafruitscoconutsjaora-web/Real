<?php
/**
 * User Wallet & Ledger
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

$minDeposit = (float)get_setting('min_deposit', '10.00');
$maxDeposit = (float)get_setting('max_deposit', '5000.00');
$depositInstructions = get_setting('deposit_instructions', "Please transfer to our verified platform account and submit your reference code below.");

$error = '';
$success = '';

// Handle manual deposit request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid security session token. Please try again.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank / Manual Transfer');
        $paymentRef = trim($_POST['payment_reference'] ?? '');

        if ($amount < $minDeposit) {
            $error = sprintf('The minimum deposit amount is %s.', format_money($minDeposit));
        } elseif ($amount > $maxDeposit) {
            $error = sprintf('The maximum deposit amount is %s.', format_money($maxDeposit));
        } elseif (empty($paymentRef)) {
            $error = 'Please provide the transaction reference ID or sender confirmation number.';
        } else {
            try {
                $txId = generate_transaction_id();
                $stmt = $db->prepare("
                    INSERT INTO transactions (transaction_id, user_id, type, amount, fee, status, payment_method, payment_reference, created_at)
                    VALUES (?, ?, 'deposit', ?, 0.00, 'pending', ?, ?, NOW())
                ");
                $stmt->execute([$txId, $user['id'], $amount, $paymentMethod, $paymentRef]);

                set_flash('success', sprintf('Deposit request for %s (Ref: %s) has been logged. Our administration team will review and credit your balance upon verification.', format_money($amount), $txId));
                redirect('/wallet');

            } catch (Exception $e) {
                error_log('Deposit request error: ' . $e->getMessage());
                $error = 'An error occurred while logging the transaction request. Please try again.';
            }
        }
    }
}

// Fetch all transactions for this user from MySQL
$transactions = [];
try {
    $stmt = $db->prepare("
        SELECT id, transaction_id, type, amount, fee, status, payment_method, payment_reference, admin_notes, created_at, processed_at
        FROM transactions
        WHERE user_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$user['id']]);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Transaction query error: ' . $e->getMessage());
}

$pageTitle = 'Player Wallet & Ledger';
$activeNav = 'wallet';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <!-- Wallet Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="p-6 rounded-2xl bg-gradient-to-br from-brand-950/60 to-slate-900 border border-brand-500/30 space-y-2">
            <div class="text-xs text-brand-300 font-semibold uppercase tracking-wider">Available Gaming Credits</div>
            <div class="text-3xl font-black text-white"><?= format_money($user['balance']) ?></div>
            <div class="text-xs text-slate-400">Ready for upcoming game engine modules</div>
        </div>

        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Deposit Limits</div>
            <div class="text-xl font-bold text-slate-200">
                <?= format_money($minDeposit) ?> &ndash; <?= format_money($maxDeposit) ?>
            </div>
            <div class="text-xs text-slate-500">Per manual funding transaction</div>
        </div>

        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-2">
            <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Gateway Infrastructure</div>
            <div class="text-xl font-bold text-emerald-400 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Manual Transfer Active
            </div>
            <div class="text-xs text-slate-500">Modular gateway schema ready for provider integrations</div>
        </div>

    </div>

    <!-- Deposit Section & Instructions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Add Money Request Form -->
        <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Submit Deposit Request
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Submit your transaction receipt or reference code for admin ledger confirmation.
                </p>
            </div>

            <form method="POST" action="/wallet" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                        Amount to Credit (<?= get_setting('currency_symbol', DEFAULT_CURRENCY) ?>)
                    </label>
                    <input type="number" step="0.01" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" id="amount" name="amount" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600 font-mono text-base"
                        placeholder="50.00">
                    <span class="text-[11px] text-slate-500 mt-1 block">Min: <?= format_money($minDeposit) ?> &bull; Max: <?= format_money($maxDeposit) ?></span>
                </div>

                <div>
                    <label for="payment_method" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                        Payment Method
                    </label>
                    <select id="payment_method" name="payment_method"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all">
                        <option value="Bank Wire / Transfer">Bank Wire / Transfer</option>
                        <option value="Cryptocurrency (USDT/BTC/ETH)">Cryptocurrency (USDT / BTC / ETH)</option>
                        <option value="Direct Electronic Transfer">Direct Electronic Transfer</option>
                        <option value="Cash Voucher / Counter">Cash Voucher / Counter</option>
                    </select>
                </div>

                <div>
                    <label for="payment_reference" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                        Reference Number / Transaction Hash / Sender Name
                    </label>
                    <input type="text" id="payment_reference" name="payment_reference" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600 font-mono"
                        placeholder="e.g. TR-99841284-NY or TxHash">
                    <span class="text-[11px] text-slate-500 mt-1 block">Used by administrators to match your incoming deposit</span>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm">
                    Submit Request for Approval
                </button>
            </form>
        </div>

        <!-- Verified Instructions -->
        <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Official Funding Instructions
            </h3>
            
            <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs text-slate-300 whitespace-pre-line leading-relaxed font-mono">
                <?= e($depositInstructions) ?>
            </div>

            <div class="space-y-2 text-xs text-slate-400 pt-2">
                <div class="flex items-start gap-2">
                    <span class="text-brand-400 font-bold">&bull;</span>
                    <span>All deposits undergo strict manual or automated validation before being credited to your wallet balance.</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-brand-400 font-bold">&bull;</span>
                    <span>Once approved by an administrator in the control portal, your wallet updates immediately.</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-brand-400 font-bold">&bull;</span>
                    <span>If you have questions regarding a pending deposit, submit a ticket in our support portal with your transaction ID.</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Complete Transaction History Table -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            Complete Wallet Transaction Ledger
        </h2>

        <?php if (empty($transactions)): ?>
            <!-- Clean Empty State -->
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
                <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <h3 class="text-base font-bold text-slate-300">No Transaction Records Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">You have no ledger activity yet. All deposits, withdrawals, and balance adjustments will be listed here with immutable timestamps.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Transaction ID</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Amount</th>
                            <th class="p-4">Method / Ref</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Admin Note</th>
                            <th class="p-4">Created Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-white">
                                    <?= e($tx['transaction_id']) ?>
                                </td>
                                <td class="p-4 uppercase text-[11px] font-semibold text-slate-400">
                                    <?= e(str_replace('_', ' ', $tx['type'])) ?>
                                </td>
                                <td class="p-4 font-mono font-bold text-sm <?= $tx['type'] === 'withdrawal' ? 'text-rose-400' : 'text-emerald-400' ?>">
                                    <?= $tx['type'] === 'withdrawal' ? '-' : '+' ?><?= format_money($tx['amount']) ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium text-slate-200"><?= e($tx['payment_method']) ?></div>
                                    <?php if ($tx['payment_reference']): ?>
                                        <div class="font-mono text-[11px] text-slate-500">Ref: <?= e($tx['payment_reference']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <?php if ($tx['status'] === 'completed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Approved
                                        </span>
                                    <?php elseif ($tx['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-950/80 text-amber-400 border border-amber-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pending Review
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950/80 text-rose-400 border border-rose-800/60">
                                            Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-slate-400 max-w-xs">
                                    <?= $tx['admin_notes'] ? e($tx['admin_notes']) : '<span class="text-slate-600">—</span>' ?>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap">
                                    <?= format_date($tx['created_at']) ?>
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
