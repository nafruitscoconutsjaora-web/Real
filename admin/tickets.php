<?php
/**
 * Admin Support Tickets Desk
 * NexusGaming Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_admin();
$db = get_db();

$openTicketId = (int)($_GET['open'] ?? 0);
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['q'] ?? '');

$activeTicket = null;
$ticketReplies = [];

// Handle POST actions: reply or change status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('/admin/tickets');
    }

    $action = $_POST['action'] ?? '';
    $ticketId = (int)($_POST['ticket_id'] ?? 0);

    if ($action === 'admin_reply') {
        $replyText = trim($_POST['reply_message'] ?? '');
        $newStatus = $_POST['status'] ?? 'answered';

        if (empty($replyText)) {
            set_flash('error', 'Reply text cannot be blank.');
            redirect("/admin/tickets?open={$ticketId}");
        }

        try {
            $db->beginTransaction();

            $stmtR = $db->prepare("
                INSERT INTO support_replies (ticket_id, admin_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmtR->execute([$ticketId, $admin['id'], $replyText]);

            $stmtUp = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newStatus, $ticketId]);

            $db->commit();
            set_flash('success', 'Reply posted and ticket status updated to ' . $newStatus);
            redirect("/admin/tickets?open={$ticketId}");

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Admin reply error: ' . $e->getMessage());
            set_flash('error', 'Failed to submit reply: ' . $e->getMessage());
            redirect("/admin/tickets?open={$ticketId}");
        }

    } elseif ($action === 'update_status') {
        $newStatus = in_array($_POST['status'], ['open', 'answered', 'closed']) ? $_POST['status'] : 'open';

        $stmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $ticketId]);

        set_flash('info', "Ticket status updated to {$newStatus}.");
        redirect("/admin/tickets" . ($openTicketId ? "?open={$openTicketId}" : ''));
    }
}

// If viewing a specific ticket
if ($openTicketId > 0) {
    $stmtT = $db->prepare("
        SELECT t.*, u.name as player_name, u.username as player_username, u.email as player_email
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id
        WHERE t.id = ?
        LIMIT 1
    ");
    $stmtT->execute([$openTicketId]);
    $activeTicket = $stmtT->fetch();

    if ($activeTicket) {
        $stmtR = $db->prepare("
            SELECT r.*,
                   u.name as user_name, u.username as user_username,
                   a.name as admin_name
            FROM support_replies r
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN admins a ON a.id = r.admin_id
            WHERE r.ticket_id = ?
            ORDER BY r.id ASC
        ");
        $stmtR->execute([$openTicketId]);
        $ticketReplies = $stmtR->fetchAll();
    }
}

// Fetch list of tickets with filters
$query = "
    SELECT t.*, u.username as player_username, u.name as player_name,
           (SELECT COUNT(*) FROM support_replies WHERE ticket_id = t.id) as reply_count
    FROM support_tickets t
    JOIN users u ON u.id = t.user_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['open', 'answered', 'closed'], true)) {
    $query .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $query .= " AND (t.ticket_number LIKE ? OR t.subject LIKE ? OR u.username LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$query .= " ORDER BY t.id DESC";

$tickets = [];
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Support query error: ' . $e->getMessage());
}

$adminPageTitle = 'Support Desk Management';
$activeTab = 'tickets';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    <?= render_flash() ?>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Player Support Desk</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage player inquiries, provide official replies, and maintain ticket resolutions.</p>
        </div>

        <form method="GET" action="/admin/tickets" class="flex flex-wrap items-center gap-2">
            <select name="status" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open / Unresolved</option>
                <option value="answered" <?= $statusFilter === 'answered' ? 'selected' : '' ?>>Answered</option>
                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>

            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search ticket #, subject, user..."
                class="w-48 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder:text-slate-500 outline-none">

            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors">
                Filter
            </button>
            <?php if ($statusFilter !== 'all' || !empty($search)): ?>
                <a href="/admin/tickets" class="px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- If viewing active ticket conversation -->
    <?php if ($activeTicket): ?>
        <div class="p-8 rounded-3xl bg-[#0b0f19] border border-amber-500/40 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm font-bold text-amber-400">
                            <?= e($activeTicket['ticket_number']) ?>
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                            <?= e($activeTicket['priority']) ?>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase <?= $activeTicket['status'] === 'open' ? 'bg-amber-950 text-amber-400 border border-amber-800' : ($activeTicket['status'] === 'answered' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400') ?>">
                            <?= e($activeTicket['status']) ?>
                        </span>
                    </div>
                    <h2 class="text-xl font-bold text-white"><?= e($activeTicket['subject']) ?></h2>
                    <p class="text-xs text-slate-400">Player: <strong><?= e($activeTicket['player_name']) ?></strong> (@<?= e($activeTicket['player_username']) ?> &bull; <?= e($activeTicket['player_email']) ?>)</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Quick status update form -->
                    <form method="POST" action="/admin/tickets?open=<?= $activeTicket['id'] ?>" class="flex items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="ticket_id" value="<?= $activeTicket['id'] ?>">
                        <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white">
                            <option value="open" <?= $activeTicket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="answered" <?= $activeTicket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
                            <option value="closed" <?= $activeTicket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </form>

                    <a href="/admin/tickets" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800">
                        Close View &times;
                    </a>
                </div>
            </div>

            <!-- Thread Messages -->
            <div class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Conversation Timeline (<?= count($ticketReplies) ?>)</h3>

                <?php foreach ($ticketReplies as $rep): ?>
                    <?php
                        $isAdmin = !empty($rep['admin_id']);
                        $cardBg = $isAdmin ? 'bg-[#0f172a] border-amber-500/30' : 'bg-slate-900 border-slate-800';
                    ?>
                    <div class="p-5 rounded-2xl border <?= $cardBg ?> space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded flex items-center justify-center font-bold text-[10px] <?= $isAdmin ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-300' ?>">
                                    <?= $isAdmin ? 'ADM' : 'PLY' ?>
                                </span>
                                <span class="text-xs font-bold text-white">
                                    <?= $isAdmin ? e($rep['admin_name'] ?: 'Admin Staff') : e($rep['user_name'] ?: 'Player') ?>
                                </span>
                                <?php if ($isAdmin): ?>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-amber-950 text-amber-400 border border-amber-800">
                                        Staff Response
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-slate-500"><?= format_date($rep['created_at']) ?></span>
                        </div>
                        <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed pl-8">
                            <?= e($rep['message']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Admin Reply Box -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                    Send Official Administrative Reply
                </h3>

                <form method="POST" action="/admin/tickets?open=<?= $activeTicket['id'] ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="admin_reply">
                    <input type="hidden" name="ticket_id" value="<?= $activeTicket['id'] ?>">

                    <div>
                        <textarea name="reply_message" rows="4" required placeholder="Type official reply to the player..."
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white outline-none focus:border-amber-500 resize-none"></textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-400">Set Ticket Status:</span>
                            <select name="status" class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white outline-none">
                                <option value="answered" selected>Mark as Answered</option>
                                <option value="closed">Resolve & Close Ticket</option>
                                <option value="open">Keep Open</option>
                            </select>
                        </div>

                        <button type="submit" class="px-6 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 shadow-md">
                            Dispatch Reply to Player
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tickets Table Directory -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-white">Support Tickets Directory (<?= count($tickets) ?>)</h2>

        <?php if (empty($tickets)): ?>
            <div class="p-12 rounded-2xl bg-[#0b0f19] border border-slate-800 text-center space-y-2">
                <p class="text-xs text-slate-500">No support tickets match the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl bg-[#0b0f19] border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-900/80 font-semibold">
                        <tr>
                            <th class="p-4">Ticket Number</th>
                            <th class="p-4">Player</th>
                            <th class="p-4">Subject</th>
                            <th class="p-4">Priority</th>
                            <th class="p-4">Replies</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Created Date</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-amber-400"><?= e($t['ticket_number']) ?></td>
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= e($t['player_name']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500">@<?= e($t['player_username']) ?></div>
                                </td>
                                <td class="p-4 font-medium text-slate-200"><?= e($t['subject']) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-900 border border-slate-700 text-slate-300">
                                        <?= e($t['priority']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400"><?= (int)$t['reply_count'] ?> messages</td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $t['status'] === 'open' ? 'bg-amber-950 text-amber-400 border border-amber-800' : ($t['status'] === 'answered' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= e($t['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap"><?= format_date($t['created_at']) ?></td>
                                <td class="p-4 text-right">
                                    <a href="/admin/tickets?open=<?= $t['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors inline-block">
                                        Open Conversation &rarr;
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
