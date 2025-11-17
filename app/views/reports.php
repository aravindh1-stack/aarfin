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

// Get Summary Stats for the selected week
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(amount) as total FROM payments WHERE payment_week = ? AND payment_year = ? GROUP BY status");
$stmt->execute($params);
$statsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$reportStats = ['paid_count' => 0, 'paid_total' => 0, 'pending_count' => 0, 'pending_total' => 0];
foreach ($statsRows as $row) {
    if ($row['status'] === 'Paid') {
        $reportStats['paid_count'] = $row['count'];
        $reportStats['paid_total'] = $row['total'];
    } elseif ($row['status'] === 'Pending' || $row['status'] === 'Partial') {
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
// Totals from summary stats
$totalAmountAll = (float)$reportStats['paid_total'] + (float)$reportStats['pending_total'];
$totalPendingAll = (float)$reportStats['pending_total'];
?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 -mt-2 mb-8 border-b border-slate-200 bg-white px-4 py-3 lg:-mx-8 lg:px-8 flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 lg:text-2xl">Weekly Reports</h1>
            <p class="text-xs text-slate-500">Report for Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
        </div>

        <!-- Report content -->
        <div class="rounded-2xl bg-white shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Summary</h2>
                    <p class="text-[0.7rem] text-slate-500 mt-0.5">Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></p>
                </div>
                <div class="grid grid-cols-1 gap-2 text-[0.75rem] sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 px-3 py-2 border border-slate-100">
                        <p class="text-[0.7rem] font-medium text-slate-500">Total Amount</p>
                        <p class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($totalAmountAll); ?></p>
                    </div>
                    <div class="rounded-xl bg-rose-50 px-3 py-2 border border-rose-100">
                        <p class="text-[0.7rem] font-medium text-rose-600">Total Pending</p>
                        <p class="text-sm font-semibold text-rose-700"><?php echo formatCurrency($totalPendingAll); ?></p>
                    </div>
                </div>
                <?php if (count($paymentList) > 0): ?>
                <a href="<?php echo URL_ROOT; ?>/generate_report?week=<?php echo $selectedWeek; ?>&year=<?php echo $selectedYear; ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-rose-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-rose-700">
                    <i class="fas fa-file-pdf text-xs"></i><span class="hidden sm:inline">Download PDF</span>
                </a>
                <?php endif; ?>
            </div>
            <div class="md:hidden">
                <?php if (count($paymentList) > 0): ?>
                    <?php foreach ($paymentList as $payment): ?>
                    <div class="p-4 border-b border-slate-100">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($payment['name']); ?></p>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-green-100 text-green-800' : ($payment['status'] === 'Partial' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-700'); ?>">
                                <?php echo htmlspecialchars($payment['status']); ?>
                            </span>
                        </div>
                        <div class="mt-2 text-right">
                            <p class="text-sm font-semibold text-slate-900"><?php echo formatCurrency($payment['amount']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-10 text-center text-sm text-slate-500">No payment data found for this period.</div>
                <?php endif; ?>
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-[0.68rem] font-semibold tracking-wide text-slate-500 uppercase">Member Name</th>
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
                                <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-green-100 text-green-800' : ($payment['status'] === 'Partial' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-700'); ?>"><?php echo htmlspecialchars($payment['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-10 text-sm text-slate-500">No payment data found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>