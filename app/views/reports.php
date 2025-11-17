<?php
$currentPage = 'reports';
$pageTitle = 'Aarfin - Reports';
require_once APP_ROOT . '/templates/header.php';

// --- GET FILTERS & DEFAULTS (respect global selection from Settings) ---
[$defaultWeek, $defaultYear] = get_selected_week_and_year();
$selectedYear = (int)($_GET['year'] ?? $defaultYear);
$selectedWeek = (int)($_GET['week'] ?? $defaultWeek);

// --- FETCH DATA FOR FILTERS (Dropdowns) ---
$currentYear = (int)date('Y');
$years_from_db = $pdo->query("SELECT DISTINCT payment_year FROM payments ORDER BY payment_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$available_years = array_unique(array_merge([$currentYear-1, $currentYear, $currentYear+1], $years_from_db));
rsort($available_years);
$available_weeks = range(1, 52);

// --- FETCH REPORT DATA BASED ON FILTERS ---
$params = [$selectedWeek, $selectedYear];

// Get Summary Stats for the selected week (from existing payment rows)
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(amount) as total FROM payments WHERE payment_week = ? AND payment_year = ? GROUP BY status");
$stmt->execute($params);
$statsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$reportStats = ['paid_count' => 0, 'paid_total' => 0, 'pending_count' => 0, 'pending_total' => 0];
foreach ($statsRows as $row) {
    if ($row['status'] === 'Paid') {
        $reportStats['paid_count'] = $row['count'];
        $reportStats['paid_total'] = $row['total'];
    } elseif ($row['status'] === 'Pending' || $row['status'] === 'Partial') {
        // We will override pending counts/totals using pendingMembers (including unpaid)
        $reportStats['pending_count'] += $row['count'];
        $reportStats['pending_total'] += $row['total'];
    }
}

// Get Detailed Payment List for the selected week

$stmt = $pdo->prepare("
    SELECT p.status, p.amount, m.name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    WHERE p.payment_week = ? AND p.payment_year = ?
    ORDER BY m.name ASC
");
$stmt->execute($params);
$paymentList = $stmt->fetchAll();

// Build pending/unpaid members list: all active members for this week with positive balance
$stmtPending = $pdo->prepare("
    SELECT
        m.name,
        m.contribution_amount,
        p.amount AS payment_amount,
        p.amount_paid,
        p.status
    FROM members m
    LEFT JOIN payments p
        ON p.member_id = m.id
        AND p.payment_week = ?
        AND p.payment_year = ?
    WHERE m.status = 'Active'
    ORDER BY m.name ASC
");
$stmtPending->execute($params);
$pendingRows = $stmtPending->fetchAll();

$pendingMembers = [];
foreach ($pendingRows as $row) {
    $due = $row['payment_amount'] !== null ? (float)$row['payment_amount'] : (float)$row['contribution_amount'];
    $paid = $row['amount_paid'] !== null ? (float)$row['amount_paid'] : 0.0;
    $balance = max(0, $due - $paid);

    if ($balance > 0) {
        $statusLabel = 'Unpaid';
        if ($paid > 0 && $paid < $due) {
            $statusLabel = 'Partial';
        }
        if ($row['status'] === 'Pending') {
            $statusLabel = 'Pending';
        }

        $pendingMembers[] = [
            'name' => $row['name'],
            'due' => $due,
            'paid' => $paid,
            'balance' => $balance,
            'status' => $statusLabel,
        ];
    }
}

// Override pending totals using all pending/unpaid members (including those without a payment row)
$pendingCountFromMembers = count($pendingMembers);
$pendingAmountFromMembers = 0;
foreach ($pendingMembers as $m) {
    $pendingAmountFromMembers += (float)$m['balance'];
}

$reportStats['pending_count'] = $pendingCountFromMembers;
$reportStats['pending_total'] = $pendingAmountFromMembers;

// Totals for KPI cards
$totalAmountAll = (float)$reportStats['paid_total'] + (float)$reportStats['pending_total'];
$totalPendingAll = (float)$reportStats['pending_total'];

?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 md:ml-64 px-4 pt-0 pb-20 lg:px-8 lg:pt-0 lg:pb-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 mb-4 border-b border-slate-200 bg-white px-4 py-2.5 lg:py-3 lg:-mx-8 lg:px-8 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between shadow-sm">
            <div class="flex items-baseline gap-3">
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900"><?php echo trans('reports'); ?></h1>
                <span class="text-xs md:text-sm text-slate-600">Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></span>
            </div>

            <a href="<?php echo URL_ROOT; ?>/generate_report?week=<?php echo $selectedWeek; ?>&year=<?php echo $selectedYear; ?>" class="ml-auto inline-flex items-center justify-center gap-2 rounded-md bg-red-600 hover:bg-red-700 px-4 py-1.5 text-xs md:text-sm font-medium text-white shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-file-pdf text-xs"></i>
                <span class="hidden sm:inline">Download PDF</span>
                <span class="inline sm:hidden">PDF</span>
            </a>
        </div>

        <!-- KPI row -->
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-lg border border-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Amount</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900"><?php echo formatCurrency($totalAmountAll); ?></p>
                        <p class="mt-2 text-[0.7rem] text-slate-500 flex items-center gap-1">
                            <i class="fas fa-layer-group text-[0.65rem]"></i>
                            <span>All payments for this week</span>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                        <i class="fas fa-wallet text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-lg border border-emerald-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">Paid</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600"><?php echo formatCurrency($reportStats['paid_total']); ?></p>
                        <p class="mt-1 text-[0.7rem] text-slate-500 flex items-center gap-1">
                            <i class="fas fa-user-check text-[0.65rem]"></i>
                            <span><?php echo $reportStats['paid_count']; ?> members fully/partially paid</span>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shadow-sm">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-lg border border-rose-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-rose-600">Pending</p>
                        <p class="mt-2 text-2xl font-bold text-rose-600"><?php echo formatCurrency($totalPendingAll); ?></p>
                        <p class="mt-1 text-[0.7rem] text-slate-500 flex items-center gap-1">
                            <i class="fas fa-user-times text-[0.65rem]"></i>
                            <span><?php echo $reportStats['pending_count']; ?> members pending / partial</span>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 shadow-sm">
                        <i class="fas fa-exclamation-circle text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main analytics layout -->
        <div class="space-y-6">
            <!-- Status breakdown -->
            <div class="rounded-xl bg-white shadow-lg border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Status Breakdown</h2>
                        <p class="mt-1 text-[0.7rem] text-slate-500">How this week's amounts are split across statuses.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50/40 px-3 py-2">
                        <p class="text-[0.7rem] font-medium text-emerald-600 flex items-center gap-1">
                            <i class="fas fa-check-circle text-[0.65rem]"></i>
                            <span>Paid</span>
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900"><?php echo formatCurrency($reportStats['paid_total']); ?></p>
                        <p class="mt-0.5 text-[0.7rem] text-slate-500"><?php echo $reportStats['paid_count']; ?> entries</p>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50/40 px-3 py-2">
                        <p class="text-[0.7rem] font-medium text-blue-600 flex items-center gap-1">
                            <i class="fas fa-adjust text-[0.65rem]"></i>
                            <span>Partial</span>
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900"><?php echo formatCurrency($reportStats['pending_total'] - $totalPendingAll); ?></p>
                        <p class="mt-0.5 text-[0.7rem] text-slate-500">Included in pending total</p>
                    </div>

                    <div class="rounded-lg border border-rose-100 bg-rose-50/40 px-3 py-2">
                        <p class="text-[0.7rem] font-medium text-rose-600 flex items-center gap-1">
                            <i class="fas fa-hourglass-half text-[0.65rem]"></i>
                            <span>Pending</span>
                        </p>
                        <p class="mt-1 text-sm font-semibold text-rose-600"><?php echo formatCurrency($totalPendingAll); ?></p>
                        <p class="mt-0.5 text-[0.7rem] text-slate-500"><?php echo $reportStats['pending_count']; ?> entries</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats & Tips row -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-xl bg-white shadow-lg border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-slate-900 mb-3">Quick Stats</h2>
                    <div class="space-y-3 text-[0.8rem] text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fas fa-user-check text-emerald-500 text-[0.7rem]"></i> Paid entries</span>
                            <span class="font-semibold text-slate-900"><?php echo $reportStats['paid_count']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fas fa-user-clock text-amber-500 text-[0.7rem]"></i> Pending / partial entries</span>
                            <span class="font-semibold text-slate-900"><?php echo $reportStats['pending_count']; ?></span>
                        </div>
                        <div class="mt-3">
                            <p class="text-[0.7rem] text-slate-500 mb-1">Pending vs Total</p>
                            <?php
                                $pendingPercent = $totalAmountAll > 0 ? round(($totalPendingAll / $totalAmountAll) * 100) : 0;
                            ?>
                            <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-2 rounded-full bg-rose-500" style="width: <?php echo $pendingPercent; ?>%;"></div>
                            </div>
                            <p class="mt-1 text-[0.7rem] text-slate-500 flex items-center justify-between">
                                <span><?php echo formatCurrency($totalPendingAll); ?> pending</span>
                                <span class="font-semibold text-rose-600"><?php echo $pendingPercent; ?>%</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-900 text-slate-50 shadow-lg border border-slate-800 p-5">
                    <h2 class="text-sm font-semibold mb-2 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-[0.7rem]"><i class="fas fa-lightbulb"></i></span>
                        <span>Tips</span>
                    </h2>
                    <p class="text-[0.7rem] text-slate-300 mb-2">Use this report to quickly see who still needs to pay for the selected week.</p>
                    <ul class="text-[0.7rem] text-slate-300 space-y-1 list-disc list-inside">
                        <li>Switch weeks from Settings to compare trends.</li>
                        <li>Export a PDF summary for offline sharing.</li>
                        <li>Focus on the pending list to follow up with members.</li>
                    </ul>
                </div>
            </div>

            <!-- Pending / Unpaid Members -->
            <div class="rounded-xl bg-white shadow-lg border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Pending / Unpaid Members</h2>
                        <p class="mt-1 text-[0.7rem] text-slate-500">Active members who still have a balance for this week.</p>
                    </div>
                </div>

                <!-- Desktop table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Member</th>
                                <th class="px-4 py-2 text-right text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Due</th>
                                <th class="px-4 py-2 text-right text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Paid</th>
                                <th class="px-4 py-2 text-right text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Balance</th>
                                <th class="px-4 py-2 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <?php if (count($pendingMembers) > 0): ?>
                                <?php foreach ($pendingMembers as $m): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($m['name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-slate-900"><?php echo formatCurrency($m['due']); ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-slate-700"><?php echo formatCurrency($m['paid']); ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-rose-600"><?php echo formatCurrency($m['balance']); ?></td>
                                        <td class="px-4 py-3">
                                            <?php
                                                $status = $m['status'];
                                                $badgeClasses = 'bg-orange-50 text-orange-700 border border-orange-100';
                                                if ($status === 'Paid') {
                                                    $badgeClasses = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                                                } elseif ($status === 'Partial') {
                                                    $badgeClasses = 'bg-blue-50 text-blue-700 border border-blue-100';
                                                }
                                            ?>
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $badgeClasses; ?>"><?php echo htmlspecialchars($status); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No pending members for this week.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile list -->
                <div class="md:hidden">
                    <?php if (count($pendingMembers) > 0): ?>
                        <?php foreach ($pendingMembers as $m): ?>
                            <div class="border-t border-slate-100 py-3">
                                <div class="flex justify-between items-start gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($m['name']); ?></p>
                                        <p class="mt-0.5 text-[0.7rem] text-slate-500">Due: <?php echo formatCurrency($m['due']); ?> · Paid: <?php echo formatCurrency($m['paid']); ?></p>
                                        <p class="mt-0.5 text-[0.7rem] font-semibold text-rose-600">Balance: <?php echo formatCurrency($m['balance']); ?></p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <?php
                                            $status = $m['status'];
                                            $badgeClasses = 'bg-orange-50 text-orange-700 border border-orange-100';
                                            if ($status === 'Paid') {
                                                $badgeClasses = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                                            } elseif ($status === 'Partial') {
                                                $badgeClasses = 'bg-blue-50 text-blue-700 border border-blue-100';
                                            }
                                        ?>
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $badgeClasses; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-6 text-center text-sm text-slate-500">No pending members for this week.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detailed Payments (scrollable, full width) -->
            <div class="flex flex-col">
                <div class="flex-1 rounded-xl bg-white shadow-lg border border-slate-200 overflow-hidden flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                        <h2 class="text-sm font-semibold text-slate-900">Detailed Payments</h2>
                        <span class="text-[0.7rem] text-slate-500">Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></span>
                    </div>
                    <div class="hidden md:block overflow-x-auto overflow-y-auto flex-1">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Member</th>
                                    <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <?php if (count($paymentList) > 0): ?>
                                    <?php foreach ($paymentList as $payment): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($payment['name']); ?></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo formatCurrency($payment['amount']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($payment['status'] === 'Partial' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-orange-50 text-orange-700 border border-orange-100'); ?>">
                                                <?php echo htmlspecialchars($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-10 text-sm text-slate-500">No payment data found for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile list -->
                    <div class="md:hidden">
                        <?php if (count($paymentList) > 0): ?>
                            <?php foreach ($paymentList as $payment): ?>
                            <div class="p-4 border-t border-slate-100">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($payment['name']); ?></p>
                                        <p class="mt-0.5 text-[0.7rem] text-slate-500">Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($payment['amount']); ?></p>
                                        <span class="inline-flex mt-1 px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($payment['status'] === 'Partial' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-orange-50 text-orange-700 border border-orange-100'); ?>">
                                            <?php echo htmlspecialchars($payment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-10 text-center text-sm text-slate-500">No payment data found for this period.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>