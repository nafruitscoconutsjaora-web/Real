<?php
/**
 * Single Support Ticket View & Conversation
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db();

$ticketId = (int)($_GET['id'] ?? 0);

// Fetch ticket & verify ownership
$stmtT = $db->prepare("
    SELECT t.*, u.username as player_username, u.name as player_name
    FROM support_tickets t
    JOIN users u ON u.id = t.user_id
    WHERE t.id = ? AND t.user_id = ?
    LIMIT 1
");
$stmtT->execute([$ticketId, $user['id']]);
$ticket = $stmtT->fetch();

if (!$ticket) {
    set_flash('error', 'Support ticket not found or access denied.');
    redirect('/support');
}

$replyError = '';

// Handle new reply or close
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $replyError = 'Security session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'reply') {
            $replyMessage = trim($_POST['reply_message'] ?? '');
            if (empty($replyMessage)) {
                $replyError = 'Reply message cannot be empty.';
            } else {
                try {
                    $db->beginTransaction();

                    $stmtR = $db->prepare("
                        INSERT INTO support_replies (ticket_id, user_id, message, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmtR->execute([$ticket['id'], $user['id'], $replyMessage]);

                    // Update ticket status back to open if it was answered
                    $stmtUp = $db->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$ticket['id']]);

                    $db->commit();
                    set_flash('success', 'Your reply has been posted.');
                    redirect('/support-ticket?id=' . $ticket['id']);

                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Support reply error: ' . $e->getMessage());
                    $replyError = 'Failed to submit reply. Please try again.';
                }
            }
        } elseif ($action === 'close') {
            $stmtC = $db->prepare("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmtC->execute([$ticket['id'], $user['id']]);
            set_flash('info', 'Ticket has been marked as closed.');
            redirect('/support-ticket?id=' . $ticket['id']);
        }
    }
}

// Fetch all replies
$replies = [];
try {
    $stmtReplies = $db->prepare("
        SELECT r.*,
               u.name as user_name, u.username as user_username,
               a.name as admin_name, a.username as admin_username
        FROM support_replies r
        LEFT JOIN users u ON u.id = r.user_id
        LEFT JOIN admins a ON a.id = r.admin_id
        WHERE r.ticket_id = ?
        ORDER BY r.id ASC
    ");
    $stmtReplies->execute([$ticket['id']]);
    $replies = $stmtReplies->fetchAll();
} catch (Exception $e) {
    error_log('Replies fetch error: ' . $e->getMessage());
}

$pageTitle = 'Ticket #' . e($ticket['ticket_number']);
$activeNav = 'support';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/user_nav.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 pb-16">

    <div class="flex items-center justify-between">
        <a href="/support" class="text-xs font-semibold text-brand-400 hover:underline flex items-center gap-1.5">
            &larr; Back to Tickets List
        </a>

        <?php if ($ticket['status'] !== 'closed'): ?>
            <form method="POST" action="/support-ticket?id=<?= $ticket['id'] ?>" class="inline" onsubmit="return confirm('Are you sure you want to close this ticket?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="close">
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                    Close Ticket
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?= render_flash() ?>

    <?php if (!empty($replyError)): ?>
        <div class="p-3.5 rounded-xl border border-rose-500/40 bg-rose-950/40 text-rose-200 text-xs">
            <?= e($replyError) ?>
        </div>
    <?php endif; ?>

    <!-- Ticket Header Card -->
    <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-bold text-brand-400">
                    <?= e($ticket['ticket_number']) ?>
                </span>
                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                    Priority: <?= e($ticket['priority']) ?>
                </span>
            </div>

            <div>
                <?php if ($ticket['status'] === 'open'): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-950/80 text-amber-400 border border-amber-800/60">
                        Open / Awaiting Admin
                    </span>
                <?php elseif ($ticket['status'] === 'answered'): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                        Answered by Admin
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400">
                        Closed
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="text-xl font-black text-white"><?= e($ticket['subject']) ?></h1>
        
        <div class="text-xs text-slate-500 pt-2 border-t border-slate-800 flex items-center justify-between">
            <span>Opened: <?= format_date($ticket['created_at']) ?></span>
            <span>Last Activity: <?= format_date($ticket['updated_at']) ?></span>
        </div>
    </div>

    <!-- Conversation Timeline -->
    <div class="space-y-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Message Thread (<?= count($replies) ?>)</h3>

        <?php foreach ($replies as $rep): ?>
            <?php
                $isAdmin = !empty($rep['admin_id']);
                $cardBg = $isAdmin ? 'bg-[#0f172a] border-sky-800/50' : 'bg-[#0b0f19] border-slate-800';
            ?>
            <div class="p-6 rounded-2xl border <?= $cardBg ?> space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs <?= $isAdmin ? 'bg-sky-600 text-white' : 'bg-slate-800 text-slate-300' ?>">
                            <?= $isAdmin ? 'ADM' : 'YOU' ?>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white">
                                <?= $isAdmin ? e($rep['admin_name'] ?: 'Official Support Staff') : e($rep['user_name'] ?: $user['username']) ?>
                            </span>
                            <?php if ($isAdmin): ?>
                                <span class="ml-1.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-950 text-sky-400 border border-sky-800">
                                    Support Staff
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500"><?= format_date($rep['created_at']) ?></span>
                </div>

                <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed pl-10">
                    <?= e($rep['message']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Box (if not closed) -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="p-6 rounded-2xl bg-[#0b0f19] border border-slate-800 space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                Post a Reply to Desk
            </h3>

            <form method="POST" action="/support-ticket?id=<?= $ticket['id'] ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reply">

                <div>
                    <textarea name="reply_message" rows="4" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-white text-sm outline-none transition-all placeholder:text-slate-600 resize-none"
                        placeholder="Type your message to support..."></textarea>
                </div>

                <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 transition-all text-xs">
                    Send Reply
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 text-center space-y-2">
            <p class="text-xs text-slate-400">This ticket has been marked as closed. If you require further assistance, please open a new support ticket.</p>
            <a href="/support" class="inline-block mt-1 text-xs font-semibold text-brand-400 hover:underline">Open New Ticket &rarr;</a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
