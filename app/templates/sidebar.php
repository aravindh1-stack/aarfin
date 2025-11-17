<?php
$currentPage = $currentPage ?? ''; 
?>
<aside
    id="sidebar"
    class="bg-white/95 backdrop-blur h-screen w-64 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-200 ease-in-out shadow-[0_10px_40px_rgba(15,23,42,0.08)] z-50 border-r border-slate-100 flex flex-col"
>
    <!-- Brand -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
        <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-500 text-white shadow-sm text-lg font-semibold">
            <?php echo strtoupper(substr(trans('app_name'), 0, 1)); ?>
        </div>
        <div class="flex flex-col">
            <a href="<?php echo URL_ROOT; ?>/dashboard" class="text-sm font-semibold tracking-tight text-slate-900">
                <?php echo trans('app_name'); ?>
            </a>
            <span class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-slate-400">Dashboard</span>
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
                    group mb-1 flex items-center rounded-xl px-3.5 py-2.5 text-sm transition
                    <?php echo $active
                        ? 'bg-blue-50 text-slate-900 shadow-sm border border-blue-100'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-100'; ?>
                    border border-transparent
                "
            >
                <span
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-xs
                        <?php echo $active ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700'; ?>"
                >
                    <i class="fas <?php echo $item['icon']; ?>"></i>
                </span>
                <span class="ml-3 truncate font-medium text-[0.86rem]">
                    <?php echo $item['label']; ?>
                </span>
                <?php if ($active): ?>
                    <span class="ml-auto h-6 w-1 rounded-full bg-blue-400"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Language switcher -->
    <div class="px-4 pb-4 pt-3 border-t border-slate-100 bg-white/90">
        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-[0.78rem] font-medium text-slate-500">
            <?php 
            // Get current URL without query string
            $current_url = strtok($_SERVER['REQUEST_URI'], '?');
            // Create language links with current URL
            $en_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=en';
            $ta_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=ta';
            ?>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-[0.7rem] text-white">
                    <i class="fas fa-globe"></i>
                </span>
                <span class="text-[0.75rem] text-slate-600">Language</span>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="<?php echo $en_link; ?>"
                    class="px-2 py-0.5 rounded-full <?php echo ($_SESSION['lang'] == 'en') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>"
                >
                    English
                </a>
                <a
                    href="<?php echo $ta_link; ?>"
                    class="px-2 py-0.5 rounded-full <?php echo ($_SESSION['lang'] == 'ta') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>"
                >
                    தமிழ்
                </a>
            </div>
        </div>
    </div>
    
</aside>