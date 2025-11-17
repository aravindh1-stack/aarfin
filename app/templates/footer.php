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