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
                    <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(<?= PROOT; ?>assets/media/cover-4.jpg);"></div>
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

        <!-- Approach -->
        <section class="pt-5 mt-md-2 mt-xl-4 mt-xxl-5">
            <div class="container pt-2 pt-sm-4 pt-lg-5 mt-xxl-2">
                <div class="fs-sm text-uppercase mb-3">Our Core Values</div>
                <h5 class=" pb-3 mb-lg-4">These principles guide everything we do and ensure we provide the highest quality of care</h5>
                <div class="swiper" data-swiper-options='
                    {
                    "spaceBetween": 24,
                    "pagination": {
                        "el": ".swiper-pagination",
                        "clickable": true
                    },
                    "breakpoints": {
                        "576": { "slidesPerView": 3 },
                        "992": { "slidesPerView": 4 }
                    }
                    }
                '>
                    <div class="swiper-wrapper">

                        <!-- Item -->
                        <div class="swiper-slide h-auto">
                            <div class="card border-0 bg-secondary rounded-5 h-100">
                                <div class="card-body">
                                    <svg class="d-block mt-1 mt-sm-0 mb-4" width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                    <g class="text-info">
                                        <path d="M20.0004 36.0226L10.982 21.3535C9.42594 23.3156 8.49609 25.7968 8.49609 28.4955C8.49609 34.8492 13.6467 39.9998 20.0004 39.9998C26.3541 39.9998 31.5047 34.8492 31.5047 28.4955C31.5047 25.7969 30.5749 23.3156 29.0188 21.3535L20.0004 36.0226Z" fill="currentColor"></path>
                                    </g>
                                    <g class="text-primary">
                                        <path d="M39.3962 0H0.605469L20.0009 31.5477L39.3962 0ZM25.7601 7.62359L20.0009 16.9914L14.2416 7.62359H25.7601Z" fill="currentColor"></path>
                                    </g>
                                    </svg>
                                    <h3 class="h4">Compassion</h3>
                                    <p class="mb-0">We treat every client with heartfelt empathy and respect, recognizing the unique challenges they face. Our caregivers are not just skilled professionals—they are warm companions who bring comfort and care into each home.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item -->
                        <div class="swiper-slide h-auto">
                            <div class="card border-0 bg-secondary rounded-5 h-100">
                                <div class="card-body">
                                    <svg class="d-block mt-1 mt-sm-0 mb-4" width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                    <g class="text-info">
                                        <path d="M26.307 23.1116C26.307 28.3136 22.09 32.5307 16.888 32.5307C11.6859 32.5307 7.46891 28.3136 7.46891 23.1116C7.46891 17.9096 11.6859 13.6925 16.888 13.6925C17.1467 13.6925 17.4028 13.7038 17.6562 13.7243V6.24121C17.4016 6.22973 17.1455 6.22363 16.888 6.22363C7.56102 6.22363 0 13.7846 0 23.1116C0 32.4386 7.56102 39.9996 16.888 39.9996C26.2149 39.9996 33.7759 32.4386 33.7759 23.1116C33.7759 22.8541 33.7698 22.598 33.7584 22.3433H26.2753C26.2958 22.5968 26.307 22.8529 26.307 23.1116Z" fill="currentColor"></path>
                                    </g>
                                    <g class="text-primary">
                                        <path d="M40 20C40 8.9543 31.0457 0 20 0V20H40Z" fill="currentColor"></path>
                                    </g>
                                    </svg>
                                    <h3 class="h4">Reliability</h3>
                                    <p class="mb-0">Families count on us, and we honor that trust by delivering consistent, dependable services. From daily assistance to specialized nursing, our team is there when needed—no compromises.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item -->
                        <div class="swiper-slide h-auto">
                            <div class="card border-0 bg-secondary rounded-5 h-100">
                                <div class="card-body">
                                    <svg class="d-block mt-1 mt-sm-0 mb-4" width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                    <g class="text-primary">
                                        <path d="M25.2791 12.7502C28.1954 9.83389 28.1954 5.10556 25.2791 2.18921C22.3627 -0.727136 17.6344 -0.727137 14.718 2.18921C11.8017 5.10556 11.8017 9.83389 14.718 12.7502C17.6344 15.6666 22.3627 15.6666 25.2791 12.7502Z" fill="currentColor"></path>
                                    </g>
                                    <g class="text-info">
                                        <path d="M14.6859 29.3056C15.6559 27.0123 16.9202 24.8838 18.4577 22.9467C13.8666 17.9802 7.29664 14.8701 0 14.8701V40.0004H12.5259C12.5259 36.2925 13.2527 32.6942 14.6859 29.3056Z" fill="currentColor"></path>
                                    </g>
                                    <g class="text-primary">
                                        <path d="M40.0014 40.0004V14.8701C26.1223 14.8701 14.8711 26.1214 14.8711 40.0004H40.0014Z" fill="currentColor"></path>
                                    </g>
                                    </svg>
                                    <h3 class="h4">Excellence</h3>
                                    <p class="mb-0">We pursue the highest standards in home health care through continuous learning, quality assurance, and personal dedication. Every touchpoint reflects our commitment to professionalism and top-tier service.</p>
                                </div>
                            </div>
                        </div>
                        <!-- Item -->
                        <div class="swiper-slide h-auto">
                            <div class="card border-0 bg-secondary rounded-5 h-100">
                                <div class="card-body">
                                   <svg class="d-block mt-1 mt-sm-0 mb-4" width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                        <g class="text-info">
                                        <path d="M34.9811 16.2655C34.3635 26.3194 26.3194 34.3634 16.2656 34.981V40H40V16.2655H34.9811Z" fill="currentColor"></path>
                                        </g>
                                        <g class="text-info">
                                        <path d="M15.0195 30.0413C23.3152 30.0413 30.0403 23.3163 30.0403 15.0205H15.0195V30.0413Z" fill="currentColor"></path>
                                        </g>
                                        <g class="text-primary">
                                        <path d="M29.1953 10.0415C27.141 4.19328 21.571 0 15.0208 0C6.725 0 0 6.725 0 15.0208C0 21.571 4.19328 27.141 10.0415 29.1953V10.0415H29.1953Z" fill="currentColor"></path>
                                        </g>
                                    </svg>
                                    <h3 class="h4">Integrity</h3>
                                    <p class="mb-0">We act with honesty, transparency, and accountability in all we do. Our relationships—with clients, families, and the community—are built on trust and ethical care.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination (bullets) -->
                    <div class="swiper-pagination position-relative bottom-0 mt-2 pt-4 d-lg-none"></div>
                </div>
            </div>
        </section>

        <!-- Our team -->
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

<?php include "system/inc/footer.php"; ?>