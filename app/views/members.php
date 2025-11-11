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
?>

<div class="relative min-h-screen md:flex">
    
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>

    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 p-4 sm:p-6 bg-slate-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?php echo trans('member_management'); ?></h1>
                <p class="mt-1 text-sm text-slate-600"><?php echo trans('member_management_desc'); ?></p>
            </div>
            <button onclick="openAddMemberModal()" class="mt-4 md:mt-0 w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white">
                <i class="fas fa-user-plus"></i> <span><?php echo trans('add_new_member'); ?></span>
            </button>
        </div>

        <div class="bg-white rounded-lg border overflow-hidden">
            <div class="md:hidden">
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $member): ?>
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($member['name']); ?></p>
                                <p class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($member['member_uid']); ?></p>
                                <p class="text-sm text-slate-500 mt-1"><i class="fas fa-phone-alt fa-xs mr-1"></i><?php echo htmlspecialchars($member['phone']); ?></p>
                            </div>
                            <div class="flex-shrink-0">
                                <button onclick='openEditMemberModal(<?php echo json_encode($member); ?>)' class="text-primary p-2"><i class="fas fa-edit"></i></button>
                                <button onclick='openDeleteMemberModal(<?php echo $member["id"]; ?>, "<?php echo htmlspecialchars(addslashes($member["name"])); ?>")' class="text-red-600 p-2"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium"><?php echo formatCurrency($member['contribution_amount']); ?></p>
                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($member['group_name']); ?></p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $member['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo trans(strtolower($member['status'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-10 text-center text-slate-500"><?php echo trans('no_members_found'); ?></div>
                <?php endif; ?>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('name_uid'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('contribution'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?php echo trans('status'); ?></th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                         <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium"><?php echo htmlspecialchars($member['name']); ?></p>
                                    <p class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($member['member_uid']); ?></p>
                                    <p class="text-sm text-slate-500 mt-1"><i class="fas fa-phone-alt fa-xs mr-1"></i><?php echo htmlspecialchars($member['phone']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium"><?php echo formatCurrency($member['contribution_amount']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($member['group_name']); ?></p>
                                </td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $member['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo $member['status']; ?></span></td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick='openEditMemberModal(<?php echo json_encode($member); ?>)' class="text-primary p-2 hover:bg-blue-50 rounded-full"><i class="fas fa-edit"></i></button>
                                    <button onclick='openDeleteMemberModal(<?php echo $member["id"]; ?>, "<?php echo htmlspecialchars(addslashes($member["name"])); ?>")' class="text-red-600 p-2 hover:bg-red-50 rounded-full"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr><td colspan="4" class="text-center py-10 text-slate-500">No members found. <a href="#" onclick="openAddMemberModal()" class="text-primary">Add one</a> to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="memberModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl">
        <form id="memberForm" action="<?php echo URL_ROOT; ?>/members" method="POST">
            <input type="hidden" name="action" id="form_action">
            <input type="hidden" name="member_id" id="form_member_id">
            <div class="p-6">
                <h3 id="modalTitle" class="text-xl font-bold mb-6">Add New Member</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm">Full Name</label><input type="text" id="form_name" name="name" required class="mt-1 block w-full rounded-lg border-slate-300"></div>
                    <div><label class="block text-sm">Phone Number</label><input type="tel" id="form_phone" name="phone" required class="mt-1 block w-full rounded-lg border-slate-300"></div>
                    <div><label class="block text-sm">Weekly Contribution (₹)</label><input type="number" step="1" min="0" id="form_contribution_amount" name="contribution_amount" required class="mt-1 block w-full rounded-lg border-slate-300"></div>
                    <div><label class="block text-sm">Fund Group</label><select id="form_group_id" name="group_id" required class="mt-1 block w-full rounded-lg border-slate-300">
                        <?php foreach($groups as $group): ?><option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div><label class="block text-sm">Join Date</label><input type="date" id="form_join_date" name="join_date" required class="mt-1 block w-full rounded-lg border-slate-300"></div>
                    <div id="status-field" class="hidden"><label class="block text-sm">Status</label><select id="form_status" name="status" class="mt-1 block w-full rounded-lg border-slate-300"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3 bg-slate-50 rounded-b-2xl">
                <button type="button" onclick="closeModal('memberModal')" class="px-5 py-2.5 rounded-lg border">Cancel</button>
                <button type="submit" id="submitButton" class="px-5 py-2.5 rounded-lg bg-primary text-white"></button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
        <h3 class="text-lg font-medium">Delete Member</h3>
        <p id="deleteMessage" class="mt-2 text-sm text-slate-500">Are you sure?</p>
        <form action="<?php echo URL_ROOT; ?>/members" method="POST" class="mt-5">
            <input type="hidden" name="action" value="delete_member">
            <input type="hidden" name="member_id" id="delete_member_id">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" onclick="closeModal('deleteModal')" class="w-full py-2 rounded-lg border">Cancel</button>
                <button type="submit" class="w-full py-2 text-white bg-red-600 rounded-lg">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>