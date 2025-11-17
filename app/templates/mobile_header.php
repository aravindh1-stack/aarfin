<header class="w-full bg-white px-4 py-4 flex justify-between items-center md:hidden border-b border-slate-200 shadow-sm">
    <a href="<?php echo URL_ROOT; ?>/dashboard" class="inline-flex items-center gap-2.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg overflow-hidden bg-white shadow-md">
            <img
                src="<?php echo URL_ROOT; ?>/assets/image/logo/aarfin-logo.svg"
                alt="<?php echo htmlspecialchars(trans('app_name')); ?> Logo"
                class="h-9 w-9 object-contain"
            >
        </div>
        <span class="text-lg font-semibold text-slate-900"><?php echo trans('app_name'); ?></span>
    </a>
    <a href="<?php echo URL_ROOT; ?>/settings" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 transition-colors duration-200 focus:outline-none">
        <i class="fas fa-gear text-lg"></i>
    </a>
</header>