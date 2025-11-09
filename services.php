<?php
    $title = "Our Services - ";
    require_once "system/DatabaseConnector.php";
    include "system/inc/head.php";
    include "system/inc/nav.php";

?>
    <!-- Breadcrumb + Services grid -->
    <section class="container py-5 mt-5 mb-md-3 mb-lg-4 mb-xxl-5">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="pt-lg-3 pb-lg-4 pb-2 breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Services</li>
            </ol>
        </nav>

        <!-- Page title -->
        <h1 class="text-center">Our services</h1>
        <p class="text-center pb-4 mb-2 mb-lg-3">We offer a comprehensive range of home care services designed to meet the unique needs of the elderly, the sick, and children. Our professional caregivers are trained to provide compassionate and reliable care.</p>

        <!-- Services grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 pb-sm-3 pb-md-4 pb-xl-5">

            <!-- Item -->
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Non-Medical Transportation (Local-OOT Driver)</h3>
                        <p class="card-text">We provide safe and reliable non-medical transportation for local and out-of-town appointments. Our trained drivers ensure clients reach their destinations comfortably and on time.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Chore - Lawn Care</h3>
                        <p class="card-text">Our lawn care team maintains a clean and safe outdoor environment by mowing lawns, trimming hedges, and ensuring your surroundings stay beautiful and well-kept.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Home Service</h3>
                        <p class="card-text">We offer home services including housekeeping, meal preparation, laundry, and other daily tasks to maintain a clean and comfortable living environment.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Non-Medical Transportation (Escort)</h3>
                        <p class="card-text">For clients needing assistance during travel, our trained escorts accompany and support them throughout their journey—ensuring comfort, safety, and peace of mind.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Companionship Services</h3>
                        <p class="card-text">Our caregivers provide meaningful companionship and emotional support through conversation, shared activities, and assistance with daily routines to combat loneliness.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Transition Coordination</h3>
                        <p class="card-text">We assist clients transitioning between care settings by coordinating appointments, transportation, and home readiness for a smooth experience.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Extended Personal Care - Nurse</h3>
                        <p class="card-text">Skilled nurses provide comprehensive personal care for clients with advanced health needs, including medication management, wound care, and routine monitoring.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Nurse Education</h3>
                        <p class="card-text">We offer nurse-led education programs to help clients and families understand health conditions, medications, and daily care requirements.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Extended Personal Care - Non-Nurse</h3>
                        <p class="card-text">Our trained caregivers assist with personal hygiene, grooming, feeding, and mobility support to maintain comfort and dignity.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Personal Care Service/Waiver - Unit Rate</h3>
                        <p class="card-text">Tailored personal care services designed under waiver programs, ensuring accessible, affordable, and quality in-home support.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Respite Care</h3>
                        <p class="card-text">We provide temporary relief for family caregivers by taking over their duties for a period, ensuring clients receive continuous care.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Supervision</h3>
                        <p class="card-text">Our supervision services ensure clients remain safe and supported at home or in the community, providing oversight and assistance with daily activities.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Personal Care Service - Unit Rate</h3>
                        <p class="card-text">Customized assistance for hygiene, dressing, meal prep, and other essential activities—charged per service unit for flexibility.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Chore - Snow Removal</h3>
                        <p class="card-text">Keep pathways safe during winter. Our snow removal team clears driveways, sidewalks, and entrances to ensure accessibility and prevent accidents.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Chore - Snow Removal</h3>
                        <p class="card-text">Keep pathways safe during winter. Our snow removal team clears driveways, sidewalks, and entrances to ensure accessibility and prevent accidents.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">One Time Transition Costs</h3>
                        <p class="card-text">We assist with one-time transition costs related to moving or setting up a new care environment, helping clients settle safely and comfortably.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card card-hover-primary bg-secondary border-0 h-100 text-decoration-none" href="#">
                    <div class="card-body pb-0 mb-sm-5">
                        <svg class="d-block text-warning mb-4" width="40" height="40">
                        </svg>
                        <h3 class="h4 card-title mt-0">Chore - Labor</h3>
                        <p class="card-text">We provide reliable general labor services, including home maintenance, moving support, and light repairs to maintain a safe living space.</p>
                    </div>
                </a>
            </div>
        </div>

   


    <!-- Additional Services -->
    <!-- <section class="container py-5 my-md-2 my-lg-4 border-top">
        <div class="row justify-content-center text-center pb-4 mb-2 mb-lg-3">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="h1 mb-lg-4">Additional Services</h2>
                <p class="fs-lg text-muted mb-0">We offer a range of supplementary services to complement our core care offerings.</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                            <i class="ai-home d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Household Management</h3>
                        <p class="fs-sm mb-0">Light housekeeping, laundry, meal preparation, grocery shopping, and other household tasks to maintain a clean and comfortable living environment.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                            <i class="ai-rocket d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Transportation Services</h3>
                        <p class="fs-sm mb-0">Safe and reliable transportation to medical appointments, social activities, shopping, and other outings to help maintain independence and social connections.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-table bg-secondary rounded-3 p-3 mb-4">
                            <i class="ai-calendar d-block h3 text-primary mb-0"></i>
                        </div>
                        <h3 class="h5 mb-2">Respite Care</h3>
                        <p class="fs-sm mb-0">Temporary relief for family caregivers, allowing them to take a break while ensuring their loved ones receive the care they need.</p>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- CTA section -->
    <section class="container py-5 my-md-2 my-lg-4">
        <div class="bg-secondary rounded-3 py-5 px-3 px-sm-4 px-lg-5">
            <div class="text-center py-md-3 py-lg-5">
                <h2 class="h1 pb-3">Ready to Get Started?</h2>
                <p class="fs-lg pb-3 mb-3">Contact us today to discuss your care needs and how we can help.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center">
                    <a href="<?= PROOT; ?>request" class="btn btn-primary btn-lg me-sm-3 mb-3">Request Service</a>
                    <a href="<?= PROOT; ?>contact" class="btn btn-outline-primary btn-lg mb-3">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

<?php include "system/inc/footer.php"; ?>