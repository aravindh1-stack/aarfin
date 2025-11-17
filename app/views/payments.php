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

    <main class="flex-1 md:ml-64 px-4 pt-0 pb-20 lg:px-8 lg:pt-0 lg:pb-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 mb-8 border-b border-slate-200 bg-white px-4 py-4 lg:py-5 lg:-mx-8 lg:px-8 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between shadow-sm">
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900"><?php echo trans('weekly_payment_status'); ?></h1>
                <p class="text-xs md:text-sm text-slate-600"><?php echo trans('status_for_week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
            </div>
            <div class="flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 min-w-[220px] max-w-xs">
                <i class="fas fa-search text-slate-400 text-xs"></i>
                <input id="paymentSearchInput" type="text" placeholder="Search payments..." class="ml-2 w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-0" />
            </div>
        </div>

        <!-- Payments list -->
        <div class="rounded-xl bg-white shadow-lg border border-slate-200 overflow-hidden">
            <!-- Summary header: three KPI cards like members page -->
            <div class="px-4 pt-4 pb-5 border-b border-slate-100 bg-slate-50/40">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500"><?php echo trans('total_due') ?? 'Total Due'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-slate-900"><?php echo formatCurrency($totalDueAll); ?></p>
                    </div>
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600"><?php echo trans('total_collected') ?? 'Total Collected'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600"><?php echo formatCurrency($totalCollectedAll); ?></p>
                    </div>
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-rose-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-rose-600"><?php echo trans('total_pending') ?? 'Total Pending'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-rose-600"><?php echo formatCurrency($totalPendingAll); ?></p>
                    </div>
                </div>
            </div>

            <!-- Mobile cards: compact view, tap to open update modal -->
            <div class="md:hidden p-3 space-y-3 bg-slate-50/60" id="paymentMobileList">
                <?php foreach ($membersList as $member): 
                    $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                    $amountPaid = $member['amount_paid'] ?? 0;
                    $status = $member['payment_status'] ?? 'Pending';
                ?>
                <div
                    class="rounded-2xl bg-white p-3 shadow-sm border border-slate-100 js-payment-card"
                    data-name="<?php echo htmlspecialchars(strtolower($member['member_name'])); ?>"
                    data-phone="<?php echo htmlspecialchars(strtolower($member['phone'])); ?>"
                >
                    <button
                        type="button"
                        class="js-update-payment-btn w-full text-left flex items-center justify-between gap-3"
                        data-member-id="<?php echo $member['member_id']; ?>"
                        data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                        data-contribution-amount="<?php echo $member['contribution_amount']; ?>"
                        data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                        data-total-due="<?php echo $totalDue; ?>"
                        data-amount-paid="<?php echo $amountPaid; ?>"
                        data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>"
                        data-status="<?php echo $status; ?>"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-sm font-semibold">
                                <?php echo strtoupper(substr($member['member_name'], 0, 1)); ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($member['member_name']); ?></p>
                                <p class="text-[0.7rem] text-slate-500 mt-0.5">
                                    <?php echo trans('week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?>
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[0.7rem] font-medium text-blue-700">
                            <i class="fas fa-pen text-[0.65rem]"></i>
                            <span><?php echo trans('update') ?? 'Update'; ?></span>
                        </span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm align-middle">
                    <thead class="bg-slate-50/80 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-500 text-xs">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <span class="leading-tight"><?php echo trans('member'); ?></span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 text-xs">
                                        <i class="fas fa-indian-rupee-sign"></i>
                                    </span>
                                    <span class="leading-tight"><?php echo trans('amount'); ?></span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-white text-[0.6rem]">
                                        <i class="fas fa-signal"></i>
                                    </span>
                                    <span class="leading-tight"><?php echo trans('status'); ?></span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-right text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center justify-end gap-2 w-full">
                                    <span class="leading-tight"><?php echo trans('actions'); ?></span>
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-xs">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100" id="paymentDesktopTableBody">
                        <?php foreach ($membersList as $member):
                            $totalDue = $member['total_due'] ?? $member['contribution_amount'];
                            $amountPaid = $member['amount_paid'] ?? 0;
                            $status = $member['payment_status'] ?? 'Pending';
                            $statusClass = 'bg-orange-50 text-orange-700 border border-orange-100';
                            if ($status === 'Paid') { $statusClass = 'bg-emerald-50 text-emerald-700 border border-emerald-100'; }
                            if ($status === 'Partial') { $statusClass = 'bg-blue-50 text-blue-700 border border-blue-100'; }
                            $balance = max(0, (float)$totalDue - (float)$amountPaid);
                            $balanceText = formatCurrency($balance);
                            $memberPhone = htmlspecialchars($member['phone'] ?? '');
                            $encodedPhone = urlencode($member['phone'] ?? '');
                            $reminderText = rawurlencode("Hi " . $member['member_name'] . ", your weekly payment balance is " . $balanceText . ". Please pay as soon as possible. Thank you.");
                        ?>
                        <tr
                            class="hover:bg-slate-50 js-payment-row"
                            data-name="<?php echo htmlspecialchars(strtolower($member['member_name'])); ?>"
                            data-phone="<?php echo htmlspecialchars(strtolower($member['phone'])); ?>"
                        >
                            <!-- Member / week / contact -->
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-sm font-semibold">
                                        <?php echo strtoupper(substr($member['member_name'], 0, 1)); ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($member['member_name']); ?></p>
                                        <p class="text-[0.7rem] text-slate-400 mt-0.5"><?php echo trans('week'); ?> <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                                        <?php if (!empty($memberPhone)): ?>
                                            <p class="text-[0.7rem] text-slate-500 mt-1 flex items-center gap-1">
                                                <i class="fas fa-phone-alt fa-xs"></i>
                                                <span class="truncate"><?php echo $memberPhone; ?></span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Amount paid / due -->
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <div class="inline-flex items-center gap-1">
                                        <span class="inline-flex h-6 items-center rounded-full bg-emerald-50 px-2 text-xs font-semibold text-emerald-700">
                                            <?php echo formatCurrency($amountPaid); ?>
                                        </span>
                                        <span class="text-[0.7rem] text-slate-500">/ <?php echo formatCurrency($totalDue); ?></span>
                                    </div>
                                    <?php if ($balance > 0): ?>
                                        <p class="text-[0.7rem] text-slate-500">
                                            <?php echo trans('total_pending') ?? 'Pending'; ?>: <span class="font-semibold text-amber-600"><?php echo $balanceText; ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php
                                        $statusIcon = 'fa-hourglass-half';
                                        if ($status === 'Paid') $statusIcon = 'fa-check-circle';
                                        if ($status === 'Partial') $statusIcon = 'fa-adjust';
                                    ?>
                                    <i class="fas <?php echo $statusIcon; ?> text-[0.65rem]"></i>
                                    <span><?php echo $status; ?></span>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right align-top">
                                <button
                                    type="button"
                                    class="js-update-payment-btn inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50 transition-colors duration-150"
                                    data-member-id="<?php echo $member['member_id']; ?>"
                                    data-member-name="<?php echo htmlspecialchars($member['member_name']); ?>"
                                    data-contribution-amount="<?php echo $member['contribution_amount']; ?>"
                                    data-payment-id="<?php echo $member['payment_id'] ?? ''; ?>"
                                    data-total-due="<?php echo $totalDue; ?>"
                                    data-amount-paid="<?php echo $amountPaid; ?>"
                                    data-notes="<?php echo htmlspecialchars($member['notes'] ?? ''); ?>"
                                    data-status="<?php echo $status; ?>"
                                >
                                    <i class="fas fa-pen text-[0.65rem]"></i>
                                    <span><?php echo trans('update') ?? 'Update'; ?></span>
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