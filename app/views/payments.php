<?php
$currentPage = 'payments';
$pageTitle = trans('app_name') . ' - ' . trans('weekly_status');
require_once APP_ROOT . '/templates/header.php';

// --- (PHP logic for filters and POST handling is correct and unchanged) ---
[$defaultWeek, $defaultYear] = get_selected_week_and_year();
$selectedYear = (int)($_GET['year'] ?? $defaultYear);
$selectedWeek = (int)($_GET['week'] ?? $defaultWeek);
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
$stmt = $pdo->prepare("SELECT m.id AS member_id, m.name AS member_name, m.phone, m.contribution_amount, p.id AS payment_id, p.amount AS total_due, p.status AS payment_status, p.amount_paid, p.notes FROM members m LEFT JOIN payments p ON m.id = p.member_id AND p.payment_week = ? AND p.payment_year = ? WHERE m.status = 'Active' ORDER BY m.name ASC");
$stmt->execute([$selectedWeek, $selectedYear]);
$membersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals for this week
$totalDueAll = 0;
$totalCollectedAll = 0;
foreach ($membersList as $m) {
    $due = $m['total_due'] ?? $m['contribution_amount'];
    $paid = $m['amount_paid'] ?? 0;
    $totalDueAll += (float)$due;
    $totalCollectedAll += (float)$paid;
}
$totalPendingAll = max(0, $totalDueAll - $totalCollectedAll);
?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 -mt-2 mb-8 border-b border-slate-200 bg-white px-4 py-5 lg:-mx-8 lg:px-8 flex flex-col gap-1 shadow-sm">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?php echo trans('weekly_payment_status'); ?></h1>
            <p class="text-sm text-slate-600"><?php echo trans('status_for_week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
        </div>

        <!-- Payments list -->
        <div class="rounded-xl bg-white shadow-lg border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900"><?php echo trans('status_for_week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></h2>
                    <p class="text-[0.7rem] text-slate-500 mt-0.5"><?php echo trans('active_members'); ?>: <?php echo count($membersList); ?></p>
                </div>
                <div class="grid grid-cols-1 gap-2 text-[0.75rem] sm:grid-cols-3"> 
                    <div class="rounded-xl bg-slate-50 px-3 py-2 border border-slate-100">
                        <p class="text-[0.7rem] font-medium text-slate-500"><?php echo trans('total_due') ?? 'Total Due'; ?></p>
                        <p class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($totalDueAll); ?></p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 border border-emerald-100">
                        <p class="text-[0.7rem] font-medium text-emerald-600"><?php echo trans('total_collected') ?? 'Total Collected'; ?></p>
                        <p class="text-sm font-semibold text-emerald-700"><?php echo formatCurrency($totalCollectedAll); ?></p>
                    </div>
                    <div class="rounded-xl bg-rose-50 px-3 py-2 border border-rose-100">
                        <p class="text-[0.7rem] font-medium text-rose-600"><?php echo trans('total_pending') ?? 'Total Pending'; ?></p>
                        <p class="text-sm font-semibold text-rose-700"><?php echo formatCurrency($totalPendingAll); ?></p>
                    </div>
                </div>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden">
                <?php foreach ($membersList as $member): 
                    $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                    $amountPaid = $member['amount_paid'] ?? 0;
                    $status = $member['payment_status'] ?? 'Pending';
                    $statusClass = 'bg-orange-100 text-orange-700';
                    if ($status === 'Paid') { $statusClass = 'bg-green-100 text-green-800'; }
                    if ($status === 'Partial') { $statusClass = 'bg-blue-100 text-blue-800'; }
                    $balance = max(0, (float)$totalDue - (float)$amountPaid);
                    $balanceText = formatCurrency($balance);
                    $memberPhone = htmlspecialchars($member['phone'] ?? '');
                    $encodedPhone = urlencode($member['phone'] ?? '');
                    $reminderText = rawurlencode("Hi " . $member['member_name'] . ", your weekly payment balance is " . $balanceText . ". Please pay as soon as possible. Thank you.");
                ?>
                <div class="p-4 border-b border-slate-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($member['member_name']); ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo trans('week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                            <?php if (!empty($memberPhone)): ?>
                                <p class="text-xs text-slate-500 mt-0.5"><i class="fas fa-phone-alt fa-xs mr-1"></i><?php echo $memberPhone; ?></p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="js-update-payment-btn inline-flex items-center gap-1 text-xs font-medium text-blue-600 px-3 py-1.5 rounded-full border border-blue-100 bg-blue-50 hover:bg-blue-100"
                            data-member-id="<?php echo $member['member_id']; ?>" data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                            data-contribution-amount="<?php echo $member['contribution_amount']; ?>" data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                            data-total-due="<?php echo $totalDue; ?>" data-amount-paid="<?php echo $amountPaid; ?>"
                            data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>" data-status="<?php echo $status; ?>">
                            <i class="fas fa-pen text-[0.65rem]"></i>
                            <span><?php echo trans('update') ?? 'Update'; ?></span>
                        </button>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <div>
                            <span class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($amountPaid); ?></span>
                            <span class="text-xs text-slate-500">/ <?php echo formatCurrency($totalDue); ?></span>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                    </div>
                    <?php if ($status !== 'Paid' && !empty($memberPhone) && $balance > 0): ?>
                    <div class="mt-3 flex flex-wrap gap-1.5 text-[0.7rem]">
                        <a href="tel:<?php echo $memberPhone; ?>" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-600 hover:bg-emerald-100">
                            <i class="fas fa-phone text-[0.65rem]"></i>
                            <span>Call</span>
                        </a>
                        <a href="https://wa.me/<?php echo $encodedPhone; ?>?text=<?php echo $reminderText; ?>" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-600 hover:bg-green-100">
                            <i class="fab fa-whatsapp text-[0.75rem]"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="sms:<?php echo $memberPhone; ?>?body=<?php echo $reminderText; ?>" class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-600 hover:bg-sky-100">
                            <i class="fas fa-comment-dots text-[0.65rem]"></i>
                            <span>SMS</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase"><?php echo trans('member'); ?></th>
                            <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase"><?php echo trans('amount'); ?></th>
                            <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase"><?php echo trans('status'); ?></th>
                            <th class="px-6 py-3 text-right text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase"><?php echo trans('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <?php foreach ($membersList as $member):
                            $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                            $amountPaid = $member['amount_paid'] ?? 0;
                            $status = $member['payment_status'] ?? 'Pending';
                            $statusClass = 'bg-orange-100 text-orange-700';
                            if ($status === 'Paid') { $statusClass = 'bg-green-100 text-green-800'; }
                            if ($status === 'Partial') { $statusClass = 'bg-blue-100 text-blue-800'; }
                            $balance = max(0, (float)$totalDue - (float)$amountPaid);
                            $balanceText = formatCurrency($balance);
                            $memberPhone = htmlspecialchars($member['phone'] ?? '');
                            $encodedPhone = urlencode($member['phone'] ?? '');
                            $reminderText = rawurlencode("Hi " . $member['member_name'] . ", your weekly payment balance is " . $balanceText . ". Please pay as soon as possible. Thank you.");
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 align-top">
                                <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($member['member_name']); ?></p>
                                <p class="text-[0.7rem] text-slate-400 mt-0.5"><?php echo trans('week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                                <?php if (!empty($memberPhone)): ?>
                                    <p class="text-[0.7rem] text-slate-500 mt-0.5"><i class="fas fa-phone-alt fa-xs mr-1"></i><?php echo $memberPhone; ?></p>
                                <?php endif; ?>
                                <?php if ($status !== 'Paid' && !empty($memberPhone) && $balance > 0): ?>
                                    <div class="mt-2 flex flex-wrap gap-1.5 text-[0.7rem]">
                                        <a href="tel:<?php echo $memberPhone; ?>" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-600 hover:bg-emerald-100">
                                            <i class="fas fa-phone text-[0.65rem]"></i>
                                            <span>Call</span>
                                        </a>
                                        <a href="https://wa.me/<?php echo $encodedPhone; ?>?text=<?php echo $reminderText; ?>" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-600 hover:bg-green-100">
                                            <i class="fab fa-whatsapp text-[0.75rem]"></i>
                                            <span>WhatsApp</span>
                                        </a>
                                        <a href="sms:<?php echo $memberPhone; ?>?body=<?php echo $reminderText; ?>" class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-600 hover:bg-sky-100">
                                            <i class="fas fa-comment-dots text-[0.65rem]"></i>
                                            <span>SMS</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($amountPaid); ?></span>
                                <span class="ml-1 text-xs text-slate-500">/ <?php echo formatCurrency($totalDue); ?></span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-6 py-4 text-right align-top">
                                <button type="button" class="js-update-payment-btn inline-flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-50"
                                    data-member-id="<?php echo $member['member_id']; ?>" data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                                    data-contribution-amount="<?php echo $member['contribution_amount']; ?>" data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                                    data-total-due="<?php echo $totalDue; ?>" data-amount-paid="<?php echo $amountPaid; ?>"
                                    data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>" data-status="<?php echo $status; ?>">
                                    <i class="fas fa-pen text-xs"></i>
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
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl border border-slate-200">
        <form id="updatePaymentForm" action="<?php echo URL_ROOT; ?>/payments?week=<?php echo $selectedWeek; ?>&year=<?php echo $selectedYear; ?>" method="POST">
            <input type="hidden" name="action" value="process_payment_update">
            <input type="hidden" name="member_id" id="update_member_id">
            <input type="hidden" name="total_amount_due" id="update_total_amount_due">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900"><?php echo trans('update_payment'); ?></h3>
                <p class="text-xs text-slate-500 mb-4">For <span id="update_member_name" class="font-medium text-slate-900"></span> · <?php echo trans('week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                <div class="space-y-4">
                    <div class="text-center p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 mb-1"><?php echo trans('total_due_for_week'); ?></p>
                        <p id="total_due_display" class="text-2xl font-semibold text-slate-900"></p>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex-1 p-4 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                            <input type="radio" name="payment_type" value="full" class="sr-only" checked>
                            <span class="text-sm font-semibold text-slate-900"><?php echo trans('full_payment'); ?></span>
                        </label>
                         <label class="flex-1 p-4 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                            <input type="radio" name="payment_type" value="partial" class="sr-only">
                            <span class="text-sm font-semibold text-slate-900">Partial Payment</span>
                        </label>
                    </div>
                    <div id="amount_paid_wrapper" class="hidden">
                        <label class="block text-xs font-medium text-slate-600">Amount Paid (₹)</label>
                        <input type="number" step="1" min="0" name="amount_paid" id="update_amount_paid" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Notes (Optional)</label>
                        <textarea name="notes" id="update_notes" rows="2" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"></textarea>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3 bg-slate-50 rounded-b-xl border-t border-slate-200">
                <button type="button" onclick="closeModal('updatePaymentModal')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors duration-200">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-sm font-medium text-white shadow-md hover:shadow-lg transition-all duration-200">Save Update</button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>