    </main>
    <?= $flash_user; ?>

    <!-- Back to top button -->
    <a class="btn-scroll-top" href="#top" data-scroll aria-label="Scroll back to top">
        <svg viewBox="0 0 40 40" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="19" fill="none" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></circle>
        </svg>
        <i class="ai-arrow-up"></i>
    </a>

    <script src="<?= PROOT; ?>assets/js/jquery-3.7.1.min.js"></script>
    <script src="<?= PROOT; ?>assets/js/swiper-bundle.min.js"></script>
    <!-- Bootstrap + Theme scripts -->
    <script src="<?= PROOT; ?>assets/js/theme.min.js"></script>

    <!-- Customizer -->
    <script src="<?= PROOT; ?>assets/js/customizer.min.js"></script>

    <script>
		// Fade out messages 
		$("#temporary").fadeOut(10000);

        // Get the current URL
        var currentUrl = window.location.href;

        // Get all the links in the sidebar
        var sidebarLinks = document.querySelectorAll('#sidebarAccount .nav-link');

        // Loop through the links and add the 'active' class to the one that matches the current URL
        sidebarLinks.forEach(function(link) {
            if (link.href === currentUrl) {
                link.classList.add('active');
            } 
        })
    </script>
</body>
</html>
