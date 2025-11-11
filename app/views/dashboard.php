<?php
$currentPage = 'dashboard';
$pageTitle = trans('app_name') . ' - ' . trans('dashboard');
require_once APP_ROOT . '/templates/header.php';

// --- PHP data fetching logic is unchanged ---
$currentWeek = (int)date('W');
$currentYear = (int)date('Y');
$totalActiveMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'Active'")->fetchColumn();
$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM payments WHERE payment_week = ? AND payment_year = ? AND status IN ('Paid', 'Partial')");
$stmt->execute([$currentWeek, $currentYear]);
$weeklyCollection = $stmt->fetchColumn() ?? 0;
$stmt = $pdo->prepare("SELECT SUM(amount - amount_paid) FROM payments WHERE payment_week = ? AND payment_year = ? AND status IN ('Pending', 'Partial')");
$stmt->execute([$currentWeek, $currentYear]);
$weeklyPendingAmount = $stmt->fetchColumn() ?? 0;
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) FROM payments WHERE payment_week = ? AND payment_year = ? AND status IN ('Pending', 'Partial')");
$stmt->execute([$currentWeek, $currentYear]);
$pendingMembersCount = $stmt->fetchColumn() ?? 0;
$stmt = $pdo->prepare("
    SELECT m.name, m.phone, p.amount, p.amount_paid 
    FROM payments p JOIN members m ON p.member_id = m.id
    WHERE p.payment_week = ? AND p.payment_year = ? AND p.status IN ('Pending', 'Partial')
    ORDER BY m.name ASC
");
$stmt->execute([$currentWeek, $currentYear]);
$pendingMembersList = $stmt->fetchAll();
$recentPayments = $pdo->query("
    SELECT p.amount_paid, p.payment_date, m.name as member_name
    FROM payments p JOIN members m ON p.member_id = m.id
    WHERE p.status IN ('Paid', 'Partial') AND p.amount_paid > 0
    ORDER BY p.payment_date DESC, p.created_at DESC LIMIT 5
")->fetchAll();
?>

<div class="relative min-h-screen md:flex">
    
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>

    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-slate-100">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><?php echo trans('dashboard'); ?></h1>
            <p class="mt-1 text-sm text-slate-600"><?php echo trans('dashboard_welcome'); ?> <?php echo trans('week') . ' ' . $currentWeek . ', ' . $currentYear; ?>.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-500"><?php echo trans('active_members'); ?></p>
                        <p class="text-sm font-medium text-slate-500"><?php echo trans('active_members'); ?></p>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $totalActiveMembers; ?></p>
                    </div>
                    <div class="bg-blue-100 text-blue-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
                 <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-500"><?php echo trans('this_week_collection'); ?></p>
                        <p class="text-2xl font-bold text-green-600"><?php echo formatCurrency($weeklyCollection); ?></p>
                    </div>
                    <div class="bg-green-100 text-green-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
                 <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-500"><?php echo trans('pending_this_week'); ?></p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo formatCurrency($weeklyPendingAmount); ?></p>
                    </div>
                    <div class="bg-yellow-100 text-yellow-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
                 <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-500"><?php echo trans('unpaid_members'); ?></p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $pendingMembersCount; ?></p>
                    </div>
                    <div class="bg-red-100 text-red-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800 mb-4"><?php echo trans('pending_members_list'); ?></h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <tbody class="divide-y divide-slate-200">
                            <?php if (count($pendingMembersList) > 0): ?>
                                <?php foreach ($pendingMembersList as $member): ?>
                                <tr>
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($member['name']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($member['phone']); ?></p>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <p class="font-semibold text-red-600">Balance: <?php echo formatCurrency($member['amount'] - $member['amount_paid']); ?></p>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="py-10 text-center text-slate-500">
                                        <div class="text-green-500 mb-2"><i class="fas fa-check-circle fa-2x"></i></div>
                                        <p><?php echo trans('all_payments_collected'); ?></p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800 mb-4"><?php echo trans('recent_payments'); ?></h2>
                <div class="space-y-4">
                    <?php if (count($recentPayments) > 0): ?>
                        <?php foreach ($recentPayments as $payment): ?>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($payment['member_name']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo formatDate($payment['payment_date']); ?></p>
                            </div>
                            <p class="font-semibold text-green-600">+<?php echo formatCurrency($payment['amount_paid']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center py-10 text-slate-500"><?php echo trans('no_recent_payments'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>