<?php
$currentPage = 'expenses';
$pageTitle = trans('app_name') . ' - ' . trans('expenses');
require_once APP_ROOT . '/templates/header.php';

// --- (PHP logic for POST handling and data fetching is unchanged) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...
}
$expenses = $pdo->query("SELECT e.*, g.group_name FROM expenses e JOIN `groups` g ON e.group_id = g.id ORDER BY e.expense_date DESC")->fetchAll();
$groups = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC")->fetchAll();
?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 p-4 sm:p-6 bg-slate-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?php echo trans('expense_management'); ?></h1>
                <p class="mt-1 text-sm text-slate-600"><?php echo trans('expense_management_desc'); ?></p>
            </div>
            <button onclick="openAddExpenseModal()" class="mt-4 md:mt-0 w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white">
                <i class="fas fa-plus"></i> <span><?php echo trans('add_expense'); ?></span>
            </button>
        </div>

        <div class="bg-white rounded-lg border overflow-hidden">
            <div class="md:hidden">
                <?php foreach ($expenses as $expense): ?>
                <div class="p-4 border-b">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($expense['title']); ?></p>
                            <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($expense['description']); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <button onclick='openEditExpenseModal(<?php echo json_encode($expense); ?>)' class="text-primary p-2"><i class="fas fa-edit"></i></button>
                            <button onclick='openDeleteExpenseModal(<?php echo $expense["id"]; ?>, "<?php echo htmlspecialchars(addslashes($expense["title"])); ?>")' class="text-red-600 p-2"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <p class="text-sm font-medium text-red-600"><?php echo formatCurrency($expense['amount']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo formatDate($expense['expense_date']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($expenses)): ?>
                <div class="p-4 text-center py-10 text-slate-500"><?php echo trans('no_expenses_found'); ?> <a href="#" onclick="openAddExpenseModal()" class="text-primary"><?php echo trans('add_one'); ?></a> <?php echo trans('to_get_started'); ?>.</p>
                <?php endif; ?>
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('title'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('description'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('amount'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('date'); ?></th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars($expense['title']); ?></div>
                                <div class="text-sm text-slate-500"><?php echo htmlspecialchars($expense['description']); ?></div>
                            </td>
                            <td class="px-6 py-4 font-medium text-red-600"><?php echo formatCurrency($expense['amount']); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-700"><?php echo formatDate($expense['expense_date']); ?></td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='openEditExpenseModal(<?php echo json_encode($expense); ?>)' class="text-primary p-2 hover:bg-blue-50 rounded-full"><i class="fas fa-edit"></i></button>
                                <button onclick='openDeleteExpenseModal(<?php echo $expense["id"]; ?>, "<?php echo htmlspecialchars(addslashes($expense["title"])); ?>")' class="text-red-600 p-2 hover:bg-red-50 rounded-full"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>