    <!-- Page wrapper -->
    <main class="page-wrapper">

        <!-- Navbar. Remove 'fixed-top' class to make the navigation bar scrollable with the page -->
        <!-- <header class="navbar navbar-expand-lg fixed-top" data-bs-theme="light"> -->
        <header class="navbar navbar-expand-lg fixed-top bg-light">

            <div class="container">

                <!-- Navbar brand (Logo) -->
                <a class="navbar-brand pe-sm-3" href="<?= PROOT; ?>">
                    <span class="text-primary flex-shrink-0 me-2">
                        <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" class="img-fluid" alt="Logo" width="35" height="32" style="border-radius: 5px;">
                    </span>
                </a>

                <!-- Theme switcher -->
                <div class="form-check form-switch mode-switch order-lg-2 me-3 me-lg-4 ms-auto" data-bs-toggle="mode">
                    <input class="form-check-input" type="checkbox" id="theme-mode">
                    <label class="form-check-label" for="theme-mode">
                    <i class="ai-sun fs-lg"></i>
                    </label>
                    <label class="form-check-label" for="theme-mode">
                    <i class="ai-moon fs-lg"></i>
                    </label>
                </div>

                <a class="btn btn-primary btn-sm fs-sm order-lg-3 d-none d-sm-inline-flex" href="<?= PROOT; ?>request">
                    Request service
                </a>

                <!-- Mobile menu toggler (Hamburger) -->
                <button class="navbar-toggler ms-sm-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar collapse (Main navigation) -->
                <nav class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav navbar-nav-scroll me-auto" style="--ar-scroll-height: 520px;">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= PROOT; ?>index">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= PROOT; ?>about">About us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= PROOT; ?>services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= PROOT; ?>apply">Apply as caregiver</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= PROOT; ?>contact">Contact us</a>
                    </li>
                    </ul>
                    <div class="d-sm-none p-3 mt-n3">
                        <a class="btn btn-primary w-100 mb-1" href="<?= PROOT; ?>request">
                            <i class="ai-cart fs-xl me-2 ms-n1"></i>
                            Request service
                        </a>
                    </div>
                </nav>
            </div>
        </header>
        