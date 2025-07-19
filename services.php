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
                <li class="breadcrumb-item"><a href="<?= PROOT; ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Services</li>
            </ol>
        </nav>

        <!-- Page title -->
        <h1 class="text-center">Our Home Care Services</h1>
        <p class="text-center fs-lg pb-4 mb-2 mb-lg-3">We offer a comprehensive range of home care services designed to meet the unique needs of the elderly, the sick, and children. Our professional caregivers are trained to provide compassionate and reliable care.</p>
    </section>

    <!-- Elderly Care -->
    <section id="elderly" class="container py-5 my-md-2 my-lg-4">
        <div class="row align-items-center">
            <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(<?= PROOT; ?>assets/media/cover-1.jpg); background-size: cover;"></div>
            </div>
            <div class="col-md-6">
                <div class="ps-md-4 ps-lg-0">
                    <h2 class="h1 mb-md-4">Elderly Care</h2>
                    <p class="fs-lg">Our elderly care services are designed to help seniors maintain their independence while receiving the support they need. We understand that each senior has unique needs, which is why we create personalized care plans.</p>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Personal care assistance (bathing, grooming, dressing)</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Medication reminders and management</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Mobility assistance and fall prevention</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Companionship and emotional support</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Light housekeeping and meal preparation</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Transportation to appointments and errands</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sick Care -->
    <section id="sick" class="container py-5 my-md-2 my-lg-4 border-top">
        <div class="row align-items-center flex-md-row-reverse">
            <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(<?= PROOT; ?>assets/media/cover-6.jpg); background-size: cover;"></div>
            </div>
            <div class="col-md-6">
                <div class="pe-md-4 pe-lg-0">
                    <h2 class="h1 mb-md-4">Sick Care</h2>
                    <p class="fs-lg">Our sick care services provide professional support for individuals recovering from illness, surgery, or managing chronic conditions. We work closely with healthcare providers to ensure continuity of care.</p>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Medication administration and management</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Wound care and dressing changes</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Vital signs monitoring</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Post-surgery recovery assistance</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Chronic disease management</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Coordination with healthcare providers</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Child Care -->
    <section id="children" class="container py-5 my-md-2 my-lg-4 border-top">
        <div class="row align-items-center">
            <div class="col-md-6 pb-2 pb-md-0 mb-4 mb-md-0">
                <div class="ratio ratio-16x9 bg-position-center rounded-3 overflow-hidden" style="background-image: url(<?= PROOT; ?>assets/media/cover-7.jpg); background-size: cover;"></div>
            </div>
            <div class="col-md-6">
                <div class="ps-md-4 ps-lg-0">
                    <h2 class="h1 mb-md-4">Child Care</h2>
                    <p class="fs-lg">Our child care services provide nurturing and stimulating care for children of all ages. We create a safe and supportive environment that promotes learning and development.</p>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Age-appropriate activities and play</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Homework help and educational support</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Meal preparation and feeding assistance</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Bathing and personal care</span>
                    </div>
                    <div class="d-flex align-items-center pb-2 mb-1">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Transportation to and from school or activities</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="ai-check-circle text-primary me-2"></i>
                        <span>Special needs care and support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Services -->
    <section class="container py-5 my-md-2 my-lg-4 border-top">
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
    </section>

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