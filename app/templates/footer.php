<div id="toast" class="fixed top-6 right-6 z-[100] w-full max-w-xs transform transition-all duration-300 translate-x-full opacity-0" role="alert">
    <div class="flex items-center w-full p-4 text-slate-500 bg-white rounded-xl shadow-2xl">
        <div id="toast-icon" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"></div>
        <div id="toast-message" class="ml-3 text-sm font-normal"></div>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-slate-400 hover:text-slate-900 rounded-lg p-1.5" onclick="hideToast()">
            <span class="sr-only">Close</span>
            <i class="fas fa-times"></i>
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