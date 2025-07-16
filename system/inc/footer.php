    </main>

    <!-- Footer -->
    <footer class="footer bg-secondary py-5">
      <div class="container pt-1 pt-sm-2 pt-md-3 pt-lg-4 pt-xl-5">
        <div class="border-bottom text-center pb-4">
          <a class="navbar-brand d-inline-flex text-nav py-0 mb-3 mb-md-4" href="index.html">
            <span class="text-primary flex-shrink-0 me-2">
              <svg version="1.1" width="35" height="32" viewBox="0 0 36 33" xmlns="http://www.w3.org/2000/svg">
                <path fill="currentColor" d="M35.6,29c-1.1,3.4-5.4,4.4-7.9,1.9c-2.3-2.2-6.1-3.7-9.4-3.7c-3.1,0-7.5,1.8-10,4.1c-2.2,2-5.8,1.5-7.3-1.1c-1-1.8-1.2-4.1,0-6.2l0.6-1.1l0,0c0.6-0.7,4.4-5.2,12.5-5.7c0.5,1.8,2,3.1,3.9,3.1c2.2,0,4.1-1.9,4.1-4.2s-1.8-4.2-4.1-4.2c-2,0-3.6,1.4-4,3.3H7.7c-0.8,0-1.3-0.9-0.9-1.6l5.6-9.8c2.5-4.5,8.8-4.5,11.3,0L35.1,24C36,25.7,36.1,27.5,35.6,29z"></path>
              </svg>
            </span>
            <span>Care</span>
          </a>
          <ul class="nav justify-content-center py-1 pb-sm-2">
            <li class="nav-item">
              <a class="nav-link py-2 px-3 mx-1" href="#">Classes</a>
            </li>
            <li class="nav-item">
              <a class="nav-link py-2 px-3 mx-1" href="#">Trainers</a>
            </li>
            <li class="nav-item">
              <a class="nav-link py-2 px-3 mx-1" href="#">Schedule</a>
            </li>
            <li class="nav-item">
              <a class="nav-link py-2 px-3 mx-1" href="#">Pricing</a>
            </li>
            <li class="nav-item">
              <a class="nav-link py-2 px-3 mx-1" href="#">Contact</a>
            </li>
          </ul>
        </div>
        <div class="d-sm-flex align-items-center justify-content-between pt-4 mt-2 mt-sm-0">
          <div class="d-flex justify-content-center gap-3 order-sm-2 mb-4 mb-sm-0">
            <a class="btn btn-icon btn-sm btn-secondary btn-instagram rounded-circle" href="#" aria-label="Instagram">
              <i class="ai-instagram"></i>
            </a>
            <a class="btn btn-icon btn-sm btn-secondary btn-facebook rounded-circle" href="#" aria-label="Facebook">
              <i class="ai-facebook"></i>
            </a>
            <a class="btn btn-icon btn-sm btn-secondary btn-youtube rounded-circle" href="#" aria-label="YouTube">
              <i class="ai-youtube"></i>
            </a>
          </div>
          <p class="nav fs-sm order-sm-1 justify-content-center mb-0">
            <span class="text-body-secondary">&copy; All rights reserved. Made by</span>
            <a class="nav-link fw-normal p-0 ms-1" href="https://createx.studio/" target="_blank" rel="noopener">Createx Studio</a>
          </p>
        </div>
      </div>
    </footer>
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
