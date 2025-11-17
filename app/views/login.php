<?php
$pageTitle = 'Aarfin - Login';
require_once APP_ROOT . '/templates/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
        <h1 class="text-xl font-bold text-center text-slate-900 mb-1">Aarfin Login</h1>
        <p class="text-xs text-center text-slate-500 mb-5">Sign in with your username and password.</p>

        <?php if (!empty($_SESSION['login_error'])): ?>
            <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo URL_ROOT; ?>/?page=login_action" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1" for="username">Username</label>
                <input type="text" id="username" name="username" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1" for="password">Password</label>
                <input type="password" id="password" name="password" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" />
            </div>

            <div class="flex items-center justify-between text-[0.75rem] text-slate-600">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="h-3.5 w-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                Login
            </button>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
