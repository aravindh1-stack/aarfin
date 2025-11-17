<?php
$currentPage = 'members';
$pageTitle = trans('app_name') . ' - ' . trans('members');
require_once APP_ROOT . '/templates/header.php';

// --- HANDLE POST REQUESTS FOR MEMBERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_member' || $action === 'edit_member') {
            $data = [
                'name' => trim($_POST['name'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''),
                'contribution_amount' => (float)($_POST['contribution_amount'] ?? 0),
                'group_id' => (int)($_POST['group_id'] ?? 1), 'join_date' => $_POST['join_date'] ?? date('Y-m-d')
            ];
            if (empty($data['name']) || empty($data['phone']) || $data['contribution_amount'] <= 0) {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'All fields are required.'];
            } else {
                 if ($action === 'add_member') {
                    $sql = "INSERT INTO members (name, phone, contribution_amount, group_id, join_date, status) VALUES (?, ?, ?, ?, ?, 'Active')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$data['name'], $data['phone'], $data['contribution_amount'], $data['group_id'], $data['join_date']]);
                    $lastId = $pdo->lastInsertId();
                    $memberUid = 'PC-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
                    $pdo->prepare("UPDATE members SET member_uid = ? WHERE id = ?")->execute([$memberUid, $lastId]);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Member added successfully!'];
                } else {
                    $member_id = (int)($_POST['member_id'] ?? 0);
                    $status = $_POST['status'] ?? 'Active';
                    $sql = "UPDATE members SET name = ?, phone = ?, contribution_amount = ?, group_id = ?, join_date = ?, status = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$data['name'], $data['phone'], $data['contribution_amount'], $data['group_id'], $data['join_date'], $status, $member_id]);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Member updated successfully!'];
                }
            }
        } elseif ($action === 'delete_member') {
            $member_id = (int)($_POST['member_id'] ?? 0);
            if ($member_id > 0) {
                $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$member_id]);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Member deleted successfully.'];
            }
        }
    } catch (PDOException $e) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
    }
    header('Location: ' . URL_ROOT . '/members');
    exit();
}

// --- FETCH DATA FOR DISPLAY ---
$members = $pdo->query("SELECT m.*, g.group_name FROM members m JOIN `groups` g ON m.group_id = g.id ORDER BY m.name ASC")->fetchAll();
$groups = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC")->fetchAll();

// Member summary stats
$totalMembers = count($members);
$activeMembers = 0;
$inactiveMembers = 0;
foreach ($members as $m) {
    if (($m['status'] ?? '') === 'Active') {
        $activeMembers++;
    } elseif (($m['status'] ?? '') === 'Inactive') {
        $inactiveMembers++;
    }
}

?>

