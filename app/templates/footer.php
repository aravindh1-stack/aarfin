<?php
$currentPage = $currentPage ?? '';
$bottomNavItems = [
    ['id' => 'dashboard', 'icon' => 'fa-house',      'label' => trans('dashboard'), 'href' => URL_ROOT . '/dashboard'],
    ['id' => 'members',   'icon' => 'fa-users',      'label' => trans('members'),   'href' => URL_ROOT . '/members'],
    ['id' => 'payments',  'icon' => 'fa-money-bill', 'label' => trans('payments'),  'href' => URL_ROOT . '/payments'],
    ['id' => 'expenses',  'icon' => 'fa-receipt',    'label' => trans('expenses'),  'href' => URL_ROOT . '/expenses'],
    ['id' => 'reports',   'icon' => 'fa-chart-pie',  'label' => trans('reports'),   'href' => URL_ROOT . '/reports'],
];
?>

<!-- Mobile bottom navigation -->
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 shadow-[0_-4px_20px_rgba(15,23,42,0.08)] z-40">
    <div class="flex justify-around items-stretch h-14">
        <?php foreach ($bottomNavItems as $item):
            $active = ($currentPage === $item['id']);
        ?>
            <a
                href="<?php echo $item['href']; ?>"
                class="flex-1 flex flex-col items-center justify-center text-[0.7rem] font-medium transition-colors duration-150
                    <?php echo $active ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900'; ?>"
            >
                <span class="flex h-7 w-7 items-center justify-center rounded-full mb-0.5 text-xs
                    <?php echo $active ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-500'; ?>">
                    <i class="fas <?php echo $item['icon']; ?>"></i>
                </span>
                <span class="truncate"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<div id="toast" class="fixed bottom-6 right-6 z-[100] w-full max-w-sm transform transition-all duration-300 translate-x-[120%] opacity-0" role="alert">
    <div class="flex items-start gap-4 w-full p-4 text-slate-700 bg-white rounded-lg shadow-lg border border-slate-200">
        <div id="toast-icon" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-full"></div>
        <div id="toast-message" class="flex-1 text-sm font-medium"></div>
        <button type="button" class="ml-auto flex-shrink-0 text-slate-400 hover:text-slate-600 transition-colors duration-200 p-1" onclick="hideToast()">
            <span class="sr-only">Close</span>
            <i class="fas fa-times text-base"></i>
        </button>
    </div>
</div>

<script src="<?php echo URL_ROOT; ?>/assets/js/main.js"></script>

<?php 
// Display toast message if it's set in the session
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('" . addslashes($toast['message']) . "', '" . $toast['type'] . "');
        });
    </script>";
    unset($_SESSION['toast']);
}
?>
</body>
</html>