<?php
    include_once "system/DatabaseConnector.php";
    $title = "Request Service - Get the Care You Need | ";
    include_once "system/inc/head.php";
    include "system/inc/nav.php";


    // Process service request form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["request_submit"])) {
        $name = sanitize($_POST["name"]);
        $email = sanitize($_POST["email"]);
        $phone = sanitize($_POST["phone"]);
        $service_type = sanitize($_POST["service_type"]);
        $care_for = sanitize($_POST["care_for"]);
        $start_date = sanitize($_POST["start_date"]);
        $frequency = sanitize($_POST["frequency"]);
        $hours = sanitize($_POST["hours"]);
        $message = sanitize($_POST["message"]);
        
        // Basic validation
        if (empty($name) || empty($email) || empty($phone) || empty($service_type)) {
            $_SESSION["flash_error"] = "Please fill in all required fields.";
            redirect(PROOT . "request.php");
        } else {
            // Here you would typically save to database and/or send email
             $sql = "
                INSERT INTO `care_service_requests`(`request_id`, `request_name`, `request_email`, `request_phone`, `request_service_type`, `request_care_for`, `request_start_date`, `request_frequency`, `request_hours`, `request_special_requirements`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $statement = $dbConnection->prepare($sql);
            $result = $statement->execute([guidv4(), $name, $email, $phone, $service_type, $care_for, $start_date, $frequency, $hours, $message]);
            if ($result) {
                // send email
                $to = $email;
                $subject = "Caregiver Request Received";
                $body = "
                    <p>
                        Thank you for your request. We will review your information and contact you soon.
                        <br><br>
                        Best regards,
                        <br>
                        - Care That Feels Like Home.
                    </p>
                ";
                send_email($name, $to, $subject, $body);

                // For now, we'll just show a success message
                $_SESSION["flash_success"] = "Thank you for your service request! We will contact you shortly to discuss your care needs.";
                redirect(PROOT . "request.php");
            }
        }
    }
?>

    

        <section class="container pt-5 pb-lg-2 pb-xl-4 py-xxl-5 my-5">

            <!-- Breadcrumb-->
            <nav aria-label="breadcrumb">
                <ol class="pt-lg-3 pb-lg-4 pb-2 breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= PROOT; ?>">Home</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Request</li>
                </ol>
            </nav>

            <!-- Page title -->
            <h1 class="display-2 pb-2 mb-sm-4 mb-lg-5">Request Our Services</h1>
        </section>

        <!-- Service request form -->
        <section class="container py-5 my-md-2 my-lg-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="h1 mb-4">Request Home Care Services</h2>
                            <p class="fs-lg mb-4">Please fill out the form below to request our home care services. We'll contact you promptly to discuss your specific needs and how we can help.</p>
                            <form method="post" action="">
                                <div class="row g-4">
                                    <div class="col-sm-6">
                                        <label for="name" class="form-label">Your name <span class="text-danger">*</span></label>
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
                                        <label for="service_type" class="form-label">Service type <span class="text-danger">*</span></label>
                                        <select id="service_type" name="service_type" class="form-select form-select-lg" required>
                                            <option value="">Select service type</option>
                                            <option value="Elderly Care">Elderly Care</option>
                                            <option value="Sick Care">Sick Care</option>
                                            <option value="Child Care">Child Care</option>
                                            <option value="Multiple Services">Multiple Services</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="care_for" class="form-label">Care is for <span class="text-danger">*</span></label>
                                        <select id="care_for" name="care_for" class="form-select form-select-lg" required>
                                            <option value="">Select option</option>
                                            <option value="Self">Self</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Child">Child</option>
                                            <option value="Other Family Member">Other Family Member</option>
                                            <option value="Friend">Friend</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="start_date" class="form-label">Preferred start date <span class="text-danger">*</span></label>
                                        <input type="date" id="start_date" name="start_date" class="form-control form-control-lg" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="frequency" class="form-label">Care frequency <span class="text-danger">*</span></label>
                                        <select id="frequency" name="frequency" class="form-select form-select-lg" required>
                                            <option value="">Select frequency</option>
                                            <option value="One-time">One-time</option>
                                            <option value="Daily">Daily</option>
                                            <option value="Weekly">Weekly</option>
                                            <option value="24/7 Care">24/7 Care</option>
                                            <option value="Other">Other (please specify in message)</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="hours" class="form-label">Hours needed (if applicable)</label>
                                        <input type="text" id="hours" name="hours" class="form-control form-control-lg" placeholder="e.g., 4 hours per day, 9am-5pm">
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Additional details about care needs</label>
                                        <textarea id="message" name="message" class="form-control form-control-lg" rows="4" placeholder="Please provide any specific care requirements or concerns"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                                            <label for="terms" class="form-check-label">I agree to the <a href="#">privacy policy</a> <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="request_submit" class="btn btn-primary btn-lg shadow-primary">Submit Request</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="container py-5 my-md-2 my-lg-4 border-top">
            <div class="row justify-content-center text-center pb-4 mb-2 mb-lg-3">
                <div class="col-lg-8 col-md-10">
                    <h2 class="h1 mb-4">How It Works</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <span class="display-4 fw-semibold text-primary">1</span>
                        </div>
                        <h3 class="h4 mb-2">Submit Request</h3>
                        <p class="mb-0">Fill out our service request form with your care needs and preferences.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <span class="display-4 fw-semibold text-primary">2</span>
                        </div>
                        <h3 class="h4 mb-2">Free Consultation</h3>
                        <p class="mb-0">We'll contact you to discuss your needs and create a personalized care plan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <span class="display-4 fw-semibold text-primary">3</span>
                        </div>
                        <h3 class="h4 mb-2">Begin Care</h3>
                        <p class="mb-0">We'll match you with the perfect caregiver and start providing the care you need.</p>
                    </div>
                </div>
            </div>
        </section>

<?php include_once "system/inc/footer.php"; ?>