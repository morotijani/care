    </main>

    <!-- Footer -->
    <footer class="footer bg-secondary py-5">
        <div class="container pt-1 pt-sm-2 pt-md-3 pt-lg-4 pt-xl-5">
            <div class="border-bottom text-center pb-4">
                <a class="navbar-brand d-inline-flex text-nav py-0 mb-3 mb-md-4" href="index.html">
                    <span class="text-primary flex-shrink-0 me-2">
                        <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" class="img-fluid" width="35" height="35" alt="Care" style="border-radius: 5px;">
                    </span>
                    <span>Care That Feels Like Home.</span>
                </a>
                <ul class="nav justify-content-center py-1 pb-sm-2">
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 mx-1" href="<?= PROOT; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 mx-1" href="<?= PROOT; ?>services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 mx-1" href="<?= PROOT; ?>apply">Apply as a Caregiver</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 mx-1" href="#">Request for a Caregiver</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 mx-1" href="<?= PROOT; ?>contact">Contact</a>
                    </li>
                </ul>
            </div>
            <div class="d-sm-flex align-items-center justify-content-between pt-4 mt-2 mt-sm-0">
                <div class="d-flex justify-content-center gap-3 order-sm-2 mb-4 mb-sm-0">
                    <a class="btn btn-icon btn-sm btn-secondary btn-instagram rounded-circle" href="javascript:;" aria-label="Instagram">
                        <i class="ai-instagram"></i>
                    </a>
                    <a class="btn btn-icon btn-sm btn-secondary btn-facebook rounded-circle" href="javascript:;" aria-label="Facebook">
                        <i class="ai-facebook"></i>
                    </a>
                    <a class="btn btn-icon btn-sm btn-secondary btn-youtube rounded-circle" href="javascript:;" aria-label="YouTube">
                        <i class="ai-youtube"></i>
                    </a>
                </div>
                <p class="nav fs-sm order-sm-1 justify-content-center mb-0">
                    <span class="text-body-secondary">&copy; All rights reserved.</span>
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
		$("#temporary").fadeOut(20000);
    </script>
</body>
</html>
