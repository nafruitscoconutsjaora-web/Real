<?php
/**
 * User Support Desk
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

$error = '';
$subject = '';
$message = '';
$priority = 'medium';

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session token expired. Please try again.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $priority = trim($_POST['priority'] ?? 'medium');
        $message = trim($_POST['message'] ?? '');

        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        if (empty($subject)) {
            $error = 'Please enter a ticket subject.';
        } elseif (empty($message)) {
            $error = 'Please describe your request or issue in the message box.';
        } else {
            try {
                $db->beginTransaction();

                $ticketNumber = generate_ticket_number();
                $stmtT = $db->prepare("
                    INSERT INTO support_tickets (ticket_number, user_id, subject, priority, status, created_at)
                    VALUES (?, ?, ?, ?, 'open', NOW())
                ");
                $stmtT->execute([$ticketNumber, $user['id'], $subject, $priority]);
                $ticketId = (int)$db->lastInsertId();

                // Insert initial message into replies
                $stmtR = $db->prepare("
                    INSERT INTO support_replies (ticket_id, user_id, message, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtR->execute([$ticketId, $user['id'], $message]);

                $db->commit();

                set_flash('success', "Support ticket {$ticketNumber} has been opened. Our support desk will reply promptly.");
                redirect('/support-ticket?id=' . $ticketId);

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Support ticket creation error: ' . $e->getMessage());
                $error = 'Could not create ticket due to a system error. Please try again.';
            }
        }
    }
}

// Fetch only this user's tickets
$tickets = [];
try {
    $stmt = $db->prepare("
        SELECT t.id, t.ticket_number, t.subject, t.priority, t.status, t.created_at, t.updated_at,
               (SELECT COUNT(*) FROM support_replies WHERE ticket_id = t.id) as reply_count
        FROM support_tickets t
        WHERE t.user_id = ?
        ORDER BY t.id DESC
    ");
    $stmt->execute([$user['id']]);
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Support tickets query error: ' . $e->getMessage());
}

$pageTitle = 'Player Support Desk';
$activeNav = 'support';
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

    <!-- Two Columns: Create Ticket Form & Ticket History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Open New Ticket Form -->
        <div class="p-8 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Open Support Ticket
                </h2>
                <p class="text-xs text-slate-400 mt-1">Submit inquiries regarding transactions, account security, or platform questions.</p>
            </div>

            <form method="POST" action="/support" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="subject" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Subject</label>
                    <input type="text" id="subject" name="subject" required value="<?= e($subject) ?>"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600"
                        placeholder="e.g. Deposit verification query">
                </div>

                <div>
                    <label for="priority" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Priority Level</label>
                    <select id="priority" name="priority"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all">
                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low &ndash; General Inquiry</option>
                        <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium &ndash; Standard Support</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High &ndash; Urgent Ledger Issue</option>
                        <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent &ndash; Critical Account Lock</option>
                    </select>
                </div>

                <div>
                    <label for="message" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Message / Details</label>
                    <textarea id="message" name="message" rows="5" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600 resize-none"
                        placeholder="Please provide full details including transaction IDs if relevant..."><?= e($message) ?></textarea>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 to-sky-500 hover:from-brand-500 hover:to-sky-400 shadow-lg shadow-brand-500/25 transition-all text-sm">
                    Submit Ticket to Desk
                </button>
            </form>
        </div>

        <!-- Right: Tickets List (2 cols on large screen) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Your Support Tickets
                </h2>
                <span class="text-xs text-slate-400 font-mono"><?= count($tickets) ?> records</span>
            </div>

            <?php if (empty($tickets)): ?>
                <!-- Clean Empty State -->
                <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-300">No support tickets yet.</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">You have not opened any support requests yet. Use the form on the left to submit an inquiry at any time.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($tickets as $t): ?>
                        <a href="/support-ticket?id=<?= $t['id'] ?>" class="block p-5 rounded-2xl bg-[#0b0f19] border border-slate-800 hover:border-brand-500/50 transition-all group">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-mono text-xs font-bold text-brand-400">
                                            <?= e($t['ticket_number']) ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                                            <?= e($t['priority']) ?>
                                        </span>
                                    </div>
                                    <h3 class="text-base font-bold text-white group-hover:text-brand-300 transition-colors">
                                        <?= e($t['subject']) ?>
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Created <?= format_date($t['created_at']) ?> &bull; <?= (int)$t['reply_count'] ?> messages
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <?php if ($t['status'] === 'open'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-950/80 text-amber-400 border border-amber-800/60">
                                            Open / Pending
                                        </span>
                                    <?php elseif ($t['status'] === 'answered'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                                            Answered by Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400">
                                            Closed
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-slate-500 group-hover:text-white transition-colors">&rarr;</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
