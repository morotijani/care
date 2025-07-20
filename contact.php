<?php
    $title = "Contact Us - Get in Touch | ";
    include_once "system/DatabaseConnector.php";
    include_once "system/inc/head.php";
    include_once "system/inc/nav.php";

    // Process contact form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["contact_submit"])) {
        $name = sanitize($_POST["name"]);
        $email = sanitize($_POST["email"]);
        $phone = sanitize($_POST["phone"]);
        $message = sanitize($_POST["message"]);
        $subject = sanitize($_POST["subject"]);
        
        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            $_SESSION["flash_error"] = "Please fill in all required fields.";
            redirect(PROOT . "contact");

        } else {
            // Here you would typically save to database and/or send email
            $sql = "
                INSERT INTO `care_contact_messages`(`message_id`, `message_name`, `message_email`, `message_phone`, `message_subject`, `message_message`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $statement = $dbConnection->prepare($sql);
            $result = $statement->execute([guidv4(), $name, $email, $phone, $subject, $message]);
            if ($result) {
                // For now, we'll just show a success message
                $_SESSION["flash_success"] = "Thank you for your message! We will get back to you soon.";
                redirect(PROOT . "contact");
            } else {
                $_SESSION["flash_error"] = "Something went wrong...please try again !";
                redirect(PROOT . "contact");
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
                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                </ol>
            </nav>

            <!-- Page title -->
            <h1 class="display-2 pb-2 mb-sm-4 mb-lg-5">Contact Us</h1>
        </section>

        <!-- Contact form + info -->
        <section class="container py-5 my-md-2 my-lg-4">
            <div class="row">
                <!-- Contact form -->
                <div class="col-lg-7 mb-5 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="h1 mb-4">Get in Touch</h2>
                            <p class="fs-lg mb-4">Have questions about our services? Want to request care for yourself or a loved one? Fill out the form below, and we'll get back to you as soon as possible.</p>
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
                                        <label for="phone" class="form-label">Phone number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control form-control-lg">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="subject" class="form-label">Subject</label>
                                        <select id="subject" name="subject" class="form-select form-select-lg">
                                            <option value="General Inquiry">General Inquiry</option>
                                            <option value="Service Request">Service Request</option>
                                            <option value="Employment">Employment</option>
                                            <option value="Feedback">Feedback</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                        <textarea id="message" name="message" class="form-control form-control-lg" rows="4" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="contact_submit" class="btn btn-primary btn-lg shadow-primary">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Contact info -->
                <div class="col-lg-5 col-xl-4 offset-xl-1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="h4 mb-4">Contact Information</h2>
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex align-items-center mb-3">
                                    <i class="ai-mail text-primary fs-5 me-2"></i>
                                    <div>info@carethatfeelslikehome.com</div>
                                </li>
                                <li class="d-flex align-items-center mb-3">
                                    <i class="ai-phone text-primary fs-5 me-2"></i>
                                    <div>+1 (701) 934-4903</div>
                                </li>
                                <li class="d-flex align-items-center mb-3">
                                    <i class="ai-map-pin text-primary fs-5 me-2"></i>
                                    <div>121 E Arikara Ave,<br>Bismarck, ND Apt 307</div>
                                </li>
                            </ul>
                            <h3 class="h4 mb-4">Hours of Operation</h3>
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex align-items-center mb-2">
                                    <div><span class="fw-semibold">Monday - Friday:</span> 8:00 AM - 8:00 PM</div>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <div><span class="fw-semibold">Saturday:</span> 9:00 AM - 5:00 PM</div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div><span class="fw-semibold">Sunday:</span> Closed (Emergency services available)</div>
                                </li>
                            </ul>
                            <h3 class="h4 mb-4">Follow Us</h3>
                            <div class="d-flex">
                                <a href="#" class="btn btn-icon btn-outline-secondary btn-facebook me-2">
                                    <i class="ai-facebook"></i>
                                </a>
                                <a href="#" class="btn btn-icon btn-outline-secondary btn-twitter me-2">
                                    <i class="ai-x"></i>
                                </a>
                                <a href="#" class="btn btn-icon btn-outline-secondary btn-instagram me-2">
                                    <i class="ai-instagram"></i>
                                </a>
                                <a href="#" class="btn btn-icon btn-outline-secondary btn-linkedin">
                                    <i class="ai-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map -->
        <section class="container pb-5">
            <div class="rounded-3 overflow-hidden">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2730.2840370165422!2d-100.79145802260915!3d46.81840757112848!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x52d7830bde5f07c1%3A0x5e5ed3892684ddde!2s121%20E%20Arikara%20Ave%20%23307%2C%20Bismarck%2C%20ND%2058501!5e0!3m2!1sen!2sus!4v1753029400218!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>

<?php include_once "system/inc/footer.php"; ?>