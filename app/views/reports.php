<?php
$currentPage = 'reports';
$pageTitle = 'PayCircle - Reports';
require_once APP_ROOT . '/templates/header.php';

// --- GET FILTERS & DEFAULTS ---
$currentYear = (int)date('Y');
$currentWeek = (int)date('W');
$selectedYear = (int)($_GET['year'] ?? $currentYear);
$selectedWeek = (int)($_GET['week'] ?? $currentWeek);

// --- FETCH DATA FOR FILTERS (Dropdowns) ---
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
?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 p-4 sm:p-6 bg-slate-100">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Weekly Reports</h1>

        <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
            <form action="<?php echo URL_ROOT; ?>/reports" method="GET" class="flex flex-col sm:flex-row items-center gap-4">
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
                    <button type="submit" class="w-full rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white">View Report</button>
                </div>
            </form>
        </div>
        
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-slate-700">Report for Week <?php echo $selectedWeek; ?>, <?php echo $selectedYear; ?></h2>
            <?php if (count($paymentList) > 0): ?>
            <a href="<?php echo URL_ROOT; ?>/generate_report?week=<?php echo $selectedWeek; ?>&year=<?php echo $selectedYear; ?>" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white">
                <i class="fas fa-file-pdf"></i><span class="hidden sm:inline"> Download as PDF</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg border overflow-hidden">
            <div class="md:hidden">
                <?php if (count($paymentList) > 0): ?>
                    <?php foreach ($paymentList as $payment): ?>
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-center">
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($payment['name']); ?></p>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-green-100 text-green-800' : ($payment['status'] === 'Partial' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo htmlspecialchars($payment['status']); ?>
                            </span>
                        </div>
                        <div class="mt-2 text-right">
                            <p class="text-sm font-medium"><?php echo formatCurrency($payment['amount']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-10 text-center text-slate-500">No payment data found for this period.</div>
                <?php endif; ?>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Member Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <?php if (count($paymentList) > 0): ?>
                            <?php foreach ($paymentList as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($payment['name']); ?></td>
                                <td class="px-6 py-4 font-medium"><?php echo formatCurrency($payment['amount']); ?></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $payment['status'] === 'Paid' ? 'bg-green-100 text-green-800' : ($payment['status'] === 'Partial' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'); ?>"><?php echo htmlspecialchars($payment['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-10 text-slate-500">No payment data found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>