<?php
    require_once "system/DatabaseConnector.php";
    $title = "Home Care Services";
    include "system/inc/head.php";
    include "system/inc/nav.php";
?>


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
                    <div class="swiper-slide-cover" style="background-image: url(<?= PROOT; ?>assets/media/nurse-education.jpg);"></div>
                </div>
                <div class="swiper-slide">
                    <div class="swiper-slide-cover" style="background-image: url(<?= PROOT; ?>assets/media/companionship.jpg);"></div>
                </div>
                <div class="swiper-slide">
                    <div class="swiper-slide-cover" style="background-image: url(<?= PROOT; ?>assets/media/home-service.jpg);"></div>
                </div>
            </div>
            <div class="swiper-pagination mb-4"></div>
        </div>

        <!-- Content -->
        <div class="container position-relative z-2 py-lg-3 py-xl-5 my-5">
            <div class="row pt-md-3 py-xxl-5 my-5">
                <div class="col py-5 mb-md-4 mb-lg-5">
                    <h1 class="display-1 fw-bold text-shadow-sm text-uppercase text-primary mb-4" style="text-shadow: 5px 0 0 #ccc;">Care That Feels Like Home</h1>
                    <p class="d-block fs-xl pb-2 mb-4 mb-md-5 text-white" style="max-width: 500px;">Providing compassionate care for the elderly, the sick, and children in the comfort of your home.</p>
                    <div class="position-relative d-inline-flex align-items-center">
                        <a href="<?= PROOT; ?>request" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                        <a href="<?= PROOT; ?>apply" class="btn btn-outline-light btn-lg mb-3">Apply as Caregiver</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="bg-secondary pb-2 pb-sm-3 pb-md-4 pb-lg-5 mb-xl-3 mb-xxl-5">
        <div class="container rounded-5 py-4 py-md-5 px-lg-5">
            <div class="row row-cols-2 row-cols-md-4 g-0">
                <div class="col d-md-flex justify-content-center text-center text-md-start position-relative">
                    <div class="position-absolute top-50 end-0 translate-middle-y border-end" style="height: 60px;"></div>
                    <div class="p-3 px-sm-0 py-sm-4">
                        <div class="h2 display-5 text-primary mb-0">540+</div>
                        <span>Happy clients</span>
                    </div>
                </div>
                <div class="col d-md-flex justify-content-center text-center text-md-start position-relative">
                    <div class="position-absolute top-50 end-0 translate-middle-y border-end d-none d-md-block" style="height: 60px;"></div>
                    <div class="p-3 px-sm-0 py-sm-4">
                        <div class="h2 display-5 text-primary mb-0">10+</div>
                        <span>Services</span>
                    </div>
                </div>
                <div class="col d-md-flex justify-content-center text-center text-md-start position-relative">
                    <div class="position-absolute top-50 end-0 translate-middle-y border-end" style="height: 60px;"></div>
                        <div class="p-3 px-sm-0 py-sm-4">
                            <div class="h2 display-5 text-primary mb-0">10+</div>
                            <span>Full time specialists</span>
                        </div>
                    </div>
                    <div class="col d-md-flex justify-content-center text-center text-md-start position-relative">
                    <div class="p-3 px-sm-0 py-sm-4">
                        <div class="h2 display-5 text-primary mb-0">100%</div>
                        <span>Good times</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


     <!-- Services -->
      <section class="container py-5 my-md-2 my-lg-3 my-xl-4 my-xxl-5">
        <div class="row align-items-center py-1 py-sm-3 py-md-4 my-lg-2">
          <div class="col-lg-4 offset-xl-1">
            <h2 class="h1 text-center text-lg-start pb-3 pb-lg-1">Our servises</h2>

            <!-- Show on screens > 992px -->
            <ul class="list-unstyled d-none d-lg-block pb-3 mb-3 mb-lg-4">
              <li class="d-flex pt-2">
                <i class="ai-check-alt fs-4 text-primary mt-n1 me-2"></i>
                We provide professional and
              </li>
              <li class="d-flex pt-2">
                <i class="ai-check-alt fs-4 text-primary mt-n1 me-2"></i>
                compassionate care services
              </li>
              <li class="d-flex pt-2">
                <i class="ai-check-alt fs-4 text-primary mt-n1 me-2"></i>
               tailored to meet the unique needs of each individual.
              </li>
            </ul>
            <a class="btn btn-primary d-none d-lg-inline-flex" href="<?= PROOT; ?>services">See all servises</a>
          </div>

          <div class="col-lg-8 col-xl-7 col-xxl-6">
            <div class="row row-cols-1 row-cols-sm-2">
              <div class="col">
                <div class="card border-0 bg-primary bg-opacity-10">
                  <div class="card-body">
                    <i class="ai-rocket fs-1 text-primary d-block mb-3"></i>
                    <h3 class="h4">Non-Medical Transportation</h3>
                    <p class="fs-sm">Safe and reliable transport for local and out-of-town appointments.</p>
                  </div>
                </div>
                <div class="card border-0 bg-info bg-opacity-10 mt-4">
                  <div class="card-body">
                    <i class="ai-home fs-1 text-info d-block mb-3"></i>
                    <h3 class="h4">Home Services</h3>
                    <p class="fs-sm">Comprehensive support for daily living, meal preparation, and home upkeep.</p>
                  </div>
                </div>
              </div>
              <div class="col pt-lg-3">
                <div class="card border-0 bg-warning bg-opacity-10 mt-4 mt-sm-0 mt-lg-4">
                  <div class="card-body">
                    <i class="ai-heart-filled fs-1 text-warning d-block mb-3"></i>
                    <h3 class="h4">Companionship</h3>
                    <p class="fs-sm">Meaningful companionship that nurtures emotional well-being.</p>
                  </div>
                </div>
                <div class="card border-0 bg-danger bg-opacity-10 mt-4">
                  <div class="card-body">
                    <i class="ai-user-group fs-2 text-danger d-block mb-3"></i>
                    <h3 class="h4">Nurse Education</h3>
                    <p class="fs-sm">Skilled nurses provide comprehensive personal care for clients with advanced health needs...</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Show button on screens < 992px -->
            <div class="d-lg-none text-center pt-3 mt-3 mt-sm-4">
              <a class="btn btn-primary" href="#">See all servises</a>
            </div>
          </div>
        </div>
      </section>


       <!-- How it works (Steps) -->
      <section class="overflow-hidden pt-5 mt-md-3 mt-lg-2 mt-xl-4 mt-xxl-5">
        <div class="container pt-2 pt-sm-4 pt-lg-5">
          <h2 class="h1 text-center pb-3 mb-0">How does it work?</h2>
            <p class="fs-lg text-muted text-center mb-3 mb-lg-4">Getting started with our home care services is simple and straightforward.</p>


          <!-- Step -->
          <div class="row align-items-center position-relative pb-5 pb-lg-0 mb-1 mb-sm-2 mb-md-4 mb-lg-0">
            <div class="col-md-6 col-xl-5 offset-lg-1 order-md-2 pb-2 pb-md-0 mb-4 mb-md-0" data-aos="fade-left" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <img class="d-dark-mode-none" src="<?= PROOT; ?>assets/media/request.png" width="525" alt="Image">
              <img class="d-none d-dark-mode-block" src="<?= PROOT; ?>assets/media/request.png" width="525" alt="Image">
            </div>
            <div class="col-md-6 col-lg-5 col-xl-4 offset-xl-1 order-md-1" data-aos="fade-right" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <div class="pe-md-4 pe-lg-0">
                <span class="badge fs-sm bg-primary bg-opacity-10 text-primary mb-3 mb-lg-4">Step 01</span>
                <h3 class="h2 mb-3 mb-lg-4">Request Service</h3>
                <p class="pb-1 pb-lg-0 mb-4 mb-lg-5">Fill out our online form with your care needs and preferences.</p>
              </div>
            </div>
          </div>

          <!-- Arrow -->
          <div class="d-none d-lg-flex justify-content-center" data-aos="fade-in" data-aos-duration="500" data-aos-offset="250">
            <svg class="d-block text-primary" width="339" height="365" viewBox="0 0 339 365" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-left: -150px; margin-top: -140px; margin-bottom: -108px;">
              <path d="M324 291.371C120.111 291.37 240.756 58.7225 1.00032 73.2606" stroke="url(#arrow1)" stroke-width="2" stroke-linecap="round" stroke-dasharray="6 6"></path>
              <path d="M337.375 290.62C338.074 290.998 338.074 292.001 337.375 292.379L328.476 297.196C327.81 297.557 327 297.074 327 296.317L327 286.683C327 285.925 327.81 285.443 328.476 285.803L337.375 290.62Z" fill="currentColor"></path>
              <defs>
                <linearGradient id="arrow1" x1="324" y1="291.5" x2="-2.99974" y2="72.4997" gradientUnits="userSpaceOnUse">
                  <stop offset="0" stop-color="currentColor"></stop>
                  <stop offset="1" stop-color="currentColor" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
            </svg>
          </div>

          <!-- Step -->
          <div class="row align-items-center position-relative pb-5 pb-lg-0 mb-1 mb-sm-2 mb-md-4 mb-lg-0">
            <div class="col-md-6 col-xl-5 offset-xl-1 pb-2 pb-md-0 mb-4 mb-md-0" data-aos="fade-right" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <img class="d-dark-mode-none" src="<?= PROOT; ?>assets/media/consultation.png" width="473" alt="Image">
              <img class="d-none d-dark-mode-block" src="<?= PROOT; ?>assets/media/consultation.png" width="473" alt="Image">
            </div>
            <div class="col-md-6 col-lg-5 col-xl-4 offset-lg-1" data-aos="fade-left" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <div class="ps-md-4 ps-lg-0">
                <span class="badge fs-sm bg-primary bg-opacity-10 text-primary mb-3 mb-lg-4">Step 02</span>
                <h3 class="h2 mb-3 mb-lg-4">Consultation</h3>
               <p class="pb-1 pb-lg-0 mb-4 mb-lg-5">We'll contact you to discuss your specific needs and create a care plan.</p>
              </div>
            </div>
          </div>

          

          <!-- Arrow -->
          <div class="d-none d-lg-flex justify-content-center" data-aos="fade-in" data-aos-duration="500" data-aos-offset="250">
            <svg class="d-block text-primary" width="263" height="275" viewBox="0 0 263 275" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-top: -60px; margin-left: -120px; margin-bottom: -80px;">
              <path d="M8.13678 249.647C7.47108 250.081 6.59001 249.602 6.59106 248.808L6.60444 238.689C6.60544 237.931 7.4158 237.45 8.08162 237.811L16.5478 242.408C17.2136 242.77 17.2512 243.712 16.6163 244.125L8.13678 249.647Z" fill="currentColor"></path>
              <path d="M261.961 37.8891C216.908 65.6243 128.226 135.486 133.916 193.05C141.029 265.005 265.134 173.468 173.666 148.634C89.2542 125.715 30.9125 210.547 13.9796 236.702" stroke="url(#arrow2)" stroke-width="2" stroke-linecap="round" stroke-dasharray="6 6"></path>
              <defs>
                <linearGradient id="arrow2" x1="13.9797" y1="234.5" x2="276.704" y2="60.1939" gradientUnits="userSpaceOnUse">
                  <stop offset="0" stop-color="currentColor"></stop>
                  <stop offset="1" stop-color="currentColor" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
            </svg>
          </div>

          <!-- Step -->
          <div class="row align-items-center position-relative pb-5 pb-lg-0 mb-1 mb-sm-2 mb-md-4 mb-lg-0">
            <div class="col-md-6 col-xl-5 offset-lg-1 order-md-2 pb-2 pb-md-0 mb-4 mb-md-0" data-aos="fade-left" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <img class="d-dark-mode-none" src="<?= PROOT; ?>assets/media/matching.png" width="525" alt="Image">
              <img class="d-none d-dark-mode-block" src="<?= PROOT; ?>assets/media/matching.png" width="525" alt="Image">
            </div>
            <div class="col-md-6 col-lg-5 col-xl-4 offset-xl-1 order-md-1" data-aos="fade-right" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
              <div class="pe-md-4 pe-lg-0">
                <span class="badge fs-sm bg-primary bg-opacity-10 text-primary mb-3 mb-lg-4">Step 03</span>
                <h3 class="h2 mb-3 mb-lg-4">Caregiver Matching</h3>
                <p class="pb-1 pb-lg-0 mb-4 mb-lg-5">We'll match you with the most suitable caregiver for your needs.</p>
              </div>
            </div>
          </div>

          <!-- Arrow -->
          <div class="d-none d-lg-flex justify-content-center" data-aos="fade-in" data-aos-duration="500" data-aos-offset="250">
            <svg class="d-block text-primary" width="339" height="365" viewBox="0 0 339 365" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-left: -150px; margin-top: -140px; margin-bottom: -108px;">
              <path d="M324 291.371C120.111 291.37 240.756 58.7225 1.00032 73.2606" stroke="url(#arrow1)" stroke-width="2" stroke-linecap="round" stroke-dasharray="6 6"></path>
              <path d="M337.375 290.62C338.074 290.998 338.074 292.001 337.375 292.379L328.476 297.196C327.81 297.557 327 297.074 327 296.317L327 286.683C327 285.925 327.81 285.443 328.476 285.803L337.375 290.62Z" fill="currentColor"></path>
              <defs>
                <linearGradient id="arrow1" x1="324" y1="291.5" x2="-2.99974" y2="72.4997" gradientUnits="userSpaceOnUse">
                  <stop offset="0" stop-color="currentColor"></stop>
                  <stop offset="1" stop-color="currentColor" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
            </svg>
          </div>
          

            <!-- Step -->
            <div class="row align-items-center position-relative">
                
                <div class="col-md-6 col-lg-5 col-xl-4 offset-lg-1 order-md-2" data-aos="fade-right" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
                    <div class="pe-md-4 pe-lg-0">
                        <span class="badge fs-sm bg-primary bg-opacity-10 text-primary mb-3 mb-lg-4">Step 04</span>
                        <h3 class="h2 mb-3 mb-lg-4">Begin Care</h3>
                        <p class="pb-1 pb-lg-0 mb-4 mb-lg-5">Your caregiver will start providing the agreed-upon services at your home.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-5 offset-xl-1 order-md-1 d-md-flex justify-content-end pb-2 pb-md-0 mb-4 mb-md-0" data-aos="fade-left" data-aos-duration="500" data-aos-offset="250" data-disable-parallax-down="md">
                    <img class="d-dark-mode-none" src="<?= PROOT; ?>assets/media/care.png" width="473" alt="Image">
                    <img class="d-none d-dark-mode-block" src="<?= PROOT; ?>assets/media/care.png" width="473" alt="Image">
                </div>
            </div>
        </div>
    </section>


    
    <!-- Testimonials carousel -->
    <section class="bg-secondary">
        <div class="container py-5 mb-1 mb-sm-2 my-lg-3 my-xl-4 my-xxl-5">
            <div class="row justify-content-center pt-2 pt-sm-4 pb-4 mb-2 mb-lg-3">
                <div class="col-lg-8 col-xl-7 col-xxl-6 text-center pt-2">
                    <h2 class="h1 mb-1">Testimonials from our clients</h2>
                </div>
            </div>
            <div class="swiper pb-2 pb-sm-4" data-swiper-options='{
                "slidesPerView": 1,
                "spaceBetween": 24,
                "loop": true,
                "pagination": {
                    "el": ".swiper-pagination",
                    "clickable": true
                },
                "breakpoints": {
                    "800": {
                        "slidesPerView": 2
                    }
                }
            }'>
                <div class="swiper-wrapper pt-5">

                    <!-- Item -->
                    <div class="swiper-slide h-auto">
                        <div class="card border-0 bg-info bg-opacity-10 h-100 text-center">
                            <div class="polygon-avatar bg-info mx-auto translate-middle-y">
                                <img src="<?= PROOT; ?>assets/media/default-profile.png" alt="Sarah Johnson">
                            </div>
                            <div class="card-body pt-0 mt-n4">
                                <p class="card-text fs-xl">"The caregiver assigned to my mother was exceptional. She was professional, compassionate, and truly cared about my mother's well-being. I highly recommend this service to anyone looking for quality home care."</p>
                            </div>
                            <div class="card-footer border-0 pt-0">
                                <div class="h4 mb-1">Sarah Johnson</div>
                                <span>Elderly Care Client</span>
                            </div>
                        </div>
                    </div>

                    <!-- Item -->
                    <div class="swiper-slide h-auto">
                        <div class="card border-0 bg-warning bg-opacity-10 h-100 text-center">
                            <div class="polygon-avatar bg-warning mx-auto translate-middle-y">
                                <img src="<?= PROOT; ?>assets/media/default-profile.png" alt="Michael & Lisa Thompson">
                            </div>
                            <div class="card-body pt-0 mt-n4">
                                <p class="card-text fs-xl">"Finding reliable childcare was a challenge until we discovered this service. Our caregiver is amazing with our children, and we feel completely at ease knowing they are in good hands."</p>
                            </div>
                            <div class="card-footer border-0 pt-0">
                                <div class="h4 mb-1">Michael & Lisa Thompson</div>
                                <span>Child Care Clients</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination (bullets) -->
                <div class="swiper-pagination position-relative bottom-0 mt-2 mt-lg-3 pt-4"></div>
            </div>
        </div>
    </section>

    <!-- CTA section -->
    <section class="container py-5 my-md-2 my-lg-4 my-xl-5">
        <div class="row align-items-center">
            <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(<?= PROOT; ?>assets/media/started.jpg); background-size: cover;">
                </div>
            </div>
            <div class="col-md-6 col-xl-5 offset-xl-1">
                <h2 class="h1 pb-3">Ready to Get Started?</h2>
                <p class="fs-lg pb-3 mb-3">Whether you need care for yourself, a loved one, or are interested in joining our team of caregivers, we're here to help.</p>
                <div class="d-flex flex-column flex-sm-row">
                    <a href="<?= PROOT; ?>request" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                    <a href="<?= PROOT; ?>apply" class="btn btn-outline-primary btn-lg mb-3">Apply as Caregiver</a>
                </div>
            </div>
        </div>
    </section>

    <section class="container pt-5 pb-sm-3 mt-2 mt-sm-4 mb-2 mb-md-3 mb-lg-4 mb-xxl-5">
        <!-- Item -->
        <div class="row align-items-center pt-xl-2 pb-5 mb-lg-2 mb-xl-3 mb-xxl-4">
            <div class="col-md-7 col-lg-6 mb-4 mb-md-0">
                <a class="d-block position-relative" href="portfolio-single-v1.html">
                    <div class="bg-danger rounded-5 position-absolute top-0 start-0 w-100 h-100" data-aos="zoom-in" data-aos-duration="600" data-aos-offset="250"></div>
                    <img class="d-block position-relative z-2 ms-auto" src="<?= PROOT; ?>assets/media/community.jpeg" style="width: 536px; height: 436px; object-fit: contain" alt="Image" data-aos="fade-in" data-aos-duration="400" data-aos-offset="250">
                </a>
            </div>
            <div class="col-md-5 col-xl-4 offset-lg-1" data-aos="fade-up" data-aos-duration="400" data-aos-offset="170">
                <div class="ps-md-3 ps-lg-0">
                    <h2 class="h4 mb-lg-4">We are serving Bismarck Mandan surrounding community.</h2>
                    <p class="fs-sm pb-3 pb-lg-4 mb-3">Area of operations</p>
                </div>
            </div>
        </div>
    </section>


<?php include "system/inc/footer.php"; ?>