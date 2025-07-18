<?php
    $title = "About Us - ";
    require_once "system/DatabaseConnector.php";
    include "system/inc/head.php";
    include "system/inc/nav.php";
?>

    <!-- Hero -->
    <section class="bg-dark position-relative py-5">
        <div class="d-none d-dark-mode-block position-absolute top-0 start-0 w-100 h-100" style="background-color: rgba(255,255,255, .03);"></div>
            <div class="container position-relative z-2 py-5 mb-4 mb-sm-5" data-bs-theme="dark">
                <div class="row pb-5 mb-2 mb-sm-0 mb-lg-3">
                    <div class="col-lg-10 col-xl-9">

                    <!-- Breadcrumb -->
                        <nav aria-label="breadcrumb">
                            <ol class="pt-lg-3 pb-lg-4 pb-2 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?= PROOT; ?>">Home</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">About us</li>
                            </ol>
                        </nav>

                        <h1 class="display-2 pb-2 pb-sm-3">About Our Home Care Services</h1>
                        <p class="fs-lg text-body">At Care that Feels like Home, we are dedicated to delivering compassionate, high-quality home health care to individuals and families in Bismarck, North Dakota. Our mission is to enhance the quality of life for our clients by supporting their independence, dignity, and well-being in the comfort of their own homes.
                        <br>
                        <br>
                        We offer a full range of services including skilled nursing, personal care, physical and occupational therapy, and homemaker support. Whether you’re recovering from surgery, managing a chronic condition, or seeking daily assistance, our experienced and caring team is here to help.
                        <br>
                        <br>
                        With a strong commitment to professionalism, personalized care, and community connection, we aim to become a trusted home health provider in Bismarck—where care truly feels like home.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our mission -->
        <section class="container py-5 my-md-2 my-lg-4">
            <div class="row align-items-center">
                <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                    <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(assets/media/cover.jpg);"></div>
                </div>
                <div class="col-md-6">
                    <div class="ps-md-4 ps-lg-0">
                        <h2 class="h1 mb-md-4">Our Mission</h2>
                        <p class="fs-lg">At Care that Feels like Home, we are committed to providing compassionate and professional home health care that enhances the independence, dignity, and well-being of individuals and families in Bismarck, North Dakota. </p>
                        <p class="fs-lg mb-0">Through personalized services—from skilled nursing and therapy to daily support—we strive to deliver comfort and trust, ensuring that every home feels like the heart of care.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our values -->
        <section class="container py-5 my-md-2 my-lg-4">
            <div class="row justify-content-center text-center pb-4 mb-2 mb-lg-3">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <h2 class="h1 mb-lg-4">Our Core Values</h2>
                    <p class="fs-lg text-muted mb-0">These principles guide everything we do and ensure we provide the highest quality of care.</p>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                                <i class="ai-heart d-block h3 text-primary mb-0"></i>
                            </div>
                            <h3 class="h5 mb-2">Compassion</h3>
                            <p class="fs-sm mb-0">We treat every client with heartfelt empathy and respect, recognizing the unique challenges they face. Our caregivers are not just skilled professionals—they are warm companions who bring comfort and care into each home.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                                <i class="ai-check d-block h3 text-primary mb-0"></i>
                            </div>
                            <h3 class="h5 mb-2">Reliability</h3>
                            <p class="fs-sm mb-0">Families count on us, and we honor that trust by delivering consistent, dependable services. From daily assistance to specialized nursing, our team is there when needed—no compromises.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                                <i class="ai-star d-block h3 text-primary mb-0"></i>
                            </div>
                            <h3 class="h5 mb-2">Excellence</h3>
                            <p class="fs-sm mb-0">We pursue the highest standards in home health care through continuous learning, quality assurance, and personal dedication. Every touchpoint reflects our commitment to professionalism and top-tier service.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                                <i class="ai-shield d-block h3 text-primary mb-0"></i>
                            </div>
                            <h3 class="h5 mb-2">Integrity</h3>
                            <p class="fs-sm mb-0">We act with honesty, transparency, and accountability in all we do. Our relationships—with clients, families, and the community—are built on trust and ethical care.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our team -->

        <!-- Team -->
        <section class="pt-5 mt-2 mt-xl-4 mt-xxl-5">
            <div class="container pt-3 pt-sm-4 pt-lg-5 mt-xl-2 mt-xxl-3">
                <div class="fs-sm text-uppercase mb-3">Our team</div>
                <h2 class="display-6 pb-3 mb-lg-4">Team of professionals</h2>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">

                    <!-- Item -->
                    <div class="col">
                        <div class="card card-hover border-0 rounded-5 overflow-hidden">
                            <img src="<?= PROOT; ?>assets/media/team/team-2.JPG" alt="Image">
                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
                                <div class="bg-dark position-absolute top-0 start-0 w-100 h-100 opacity-50"></div>
                                <div class="card-body d-flex flex-column justify-content-end h-100 position-relative z-2 text-center" data-bs-theme="dark">
                                    <h3 class="h5 mb-1">Jane Cooper</h3>
                                    <p class="text-body mb-3">Head of PR department</p>
                                    <div class="d-flex justify-content-center">
                                        <a class="btn btn-icon btn-sm btn-light btn-instagram rounded-circle mx-2" href="#" aria-label="Instagram">
                                            <i class="ai-instagram"></i>
                                        </a>
                                        <a class="btn btn-icon btn-sm btn-light btn-facebook rounded-circle mx-2" href="#" aria-label="Facebook">
                                            <i class="ai-facebook"></i>
                                        </a>
                                        <a class="btn btn-icon btn-sm btn-light btn-linkedin rounded-circle mx-2" href="#" aria-label="LinkedIn">
                                            <i class="ai-linkedin"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item -->
                    <div class="col">
                        <div class="card card-hover border-0 rounded-5 overflow-hidden">
                            <img src="<?= PROOT; ?>assets/media/team/team-1.JPG" alt="Image">
                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
                                <div class="bg-dark position-absolute top-0 start-0 w-100 h-100 opacity-50"></div>
                                <div class="card-body d-flex flex-column justify-content-end h-100 position-relative z-2 text-center" data-bs-theme="dark">
                                    <h3 class="h5 mb-1">Guy Hawkins</h3>
                                    <p class="text-body mb-3">Head of marketing</p>
                                    <div class="d-flex justify-content-center">
                                        <a class="btn btn-icon btn-sm btn-light btn-facebook rounded-circle mx-2" href="#" aria-label="Facebook">
                                            <i class="ai-facebook"></i>
                                        </a>
                                        <a class="btn btn-icon btn-sm btn-light btn-x rounded-circle mx-2" href="#" aria-label="X">
                                            <i class="ai-x"></i>
                                        </a>
                                        <a class="btn btn-icon btn-sm btn-light btn-linkedin rounded-circle mx-2" href="#" aria-label="LinkedIn">
                                            <i class="ai-linkedin"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- CTA section -->
    <section class="container py-5 my-md-2 my-lg-4">
        <div class="bg-secondary rounded-3 py-5 px-3 px-sm-4 px-lg-5">
            <div class="row align-items-center py-lg-4">
                <div class="col-md-6 col-lg-5 offset-lg-1 text-center text-md-start pb-5 pb-md-0 mb-2 mb-md-0">
                    <h2 class="h1 pb-3 pb-sm-4">Join Our Team of Caregivers</h2>
                    <p class="fs-lg mb-4 mb-lg-5">Are you passionate about helping others? Join our team of dedicated caregivers and make a difference in people's lives.</p>
                    <a href="apply.php" class="btn btn-primary btn-lg">Apply Now</a>
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(assets/media/cover.jpg);"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include "system/inc/footer.php"; ?>