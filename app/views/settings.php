<?php
$currentPage = 'settings';
$pageTitle = trans('app_name') . ' - ' . trans('settings');
require_once APP_ROOT . '/templates/header.php';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $viewWeek = (int)($_POST['view_week'] ?? date('W'));
        if ($viewWeek < 1) $viewWeek = 1;
        if ($viewWeek > 52) $viewWeek = 52;

        $projectStartDate = $_POST['project_start_date'] ?? date('Y-m-d');
        if (!$projectStartDate) {
            $projectStartDate = date('Y-m-d');
        }

        $_SESSION['selected_week'] = $viewWeek;
        $_SESSION['selected_year'] = (int)date('Y');
        $_SESSION['project_start_date'] = $projectStartDate;
    } elseif ($action === 'go_current_week') {
        // Use posted project_start_date if provided, otherwise fall back
        $projectStartDate = $_POST['project_start_date'] ?? get_project_start_date($pdo);
        if (!$projectStartDate) {
            $projectStartDate = date('Y-m-d');
        }
        $_SESSION['project_start_date'] = $projectStartDate;

        // Compute current project week based on project_start_date -> today
        $start = new DateTime($projectStartDate);
        $today = new DateTime();
        $diffDays = max(0, (int)$start->diff($today)->days);
        $projectWeek = (int)floor($diffDays / 7) + 1;
        if ($projectWeek < 1) $projectWeek = 1;
        if ($projectWeek > 52) $projectWeek = 52;

        $_SESSION['selected_week'] = $projectWeek;
        $_SESSION['selected_year'] = (int)$today->format('Y');
    }

    // After any change, redirect to dashboard to apply and show the selected week
    header('Location: ' . URL_ROOT . '/dashboard');
    exit();
}

// Data for display
$projectStartDate = get_project_start_date($pdo);
[$currentWeek, $currentYear] = get_selected_week_and_year();
$projectStartDateFormatted = date('l, F d, Y', strtotime($projectStartDate));

?>

<div class="relative min-h-screen md:flex">
    <?php require_once APP_ROOT . '/templates/mobile_header.php'; ?>
    <?php require_once APP_ROOT . '/templates/sidebar.php'; ?>

    <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8 bg-slate-50">
        <!-- Top bar: connected to sidebar like a global header -->
        <div class="-mx-4 -mt-2 mb-8 border-b border-slate-200 bg-white px-4 py-3 lg:-mx-8 lg:px-8 flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 lg:text-2xl"><?php echo trans('settings'); ?></h1>
            <p class="text-xs text-slate-500"><?php echo trans('app_settings') ?? 'Manage how your app calculates and shows weekly data.'; ?></p>
        </div>

        <div class="space-y-6 max-w-2xl">
            <form action="<?php echo URL_ROOT; ?>/settings" method="POST" class="space-y-6">

                <!-- Select Viewing Week -->
                <section class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
                    <h2 class="text-sm font-semibold text-slate-900 mb-1"><?php echo trans('select_viewing_week') ?? 'Select Viewing Week'; ?></h2>
                    <p class="text-xs text-slate-500 mb-4"><?php echo trans('select_viewing_week_desc') ?? 'Manually select a week to view its data.'; ?></p>

                    <label class="block text-xs font-medium text-slate-600 mb-1"><?php echo trans('select_week') ?? 'Select Week'; ?></label>
                    <div class="relative mt-1">
                        <select name="view_week" class="block w-full rounded-xl border border-slate-300 bg-white pl-9 pr-8 py-2.5 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900 appearance-none">
                            <?php for ($w = 1; $w <= 52; $w++): ?>
                                <option value="<?php echo $w; ?>" <?php echo $w === $currentWeek ? 'selected' : ''; ?>><?php echo 'Week ' . $w; ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400 text-xs">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>
                </section>

                <!-- Project Settings -->
                <section class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
                <h2 class="text-sm font-semibold text-slate-900 mb-1"><?php echo trans('project_settings') ?? 'Project Settings'; ?></h2>
                <p class="text-xs text-slate-500 mb-4"><?php echo trans('project_settings_desc') ?? 'Configure how your project weeks are calculated.'; ?></p>

                <!-- Project start date -->
                <div class="mb-4 pb-4 border-b border-slate-100">
                    <p class="text-xs font-medium text-slate-600 mb-1"><?php echo trans('project_start_date') ?? 'Project Start Date'; ?></p>
                    <p class="text-[0.7rem] text-slate-400 mb-2"><?php echo trans('project_start_date_help') ?? 'This is the "Week 1" date. Set this once when starting a new project.'; ?></p>

                    <div class="flex items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <input type="date" name="project_start_date" value="<?php echo htmlspecialchars($projectStartDate); ?>" class="block w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2.5 text-sm text-slate-800 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>
                </div>

                <!-- Quick navigation -->
                <div class="flex flex-col gap-3">
                    <div>
                        <p class="text-xs font-medium text-slate-600 mb-1"><?php echo trans('quick_navigation') ?? 'Quick Navigation'; ?></p>
                        <p class="text-[0.7rem] text-slate-400 mb-3"><?php echo trans('quick_navigation_desc') ?? 'Automatically calculate and jump to the current week based on the project start date.'; ?></p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button type="submit" name="action" value="update_settings" class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800">
                            <i class="fas fa-save text-xs"></i>
                            <span><?php echo trans('save_settings') ?? 'Save Settings'; ?></span>
                        </button>

                        <button type="submit" name="action" value="go_current_week" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
                            <i class="fas fa-crosshairs text-xs"></i>
                            <span><?php echo trans('go_to_current_week') ?? 'Go to Current Week'; ?></span>
                        </button>
                    </div>
                </div>
            </section>
            </form>
        </div>
    </main>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
