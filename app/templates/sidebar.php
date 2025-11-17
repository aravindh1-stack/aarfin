<?php
$currentPage = $currentPage ?? ''; 
?>
<aside
    id="sidebar"
    class="bg-white h-screen w-64 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out shadow-lg z-50 border-r border-slate-200 flex flex-col"
>
    <!-- Brand -->
    <div class="flex items-center gap-4 px-6 py-6 border-b border-slate-200 bg-gradient-to-br from-slate-50 to-white">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-blue-500 text-white shadow-lg text-lg font-bold">
            <?php echo strtoupper(substr(trans('app_name'), 0, 1)); ?>
        </div>
        <div class="flex flex-col min-w-0">
            <a href="<?php echo URL_ROOT; ?>/dashboard" class="text-sm font-semibold tracking-tight text-slate-900 truncate">
                <?php echo trans('app_name'); ?>
            </a>
            <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-slate-500">Management</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 pt-4 pb-16 overflow-y-auto custom-scrollbar">
        <?php
        $items = [
            ['id' => 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => trans('dashboard')],
            ['id' => 'members',   'icon' => 'fa-users',          'label' => trans('members')],
            ['id' => 'payments',  'icon' => 'fa-money-bill-wave','label' => trans('payments')],
            ['id' => 'expenses',  'icon' => 'fa-receipt',        'label' => trans('expenses')],
            ['id' => 'reports',   'icon' => 'fa-chart-pie',      'label' => trans('reports')],
            ['id' => 'settings',  'icon' => 'fa-cog',            'label' => trans('settings')],
        ];

        foreach ($items as $item):
            $active = $currentPage === $item['id'];
        ?>
            <a
                href="<?php echo URL_ROOT . '/' . $item['id']; ?>"
                class="
                    group mb-2 flex items-center rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200
                    <?php echo $active
                        ? 'bg-blue-50 text-blue-900 border border-blue-200 shadow-sm'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent hover:border-slate-200'; ?>
                "
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-sm transition-all duration-200
                        <?php echo $active ? 'bg-blue-100 text-blue-600 shadow-sm' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700'; ?>"
                >
                    <i class="fas <?php echo $item['icon']; ?>"></i>
                </span>
                <span class="ml-3 truncate flex-1">
                    <?php echo $item['label']; ?>
                </span>
                <?php if ($active): ?>
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-blue-500 shadow-sm"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Language switcher -->
    <div class="px-4 pb-4 pt-4 border-t border-slate-200 bg-white">
        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-[0.75rem] font-semibold text-slate-600 border border-slate-200">
            <?php
            $current_url = strtok($_SERVER['REQUEST_URI'], '?');
            $en_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=en';
            $ta_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=ta';
            ?>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-900 text-[0.65rem] text-white">
                    <i class="fas fa-globe"></i>
                </span>
                <span>Language</span>
            </div>
            <div class="flex items-center gap-1.5">
                <a
                    href="<?php echo $en_link; ?>"
                    class="px-2.5 py-1 rounded-md transition-all duration-200 <?php echo ($_SESSION['lang'] == 'en') ? 'bg-white text-blue-600 shadow-sm font-semibold border border-blue-200' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>"
                >
                    EN
                </a>
                <a
                    href="<?php echo $ta_link; ?>"
                    class="px-2.5 py-1 rounded-md transition-all duration-200 <?php echo ($_SESSION['lang'] == 'ta') ? 'bg-white text-blue-600 shadow-sm font-semibold border border-blue-200' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>"
                >
                    TM
                </a>
            </div>
        </div>
    </div>
    
</aside>