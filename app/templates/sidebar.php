<?php
$currentPage = $currentPage ?? ''; 
?>
<aside id="sidebar" class="bg-white h-screen w-64 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out shadow-md z-50">
    <div class="p-6 text-center border-b">
        <a href="<?php echo URL_ROOT; ?>/dashboard" class="text-2xl font-bold text-primary"><?php echo trans('app_name'); ?></a>
    </div>
    <nav class="mt-6">
        <a href="<?php echo URL_ROOT; ?>/dashboard" class="flex items-center px-6 py-3 text-slate-700 hover:bg-slate-100 <?php echo ($currentPage == 'dashboard') ? 'bg-slate-100 text-primary font-semibold' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6"></i><span class="ml-3"><?php echo trans('dashboard'); ?></span>
        </a>
        <a href="<?php echo URL_ROOT; ?>/members" class="flex items-center px-6 py-3 text-slate-700 hover:bg-slate-100 <?php echo ($currentPage == 'members') ? 'bg-slate-100 text-primary font-semibold' : ''; ?>">
            <i class="fas fa-users w-6"></i><span class="ml-3"><?php echo trans('members'); ?></span>
        </a>
        <a href="<?php echo URL_ROOT; ?>/payments" class="flex items-center px-6 py-3 text-slate-700 hover:bg-slate-100 <?php echo ($currentPage == 'payments') ? 'bg-slate-100 text-primary font-semibold' : ''; ?>">
            <i class="fas fa-money-bill-wave w-6"></i><span class="ml-3"><?php echo trans('payments'); ?></span>
        </a>
        <a href="<?php echo URL_ROOT; ?>/expenses" class="flex items-center px-6 py-3 text-slate-700 hover:bg-slate-100 <?php echo ($currentPage == 'expenses') ? 'bg-slate-100 text-primary font-semibold' : ''; ?>">
            <i class="fas fa-receipt w-6"></i><span class="ml-3"><?php echo trans('expenses'); ?></span>
        </a>
        <a href="<?php echo URL_ROOT; ?>/reports" class="flex items-center px-6 py-3 text-slate-700 hover:bg-slate-100 <?php echo ($currentPage == 'reports') ? 'bg-slate-100 text-primary font-semibold' : ''; ?>">
            <i class="fas fa-chart-pie w-6"></i><span class="ml-3"><?php echo trans('reports'); ?></span>
        </a>
    </nav>

    <div class="absolute bottom-0 w-full p-4">
        <div class="flex justify-around items-center text-sm">
            <?php 
            // Get current URL without query string
            $current_url = strtok($_SERVER['REQUEST_URI'], '?');
            // Create language links with current URL
            $en_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=en';
            $ta_link = $current_url . (strpos($current_url, '?') === false ? '?' : '&') . 'lang=ta';
            ?>
            <a href="<?php echo $en_link; ?>" class="<?php echo ($_SESSION['lang'] == 'en') ? 'font-bold text-primary' : 'text-slate-500'; ?>">English</a>
            <span class="text-slate-300">|</span>
            <a href="<?php echo $ta_link; ?>" class="<?php echo ($_SESSION['lang'] == 'ta') ? 'font-bold text-primary' : 'text-slate-500'; ?>">தமிழ்</a>
        </div>
    </div>
    
</aside>