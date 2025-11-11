<?php
$currentPage = 'payments';
$pageTitle = trans('app_name') . ' - ' . trans('weekly_status');
require_once APP_ROOT . '/templates/header.php';

// --- (PHP logic for filters and POST handling is correct and unchanged) ---
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$selectedWeek = (int)($_GET['week'] ?? date('W'));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment_update') {
    $memberId = (int)$_POST['member_id'];
    $totalAmountDue = (float)$_POST['total_amount_due'];
    $amountPaid = (float)($_POST['amount_paid'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $week = (int)($_GET['week'] ?? $selectedWeek);
    $year = (int)($_GET['year'] ?? $selectedYear);
    if ($amountPaid >= $totalAmountDue) { $newStatus = 'Paid'; $amountPaid = $totalAmountDue; } 
    elseif ($amountPaid > 0) { $newStatus = 'Partial'; } 
    else { $newStatus = 'Pending'; }
    try {
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE member_id = ? AND payment_week = ? AND payment_year = ?");
        $stmt->execute([$memberId, $week, $year]);
        $paymentId = $stmt->fetchColumn();
        if ($paymentId) {
            $updateStmt = $pdo->prepare("UPDATE payments SET amount_paid = ?, notes = ?, status = ?, payment_date = ? WHERE id = ?");
            $updateStmt->execute([$amountPaid, $notes, $newStatus, date('Y-m-d'), $paymentId]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO payments (member_id, amount, amount_paid, notes, payment_week, payment_year, status, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([$memberId, $totalAmountDue, $amountPaid, $notes, $week, $year, $newStatus, date('Y-m-d')]);
        }
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Payment updated successfully!'];
    } catch (PDOException $e) { $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]; }
    header('Location: ' . URL_ROOT . "/payments?year={$year}&week={$week}");
    exit();
}
$currentYear = (int)date('Y');
$years_from_db = $pdo->query("SELECT DISTINCT payment_year FROM payments ORDER BY payment_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$available_years = array_unique(array_merge([$currentYear-1, $currentYear, $currentYear+1], $years_from_db));
rsort($available_years);
$available_weeks = range(1, 52);
$stmt = $pdo->prepare("SELECT m.id AS member_id, m.name AS member_name, m.contribution_amount, p.id AS payment_id, p.amount AS total_due, p.status AS payment_status, p.amount_paid, p.notes FROM members m LEFT JOIN payments p ON m.id = p.member_id AND p.payment_week = ? AND p.payment_year = ? WHERE m.status = 'Active' ORDER BY m.name ASC");
$stmt->execute([$selectedWeek, $selectedYear]);
$membersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 p-4 sm:p-6 bg-slate-100">
        <h1 class="text-2xl font-bold text-slate-800 mb-6"><?php echo trans('weekly_payment_status'); ?></h1>
        <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
            <form action="<?php echo URL_ROOT; ?>/payments" method="GET" class="flex flex-col sm:flex-row items-center gap-4">
                <div class="w-full sm:w-auto">
                    <label for="year" class="block text-sm font-medium text-slate-700">Year</label>
                    <select name="year" id="year" class="mt-1 block w-full rounded-lg border-slate-300">
                        <?php foreach ($available_years as $year): ?><option value="<?php echo $year; ?>" <?php echo ($selectedYear == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="week" class="block text-sm font-medium text-slate-700">Week</label>
                    <select name="week" id="week" class="mt-1 block w-full rounded-lg border-slate-300">
                        <?php foreach ($available_weeks as $week): ?><option value="<?php echo $week; ?>" <?php echo ($selectedWeek == $week) ? 'selected' : ''; ?>>Week <?php echo $week; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-1 sm:mt-6">
                    <button type="submit" class="w-full rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white">View Week</button>
                </div>
            </form>
        </div>
        <div class="bg-white rounded-lg border overflow-hidden">
            <div class="p-4 border-b"><h2 class="text-lg font-semibold"><?php echo trans('status_for_week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></h2></div>
            <div class="md:hidden">
                <?php foreach ($membersList as $member): 
                    $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                    $amountPaid = $member['amount_paid'] ?? 0;
                    $status = $member['payment_status'] ?? 'Pending';
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    if ($status === 'Paid') { $statusClass = 'bg-green-100 text-green-800'; }
                    if ($status === 'Partial') { $statusClass = 'bg-blue-100 text-blue-800'; }
                ?>
                <div class="p-4 border-b">
                    <div class="flex justify-between items-center">
                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($member['member_name']); ?></p>
                        <button type="button" class="js-update-payment-btn text-sm font-medium text-primary px-3 py-1 rounded-lg hover:bg-blue-50"
                            data-member-id="<?php echo $member['member_id']; ?>" data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                            data-contribution-amount="<?php echo $member['contribution_amount']; ?>" data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                            data-total-due="<?php echo $totalDue; ?>" data-amount-paid="<?php echo $amountPaid; ?>"
                            data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>" data-status="<?php echo $status; ?>">
                            Update
                        </button>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <div>
                            <span class="font-medium text-slate-800"><?php echo formatCurrency($amountPaid); ?></span>
                            <span class="text-slate-500 text-sm">/ <?php echo formatCurrency($totalDue); ?></span>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left"><?php echo trans('member'); ?></th><th class="px-6 py-3 text-left"><?php echo trans('amount'); ?></th>
                            <th class="px-6 py-3 text-left"><?php echo trans('status'); ?></th><th class="px-6 py-3 text-right"><?php echo trans('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <?php foreach ($membersList as $member):
                            $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                            $amountPaid = $member['amount_paid'] ?? 0;
                            $status = $member['payment_status'] ?? 'Pending';
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            if ($status === 'Paid') { $statusClass = 'bg-green-100 text-green-800'; }
                            if ($status === 'Partial') { $statusClass = 'bg-blue-100 text-blue-800'; }
                        ?>
                        <tr>
                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($member['member_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-800"><?php echo formatCurrency($amountPaid); ?></span>
                                <span class="text-slate-500">/ <?php echo formatCurrency($totalDue); ?></span>
                            </td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" class="js-update-payment-btn text-sm font-medium text-primary hover:underline"
                                    data-member-id="<?php echo $member['member_id']; ?>" data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                                    data-contribution-amount="<?php echo $member['contribution_amount']; ?>" data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                                    data-total-due="<?php echo $totalDue; ?>" data-amount-paid="<?php echo $amountPaid; ?>"
                                    data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>" data-status="<?php echo $status; ?>">
                                    <?php echo trans('update'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="updatePaymentModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl">
        <form id="updatePaymentForm" action="<?php echo URL_ROOT; ?>/payments?week=<?php echo $selectedWeek; ?>&year=<?php echo $selectedYear; ?>" method="POST">
            <input type="hidden" name="action" value="process_payment_update">
            <input type="hidden" name="member_id" id="update_member_id">
            <input type="hidden" name="total_amount_due" id="update_total_amount_due">
            <div class="p-6">
                <h3 class="text-xl font-bold"><?php echo trans('update_payment'); ?></h3>
                <p class="text-sm text-slate-600 mb-6">For <strong id="update_member_name"></strong> - Week <?php echo $selectedWeek; ?></p>
                <div class="space-y-4">
                    <div class="text-center p-4 bg-slate-50 rounded-lg">
                        <p class="text-center py-10 text-slate-500"><?php echo trans('no_members_found'); ?></p>
                        <p class="text-sm text-slate-500"><?php echo trans('total_due_for_week'); ?></p>
                        <p id="total_due_display" class="text-2xl font-bold text-slate-800"></p>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex-1 p-4 border rounded-lg cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                            <input type="radio" name="payment_type" value="full" class="sr-only" checked>
                            <span class="font-medium"><?php echo trans('full_payment'); ?></span>
                        </label>
                         <label class="flex-1 p-4 border rounded-lg cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                            <input type="radio" name="payment_type" value="partial" class="sr-only">
                            <span class="font-medium">Partial Payment</span>
                        </label>
                    </div>
                    <div id="amount_paid_wrapper" class="hidden">
                        <label class="block text-sm">Amount Paid (₹)</label>
                        <input type="number" step="1" min="0" name="amount_paid" id="update_amount_paid" class="mt-1 block w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm">Notes (Optional)</label>
                        <textarea name="notes" id="update_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3 bg-slate-50 rounded-b-2xl">
                <button type="button" onclick="closeModal('updatePaymentModal')" class="px-5 py-2.5 rounded-lg border">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-primary text-white">Save Update</button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>