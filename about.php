<?php
    $title = "About Us - ";
    require_once "system/DatabaseConnector.php";
    include "system/inc/head.php";
?>

<!-- Main content -->
<main class="page-wrapper">
    <!-- Hero section -->
    <section class="container pt-5 mt-5">
        <div class="row pt-md-4 pt-lg-5 pb-xl-3">
            <div class="col-lg-10 col-xl-8 mx-auto text-center">
                <h1 class="display-3 pb-3">About Our Home Care Services</h1>
                <p class="fs-lg pb-3 mb-3">We are dedicated to providing exceptional home care services for the elderly, the sick, and children, ensuring they receive the attention and support they need in the comfort of their own homes.</p>
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
                    <p class="fs-lg">Our mission is to enhance the quality of life for our clients by providing personalized care services that promote independence, dignity, and well-being. We strive to be the leading provider of home care services, known for our exceptional care, reliability, and compassion.</p>
                    <p class="fs-lg mb-0">We believe that everyone deserves to receive care in the comfort of their own home, surrounded by familiar settings and loved ones. Our dedicated team works tirelessly to make this possible for all our clients.</p>
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
                        <p class="fs-sm mb-0">We approach each client with genuine care, empathy, and understanding of their unique needs and circumstances.</p>
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
                        <p class="fs-sm mb-0">We are committed to providing consistent, dependable care that our clients and their families can count on.</p>
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
                        <p class="fs-sm mb-0">We strive for the highest standards in all aspects of our service, from caregiver selection to care delivery.</p>
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
                        <p class="fs-sm mb-0">We conduct our business with honesty, transparency, and ethical practices that earn the trust of our clients.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our team -->
    <section class="container py-5 my-md-2 my-lg-4">
        <div class="row justify-content-center text-center pb-4 mb-2 mb-lg-3">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="h1 mb-lg-4">Our Leadership Team</h2>
                <p class="fs-lg text-muted mb-0">Meet the dedicated professionals who lead our organization with expertise and passion.</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <!-- Team member 1 -->
            <div class="col">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-inline-block rounded-circle position-relative mb-3" style="width: 120px; height: 120px;">
                            <img src="assets/media/avatar.png" class="rounded-circle" width="120" alt="Team Member">
                        </div>
                        <h3 class="h5 mb-1">Dr. Emily Johnson</h3>
                        <p class="text-muted mb-2">Founder & CEO</p>
                        <p class="fs-sm mb-0">With over 15 years of experience in healthcare management, Dr. Johnson leads our team with vision and compassion.</p>
                    </div>
                </div>
            </div>
            <!-- Team member 2 -->
            <div class="col">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-inline-block rounded-circle position-relative mb-3" style="width: 120px; height: 120px;">
                            <img src="assets/media/avatar.png" class="rounded-circle" width="120" alt="Team Member">
                        </div>
                        <h3 class="h5 mb-1">Michael Roberts</h3>
                        <p class="text-muted mb-2">Operations Director</p>
                        <p class="fs-sm mb-0">Michael ensures our day-to-day operations run smoothly, maintaining our high standards of care.</p>
                    </div>
                </div>
            </div>
            <!-- Team member 3 -->
            <div class="col">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-inline-block rounded-circle position-relative mb-3" style="width: 120px; height: 120px;">
                            <img src="assets/media/avatar.png" class="rounded-circle" width="120" alt="Team Member">
                        </div>
                        <h3 class="h5 mb-1">Sarah Thompson</h3>
                        <p class="text-muted mb-2">Head of Caregiver Training</p>
                        <p class="fs-sm mb-0">Sarah develops our comprehensive training programs, ensuring our caregivers provide exceptional care.</p>
                    </div>
                </div>
            </div>
            <!-- Team member 4 -->
            <div class="col">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-inline-block rounded-circle position-relative mb-3" style="width: 120px; height: 120px;">
                            <img src="assets/media/avatar.png" class="rounded-circle" width="120" alt="Team Member">
                        </div>
                        <h3 class="h5 mb-1">David Wilson</h3>
                        <p class="text-muted mb-2">Client Relations Manager</p>
                        <p class="fs-sm mb-0">David ensures our clients receive personalized attention and that their needs are met with care.</p>
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