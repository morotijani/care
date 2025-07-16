<?php
    $title = "Home Care Services - Compassionate Care for Your Loved Ones | ";
    require_once "system/DatabaseConnector.php";
    include "system/inc/head.php";
    include "system/inc/nav.php";
?>

<!-- Main content -->

<!-- Hero slider -->
      <section class="position-relative min-vh-100 py-5" data-bs-theme="light">

        <!-- Background image slider -->
        <div class="swiper swiper-scale-effect position-absolute top-0 start-0 w-100 h-100" data-swiper-options='{
          "effect": "fade",
          "speed": 800,
          "autoplay": {
            "delay": 7000,
            "disableOnInteraction": false
          },
          "pagination": {
            "el": ".swiper-pagination",
            "clickable": true
          }
        }'>
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <div class="swiper-slide-cover bg-position-top-center" style="background-image: url(<?= PROOT; ?>assets/media/cover.jpg);"></div>
            </div>
            <div class="swiper-slide">
              <div class="swiper-slide-cover bg-position-top-center" style="background-image: url(<?= PROOT; ?>assets/media/cover.jpg);"></div>
            </div>
            <div class="swiper-slide">
              <div class="swiper-slide-cover bg-position-top-center" style="background-image: url(<?= PROOT; ?>assets/media/cover.jpg);"></div>
            </div>
          </div>
          <div class="swiper-pagination mb-4"></div>
        </div>

        <!-- Content -->
        <div class="container position-relative z-2 py-lg-3 py-xl-5 my-5">
          <div class="row pt-md-3 py-xxl-5 my-5">
            <div class="col py-5 mb-md-4 mb-lg-5">
              <h1 class="display-1 text-uppercase mb-4">Professional Home Care Services</h1>
              <p class="d-block text-body fs-xl pb-2 mb-4 mb-md-5" style="max-width: 500px;">Providing compassionate care for the elderly, the sick, and children in the comfort of your home.</p>
              <div class="position-relative d-inline-flex align-items-center">
                <a href="request.php" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                        <a href="apply.php" class="btn btn-outline-light btn-lg mb-3">Apply as Caregiver</a>
            </div>

            <!-- <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-md-start">
                        <a href="request.php" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                        <a href="apply.php" class="btn btn-outline-light btn-lg mb-3">Apply as Caregiver</a>
                    </div> -->


            </div>
          </div>
        </div>
      </section>



    <!-- Hero section with parallax effect -->
    <!-- <section class="position-relative pt-5">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: .5;"></div>
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background-image: url(assets/media/cover.jpg); background-position: center; background-repeat: no-repeat; background-size: cover;"></div>
        <div class="container position-relative zindex-2 pt-5 pb-md-2 pb-lg-4 pb-xl-5">
            <div class="row pt-3 pb-2 py-md-3 py-lg-4 py-xl-5">
                <div class="col-md-6 pt-xl-5 pb-md-5 text-center text-md-start">
                    <h1 class="display-3 text-white pb-3">Professional Home Care Services</h1>
                    <p class="fs-lg text-white opacity-70 pb-md-4 mb-4">Providing compassionate care for the elderly, the sick, and children in the comfort of your home.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-md-start">
                        <a href="request.php" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                        <a href="apply.php" class="btn btn-outline-light btn-lg mb-3">Apply as Caregiver</a>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Services overview -->
    <section class="container py-5 my-md-2 my-lg-4 my-xl-5">
        <div class="row justify-content-center text-center pt-xl-2 pb-4 mb-lg-3">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="h1 mb-lg-4">Our Care Services</h2>
                <p class="fs-lg text-muted mb-0">We provide professional and compassionate care services tailored to meet the unique needs of each individual.</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <!-- Elderly Care -->
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-table bg-secondary rounded-3 p-3 mx-auto mt-n3 mb-4">
                            <i class="ai-user-check d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Elderly Care</h3>
                        <p class="fs-sm mb-0">Compassionate assistance for seniors, including personal care, medication reminders, companionship, and mobility support.</p>
                    </div>
                    <div class="card-footer pt-0 border-0">
                        <a href="services.php#elderly" class="btn btn-outline-primary stretched-link">Learn more</a>
                    </div>
                </div>
            </div>
            <!-- Sick Care -->
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-table bg-secondary rounded-3 p-3 mx-auto mt-n3 mb-4">
                            <i class="ai-activity d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Sick Care</h3>
                        <p class="fs-sm mb-0">Professional care for individuals recovering from illness or surgery, including medication management and health monitoring.</p>
                    </div>
                    <div class="card-footer pt-0 border-0">
                        <a href="services.php#sick" class="btn btn-outline-primary stretched-link">Learn more</a>
                    </div>
                </div>
            </div>
            <!-- Child Care -->
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-table bg-secondary rounded-3 p-3 mx-auto mt-n3 mb-4">
                            <i class="ai-users d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Child Care</h3>
                        <p class="fs-sm mb-0">Reliable and nurturing care for children of all ages, including educational activities, meal preparation, and supervision.</p>
                    </div>
                    <div class="card-footer pt-0 border-0">
                        <a href="services.php#children" class="btn btn-outline-primary stretched-link">Learn more</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="container py-5 my-md-2 my-lg-4 my-xl-5">
        <div class="row justify-content-center text-center pt-xl-2 pb-4 mb-lg-3">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="h1 mb-lg-4">How It Works</h2>
                <p class="fs-lg text-muted mb-0">Getting started with our home care services is simple and straightforward.</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 pb-xl-3">
            <div class="col text-center">
                <div class="position-relative d-flex flex-column align-items-center">
                    <div class="bg-primary text-white fw-bold fs-lg rounded-circle d-flex align-items-center justify-content-center position-relative zindex-2" style="width: 50px; height: 50px;">1</div>
                    <div class="position-absolute start-50 translate-middle-x h-100 top-0 d-none d-lg-block" style="width: 1px; background-color: #dee2e6;"></div>
                </div>
                <h3 class="h5 pt-3 mb-2">Request Service</h3>
                <p class="mb-0">Fill out our online form with your care needs and preferences.</p>
            </div>
            <div class="col text-center">
                <div class="position-relative d-flex flex-column align-items-center">
                    <div class="bg-primary text-white fw-bold fs-lg rounded-circle d-flex align-items-center justify-content-center position-relative zindex-2" style="width: 50px; height: 50px;">2</div>
                    <div class="position-absolute start-50 translate-middle-x h-100 top-0 d-none d-lg-block" style="width: 1px; background-color: #dee2e6;"></div>
                </div>
                <h3 class="h5 pt-3 mb-2">Consultation</h3>
                <p class="mb-0">We'll contact you to discuss your specific needs and create a care plan.</p>
            </div>
            <div class="col text-center">
                <div class="position-relative d-flex flex-column align-items-center">
                    <div class="bg-primary text-white fw-bold fs-lg rounded-circle d-flex align-items-center justify-content-center position-relative zindex-2" style="width: 50px; height: 50px;">3</div>
                    <div class="position-absolute start-50 translate-middle-x h-100 top-0 d-none d-lg-block" style="width: 1px; background-color: #dee2e6;"></div>
                </div>
                <h3 class="h5 pt-3 mb-2">Caregiver Matching</h3>
                <p class="mb-0">We'll match you with the most suitable caregiver for your needs.</p>
            </div>
            <div class="col text-center">
                <div class="position-relative d-flex flex-column align-items-center">
                    <div class="bg-primary text-white fw-bold fs-lg rounded-circle d-flex align-items-center justify-content-center position-relative zindex-2" style="width: 50px; height: 50px;">4</div>
                </div>
                <h3 class="h5 pt-3 mb-2">Begin Care</h3>
                <p class="mb-0">Your caregiver will start providing the agreed-upon services at your home.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="bg-secondary py-5 my-md-2 my-lg-4 my-xl-5">
        <div class="container py-md-4 py-lg-5">
            <div class="row justify-content-center text-center pt-2 pb-4 mb-3">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <h2 class="h1 mb-lg-4">What Our Clients Say</h2>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning"></i>
                            </div>
                            <p class="card-text fs-base mb-3">"The caregiver assigned to my mother was exceptional. She was professional, compassionate, and truly cared about my mother's well-being. I highly recommend this service to anyone looking for quality home care."</p>
                            <div class="d-flex align-items-center">
                                <div class="ps-3">
                                    <h6 class="mb-0">Sarah Johnson</h6>
                                    <span class="fs-sm text-muted">Elderly Care Client</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning me-1"></i>
                                <i class="ai-star-filled fs-lg text-warning"></i>
                            </div>
                            <p class="card-text fs-base mb-3">"Finding reliable childcare was a challenge until we discovered this service. Our caregiver is amazing with our children, and we feel completely at ease knowing they are in good hands."</p>
                            <div class="d-flex align-items-center">
                                <div class="ps-3">
                                    <h6 class="mb-0">Michael & Lisa Thompson</h6>
                                    <span class="fs-sm text-muted">Child Care Clients</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA section -->
    <section class="container py-5 my-md-2 my-lg-4 my-xl-5">
        <div class="row align-items-center">
            <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(assets/media/cover.jpg);">
                </div>
            </div>
            <div class="col-md-6 col-xl-5 offset-xl-1">
                <h2 class="h1 pb-3">Ready to Get Started?</h2>
                <p class="fs-lg pb-3 mb-3">Whether you need care for yourself, a loved one, or are interested in joining our team of caregivers, we're here to help.</p>
                <div class="d-flex flex-column flex-sm-row">
                    <a href="request.php" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                    <a href="apply.php" class="btn btn-outline-primary btn-lg mb-3">Apply as Caregiver</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include "system/inc/footer.php"; ?>