<div class="relative min-h-screen md:flex">
    
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>

    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 md:ml-64 px-4 pt-0 pb-20 lg:px-8 lg:pt-0 lg:pb-8 bg-slate-50">

        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 mb-4 border-b border-slate-200 bg-white px-4 py-3 lg:py-4 lg:-mx-8 lg:px-8 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between shadow-sm">
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900"><?php echo trans('member_management'); ?></h1>
                <p class="mt-1 text-xs md:text-sm text-slate-600"><?php echo trans('member_management_desc'); ?></p>
            </div>
            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 min-w-[220px]">
                    <i class="fas fa-search text-slate-400 text-xs"></i>
                    <input id="memberSearchInput" type="text" placeholder="Search members..." class="ml-2 w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-0" />
                </div>
                <button onclick="openAddMemberModal()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2.5 text-sm font-medium text-white shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-user-plus text-sm"></i>
                    <span><?php echo trans('add_new_member'); ?></span>
                </button>
            </div>
        </div>

        <!-- Members list with attached summary header -->
        <div class="rounded-xl bg-white shadow-lg border border-slate-200 overflow-hidden">
            <!-- Summary header: three KPI cards like payments page -->
            <div class="px-4 pt-4 pb-5 border-b border-slate-100 bg-slate-50/40">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500"><?php echo trans('members') ?? 'Members'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-slate-900"><?php echo $totalMembers; ?></p>
                    </div>
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600"><?php echo trans('active_members') ?? 'Active'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600"><?php echo $activeMembers; ?></p>
                    </div>
                    <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm border border-amber-200">
                        <p class="text-xs font-semibold uppercase tracking-widest text-amber-600"><?php echo trans('inactive') ?? 'Inactive'; ?></p>
                        <p class="mt-2 text-2xl font-bold text-amber-600"><?php echo $inactiveMembers; ?></p>
                    </div>
                </div>
            </div>

            <!-- Mobile cards (compact: name + UID + edit, tap to open details) -->
            <div class="md:hidden p-3 space-y-3 bg-slate-50/60" id="memberMobileList">
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $member): ?>
                    <div
                        class="rounded-2xl bg-white p-3 shadow-sm border border-slate-100 js-member-card"
                        data-name="<?php echo htmlspecialchars(strtolower($member['name'])); ?>"
                        data-uid="<?php echo htmlspecialchars(strtolower($member['member_uid'])); ?>"
                        data-phone="<?php echo htmlspecialchars(strtolower($member['phone'])); ?>"
                    >
                        <button
                            type="button"
                            onclick='openEditMemberModal(<?php echo json_encode($member); ?>)'
                            class="w-full text-left flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-sm font-semibold">
                                    <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900 truncate"><?php echo htmlspecialchars($member['name']); ?></p>
                                    <p class="text-[0.7rem] text-slate-400 font-mono mt-0.5">#<?php echo htmlspecialchars($member['member_uid']); ?></p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[0.7rem] font-medium text-slate-700">
                                <i class="fas fa-pen text-[0.6rem]"></i>
                                <span><?php echo trans('edit') ?? 'Edit'; ?></span>
                            </span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-sm text-slate-500 bg-white rounded-2xl border border-dashed border-slate-200"><?php echo trans('no_members_found'); ?></div>
                <?php endif; ?>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm align-middle">
                    <thead class="bg-slate-50/80 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-500 text-xs">
                                        <i class="fas fa-user"></i>
                                    </span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 text-xs">
                                        <i class="fas fa-indian-rupee-sign"></i>
                                    </span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-white text-[0.6rem]">
                                        <i class="fas fa-signal"></i>
                                    </span>
                                </div>
                            </th>
                            <th class="relative px-6 py-3 text-right text-[0.7rem] font-semibold tracking-wide text-slate-500 uppercase">
                                <div class="inline-flex items-center justify-end gap-2 w-full">
                                    <span class="leading-tight"><?php echo trans('actions') ?? 'Actions'; ?></span>
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-xs">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100" id="memberDesktopTableBody">
                         <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                            <tr
                                class="hover:bg-slate-50 js-member-row"
                                data-name="<?php echo htmlspecialchars(strtolower($member['name'])); ?>"
                                data-uid="<?php echo htmlspecialchars(strtolower($member['member_uid'])); ?>"
                                data-phone="<?php echo htmlspecialchars(strtolower($member['phone'])); ?>"
                            >
                                <!-- Name / UID / phone -->
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-sm font-semibold">
                                            <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($member['name']); ?></p>
                                            <p class="text-[0.7rem] text-slate-400 font-mono mt-0.5">#<?php echo htmlspecialchars($member['member_uid']); ?></p>
                                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                                <i class="fas fa-phone-alt fa-xs"></i>
                                                <span class="truncate"><?php echo htmlspecialchars($member['phone']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contribution / group -->
                                <td class="px-6 py-4 align-top">
                                    <p class="text-sm font-semibold text-slate-900 flex items-center gap-1">
                                        <span class="inline-flex h-6 min-w-[3rem] items-center justify-center rounded-full bg-emerald-50 px-2 text-xs font-semibold text-emerald-700">
                                            <?php echo formatCurrency($member['contribution_amount']); ?>
                                        </span>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <i class="fas fa-layer-group fa-xs"></i>
                                        <span><?php echo htmlspecialchars($member['group_name']); ?></span>
                                    </p>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 align-top">
                                    <?php
                                        $isActive = ($member['status'] === 'Active');
                                        $statusClasses = $isActive
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                                            : 'bg-rose-50 text-rose-700 border border-rose-100';
                                        $statusIcon = $isActive ? 'fa-check-circle' : 'fa-exclamation-circle';
                                    ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusClasses; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> text-[0.65rem]"></i>
                                        <span><?php echo $member['status']; ?></span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right align-top">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            onclick='openEditMemberModal(<?php echo json_encode($member); ?>)'
                                            class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50 transition-colors duration-150"
                                        >
                                            <i class="fas fa-pen text-[0.65rem]"></i>
                                            <span><?php echo trans('edit') ?? 'Edit'; ?></span>
                                        </button>
                                        <button
                                            onclick='openDeleteMemberModal(<?php echo $member["id"]; ?>, "<?php echo htmlspecialchars(addslashes($member["name"])); ?>")'
                                            class="inline-flex items-center justify-center h-8 w-8 rounded-full border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:border-rose-200 text-xs transition-colors duration-150"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr><td colspan="4" class="text-center py-10 text-sm text-slate-500"><?php echo trans('no_members_found'); ?> <a href="#" onclick="openAddMemberModal()" class="text-blue-600 hover:text-blue-700 font-medium"><?php echo trans('add_new_member'); ?></a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="memberModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl border border-slate-200">
        <form id="memberForm" action="<?php echo URL_ROOT; ?>/members" method="POST">
            <input type="hidden" name="action" id="form_action">
            <input type="hidden" name="member_id" id="form_member_id">
            <div class="p-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-slate-900 mb-5">Add New Member</h3>
                <div class="space-y-4">
                    <div><label class="block text-xs font-medium text-slate-600">Full Name</label><input type="text" id="form_name" name="name" required class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"></div>
                    <div><label class="block text-xs font-medium text-slate-600">Phone Number</label><input type="tel" id="form_phone" name="phone" required class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"></div>
                    <div><label class="block text-xs font-medium text-slate-600">Weekly Contribution (₹)</label><input type="number" step="1" min="0" id="form_contribution_amount" name="contribution_amount" required class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"></div>
                    <div><label class="block text-xs font-medium text-slate-600">Fund Group</label><select id="form_group_id" name="group_id" required class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900">
                         <?php foreach($groups as $group): ?><option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div><label class="block text-xs font-medium text-slate-600">Join Date</label><input type="date" id="form_join_date" name="join_date" required class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"></div>
                    <div id="status-field" class="hidden"><label class="block text-xs font-medium text-slate-600">Status</label><select id="form_status" name="status" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between gap-3 bg-slate-50 rounded-b-xl border-t border-slate-200">
                <button
                    type="button"
                    id="editDeleteButton"
                    class="hidden px-4 py-2.5 rounded-lg border border-rose-200 bg-rose-50 text-xs font-medium text-rose-600 hover:bg-rose-100 hover:border-rose-300 transition-colors duration-200"
                >
                    <i class="fas fa-trash-alt text-[0.65rem] mr-1"></i>
                    <?php echo trans('delete') ?? 'Delete'; ?>
                </button>

                <div class="ml-auto flex items-center gap-3">
                    <button type="button" onclick="closeModal('memberModal')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors duration-200">Cancel</button>
                    <button type="submit" id="submitButton" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-sm font-medium text-white shadow-md hover:shadow-lg transition-all duration-200"></button>
                </div>
            </div>
        </form>

    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-2xl border border-slate-200 p-6 text-center">
        <h3 class="text-base font-semibold text-slate-900">Delete Member</h3>
        <p id="deleteMessage" class="mt-2 text-sm text-slate-500">Are you sure?</p>
        <form action="<?php echo URL_ROOT; ?>/members" method="POST" class="mt-5">
            <input type="hidden" name="action" value="delete_member">
            <input type="hidden" name="member_id" id="delete_member_id">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" onclick="closeModal('deleteModal')" class="w-full py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="w-full py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>