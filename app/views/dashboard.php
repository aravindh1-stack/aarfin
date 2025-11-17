<?php
$currentPage = 'dashboard';
$pageTitle = trans('app_name') . ' - ' . trans('dashboard');
require_once APP_ROOT . '/templates/header.php';

// --- Week/year come from global selection (Settings) ---
[$currentWeek, $currentYear] = get_selected_week_and_year();
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
// Derived stats for overview
$weeklyTotalDue = (float)$weeklyCollection + (float)$weeklyPendingAmount;
$collectionPercent = $weeklyTotalDue > 0 ? round(($weeklyCollection / $weeklyTotalDue) * 100) : 0;
$pendingPercent = $weeklyTotalDue > 0 ? 100 - $collectionPercent : 0;
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

    <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 -mt-2 mb-8 border-b border-slate-200 bg-white px-4 py-3 lg:-mx-8 lg:px-8 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900 lg:text-2xl"><?php echo trans('dashboard'); ?></h1>
                <p class="mt-0.5 text-xs text-slate-500">
                    <?php echo trans('dashboard_welcome'); ?>
                    <span class="font-medium text-slate-700"><?php echo trans('week') . ' ' . $currentWeek . ', ' . $currentYear; ?></span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 min-w-[220px]">
                    <i class="fas fa-search text-slate-400 text-xs"></i>
                    <input type="text" placeholder="Search..." class="ml-2 w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-0" />
                </div>
                <button class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700">
                    <?php echo trans('week'); ?> <?php echo $currentWeek; ?>
                </button>
                <div class="hidden sm:flex items-center gap-3">
                    <button class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                        <i class="fas fa-bell text-sm"></i>
                    </button>
                    <div class="flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 border border-slate-200">
                        <div class="h-7 w-7 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 text-white flex items-center justify-center text-xs font-semibold">
                            <?php echo strtoupper(substr(trans('app_name'), 0, 1)); ?>
                        </div>
                        <span class="hidden text-xs font-medium text-slate-700 md:inline-block"><?php echo trans('app_name'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-1 gap-5 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400"><?php echo trans('active_members'); ?></p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900"><?php echo $totalActiveMembers; ?></p>
                        <p class="mt-1 text-xs text-emerald-500">
                            <i class="fas fa-arrow-up text-[0.65rem]"></i>
                            <span class="ml-1">+0% <?php echo trans('this_week_collection'); ?></span>
                        </p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400"><?php echo trans('this_week_collection'); ?></p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-600"><?php echo formatCurrency($weeklyCollection); ?></p>
                        <p class="mt-1 text-xs text-emerald-500">
                            <i class="fas fa-arrow-up text-[0.65rem]"></i>
                            <span class="ml-1">+0% <?php echo trans('week'); ?></span>
                        </p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400"><?php echo trans('pending_this_week'); ?></p>
                        <p class="mt-2 text-3xl font-semibold text-amber-500"><?php echo formatCurrency($weeklyPendingAmount); ?></p>
                        <p class="mt-1 text-xs text-amber-500">
                            <i class="fas fa-arrow-down text-[0.65rem]"></i>
                            <span class="ml-1"><?php echo trans('pending_this_week'); ?></span>
                        </p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400"><?php echo trans('unpaid_members'); ?></p>
                        <p class="mt-2 text-3xl font-semibold text-rose-500"><?php echo $pendingMembersCount; ?></p>
                        <p class="mt-1 text-xs text-rose-500">
                            <i class="fas fa-user-times text-[0.65rem]"></i>
                            <span class="ml-1"><?php echo trans('pending_members_list'); ?></span>
                        </p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-500">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle row: statistics & recent payments -->
        <div class="grid grid-cols-1 gap-6 mb-8 xl:grid-cols-3">
            <div class="xl:col-span-2 rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900"><?php echo trans('statistics'); ?></h2>
                        <p class="mt-1 text-xs text-slate-500"><?php echo trans('this_week_collection'); ?> &amp; <?php echo trans('pending_this_week'); ?></p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-[0.7rem] font-medium text-slate-600 border border-slate-100">
                        <i class="fas fa-calendar-week text-[0.65rem]"></i>
                        <span><?php echo trans('week'); ?> <?php echo $currentWeek; ?>, <?php echo $currentYear; ?></span>
                    </span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500 mb-2"><?php echo trans('this_week_collection'); ?></p>
                        <p class="text-2xl font-semibold text-emerald-600 mb-1"><?php echo formatCurrency($weeklyCollection); ?></p>
                        <p class="text-[0.7rem] text-slate-500 mb-4"><?php echo trans('active_members'); ?>: <?php echo $totalActiveMembers; ?></p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-[0.7rem] text-slate-500">
                                <span><?php echo trans('total_collected') ?? 'Collected'; ?></span>
                                <span><?php echo $collectionPercent; ?>%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: <?php echo $collectionPercent; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-white p-4">
                        <p class="text-xs font-medium text-slate-500 mb-2"><?php echo trans('pending_this_week'); ?></p>
                        <p class="text-2xl font-semibold text-amber-500 mb-1"><?php echo formatCurrency($weeklyPendingAmount); ?></p>
                        <p class="text-[0.7rem] text-slate-500 mb-4"><?php echo trans('pending_members_list'); ?></p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-[0.7rem] text-slate-500">
                                <span><?php echo trans('total_pending') ?? 'Pending'; ?></span>
                                <span><?php echo $pendingPercent; ?>%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-2 rounded-full bg-amber-400" style="width: <?php echo $pendingPercent; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-900"><?php echo trans('recent_payments'); ?></h2>
                    <span class="rounded-full bg-slate-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-slate-500 border border-slate-100"><?php echo trans('this_week_collection'); ?></span>
                </div>
                <div class="space-y-4 max-h-72 overflow-y-auto custom-scrollbar">
                    <?php if (count($recentPayments) > 0): ?>
                        <?php foreach ($recentPayments as $payment): ?>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($payment['member_name']); ?></p>
                                    <p class="mt-0.5 text-xs text-slate-500"><?php echo formatDate($payment['payment_date']); ?></p>
                                </div>
                                <p class="text-sm font-semibold text-emerald-600">+<?php echo formatCurrency($payment['amount_paid']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="py-10 text-center text-sm text-slate-500"><?php echo trans('no_recent_payments'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bottom row: pending members -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-900"><?php echo trans('pending_members_list'); ?></h2>
                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-rose-500 border border-rose-100 flex items-center gap-1">
                    <i class="fas fa-user-times text-[0.6rem]"></i>
                    <?php echo $pendingMembersCount; ?> <?php echo trans('unpaid_members'); ?>
                </span>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden">
                <?php if (count($pendingMembersList) > 0): ?>
                    <?php foreach ($pendingMembersList as $member): ?>
                    <?php
                        $memberName = htmlspecialchars($member['name']);
                        $memberPhone = htmlspecialchars($member['phone']);
                        $balance = (float)$member['amount'] - (float)$member['amount_paid'];
                        $balanceText = formatCurrency($balance);
                        $encodedPhone = urlencode($member['phone']);
                        $reminderText = rawurlencode("Hi $memberName, your weekly payment balance is $balanceText. Please pay as soon as possible. Thank you.");
                    ?>
                    <div class="p-4 border-b border-slate-100">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo $memberName; ?></p>
                                <p class="mt-0.5 text-xs text-slate-500"><?php echo $memberPhone; ?></p>
                            </div>
                            <p class="text-sm font-semibold text-rose-500">Balance: <?php echo $balanceText; ?></p>
                        </div>
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
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-10 text-center">
                        <div class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <p class="text-sm text-slate-500"><?php echo trans('all_payments_collected'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($pendingMembersList) > 0): ?>
                            <?php foreach ($pendingMembersList as $member): ?>
                            <?php
                                $memberName = htmlspecialchars($member['name']);
                                $memberPhone = htmlspecialchars($member['phone']);
                                $balance = (float)$member['amount'] - (float)$member['amount_paid'];
                                $balanceText = formatCurrency($balance);
                                $encodedPhone = urlencode($member['phone']);
                                $reminderText = rawurlencode("Hi $memberName, your weekly payment balance is $balanceText. Please pay as soon as possible. Thank you.");
                            ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="py-3 pr-4">
                                    <p class="text-sm font-medium text-slate-900"><?php echo $memberName; ?></p>
                                    <p class="mt-0.5 text-xs text-slate-500"><?php echo $memberPhone; ?></p>
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
                                </td>
                                <td class="py-3 pl-4 text-right align-middle">
                                    <p class="text-sm font-semibold text-rose-500">Balance: <?php echo $balanceText; ?></p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="py-10 text-center">
                                    <div class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <p class="text-sm text-slate-500"><?php echo trans('all_payments_collected'); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>