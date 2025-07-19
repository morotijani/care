<?php
    $title = "Apply as Caregiver - Join Our Team | ";
    include_once "system/DatabaseConnector.php";
    include_once "system/inc/head.php";
    include_once "system/inc/nav.php";
    

    // Process application form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["apply_submit"])) {
        $name = sanitize($_POST["name"]);
        $email = sanitize($_POST["email"]);
        $phone = sanitize($_POST["phone"]);
        $experience = sanitize($_POST["experience"]);
        $service_type = sanitize($_POST["service_type"]);
        $availability = sanitize($_POST["availability"]);
        $message = sanitize($_POST["message"]);

        
        // Basic validation
        if (empty($name) || empty($email) || empty($phone) || empty($experience)) {
            $_SESSION["flash_error"] = "Please fill in all required fields.";
            redirect(PROOT . "apply");
        } else {
            // Here you would typically save to database and/or send email
            $sql = "
                INSERT INTO `care_applications`(`application_id`, `application_name`, `application_email`, `application_phone`, `application_experience`, `application_service_type`, `application_availability`, `application_message`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $statement = $dbConnection->prepare($sql);
            $result = $statement->execute([guidv4(), $name, $email, $phone, $experience, $service_type, $availability, $message]);
            if ($result) {
                // send email
                $to = $email;
                $subject = "Caregiver Application Received";
                $body = "
                    <p>
                        Thank you for your application. We will review your information and contact you soon.
                        <br><br>
                        Best regards,
                        <br>
                        - Care That Feels Like Home.
                    </p>
                ";
                send_email($name, $to, $subject, $body);

                // For now, we'll just show a success message
                $_SESSION["flash_success"] = "Thank you for your application! We will review your information and contact you soon.";
                redirect(PROOT . "apply");
            } else {
                $_SESSION["flash_error"] = "Something went wrong...please try again !";
                redirect(PROOT . "apply");
            }
        }
    }
?>

          <!-- Page content-->
      <section class="container pt-5 pb-lg-2 pb-xl-4 py-xxl-5 my-5">

            <!-- Breadcrumb-->
            <nav aria-label="breadcrumb">
                <ol class="pt-lg-3 pb-lg-4 pb-2 breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= PROOT; ?>">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Apply</li>
                </ol>
            </nav>

            <!-- Page title -->
            <h1 class="display-2 pb-2 mb-sm-4 mb-lg-5">Apply as a Caregiver</h1>
      </section>

    <!-- Application form -->
    <section class="container py-5 my-md-2 my-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h1 mb-4">Join Our Team of Caregivers</h2>
                        <p class="fs-lg mb-4">We're looking for compassionate, dedicated individuals to join our team of caregivers. If you have a passion for helping others and making a positive difference in people's lives, we'd love to hear from you.</p>
                        <form method="post" action="">
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="phone" class="form-label">Phone number <span class="text-danger">*</span></label>
                                    <input type="tel" id="phone" name="phone" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="experience" class="form-label">Years of experience <span class="text-danger">*</span></label>
                                    <select id="experience" name="experience" class="form-select form-select-lg" required>
                                        <option value="">Select experience</option>
                                        <option value="Less than 1 year">Less than 1 year</option>
                                        <option value="1-2 years">1-2 years</option>
                                        <option value="3-5 years">3-5 years</option>
                                        <option value="5+ years">5+ years</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="service_type" class="form-label">Preferred service type <span class="text-danger">*</span></label>
                                    <select id="service_type" name="service_type" class="form-select form-select-lg" required>
                                        <option value="">Select service type</option>
                                        <option value="Elderly Care">Elderly Care</option>
                                        <option value="Sick Care">Sick Care</option>
                                        <option value="Child Care">Child Care</option>
                                        <option value="Any">Any/All Services</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="availability" class="form-label">Availability <span class="text-danger">*</span></label>
                                    <select id="availability" name="availability" class="form-select form-select-lg" required>
                                        <option value="">Select availability</option>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Weekends only">Weekends only</option>
                                        <option value="Evenings only">Evenings only</option>
                                        <option value="Flexible">Flexible</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Tell us about yourself and your caregiving experience</label>
                                    <textarea id="message" name="message" class="form-control form-control-lg" rows="4"></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                                        <label for="terms" class="form-check-label">I agree to the <a href="#">terms and conditions</a> <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="apply_submit" class="btn btn-primary btn-lg shadow-primary">Submit Application</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why join us -->
    <section class="container py-5 my-md-2 my-lg-4 border-top">
        <div class="row justify-content-center text-center pb-4 mb-2 mb-lg-3">
            <div class="col-lg-8 col-md-10">
                <h2 class="h1 mb-4">Why Join Our Team?</h2>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ai-star text-primary fs-3 me-3"></i>
                            <h3 class="h5 mb-0">Competitive Pay</h3>
                        </div>
                        <p class="mb-0">We offer competitive compensation packages that recognize your skills, experience, and dedication.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ai-star text-primary fs-3 me-3"></i>
                            <h3 class="h5 mb-0">Flexible Scheduling</h3>
                        </div>
                        <p class="mb-0">We work with you to create a schedule that fits your lifestyle and availability.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ai-star text-primary fs-3 me-3"></i>
                            <h3 class="h5 mb-0">Professional Growth</h3>
                        </div>
                        <p class="mb-0">We provide ongoing training and development opportunities to enhance your skills and advance your career.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include_once "system/inc/footer.php"; ?>