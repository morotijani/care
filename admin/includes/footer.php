        </main> <!-- End of admin-content -->

        <footer class="px-4 py-3 border-top bg-white text-muted small d-flex flex-wrap justify-content-between align-items-center">
            <div>
                &copy; <?= date('Y'); ?> <strong>Care That Feels Like Home</strong> &mdash; Admin Console
            </div>
            <div class="d-none d-sm-block">
                <span class="text-secondary"><i class="fas fa-shield-heart text-teal me-1" style="color: #0d9488;"></i>Bismarck, ND Operations</span>
            </div>
        </footer>
    </div> <!-- End of admin-main -->
</div> <!-- End of admin-wrapper -->

<?= $flash_user; ?>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    // Mobile sidebar toggle
    (function() {
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        function toggleSidebar() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (backdrop) backdrop.addEventListener('click', toggleSidebar);
    })();

    // Auto-fade flash toast
    setTimeout(function() {
        const toastEl = document.getElementById('temporary');
        if (toastEl) {
            $(toastEl).fadeOut(600);
        }
    }, 5000);
</script>
</body>
</html>
