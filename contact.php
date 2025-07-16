<?php
    $title = "Contact Us - Get in Touch | ";
    include_once "system/DatabaseConnector.php";
    include_once "system/inc/head.php";

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
        } else {
            // Here you would typically save to database and/or send email
            // For now, we'll just show a success message
            $_SESSION["flash_success"] = "Thank you for your message! We will get back to you soon.";
            redirect(PROOT . "contact.php");
        }
    }
?>

<!-- Main content -->
<main class="page-wrapper">
    <!-- Hero section -->
    <section class="bg-dark py-5">
        <div class="container pt-5 pb-5">
            <div class="row pt-md-4 pb-lg-5">
                <div class="col-lg-10 col-xl-8 text-center text-lg-start">
                    <h1 class="display-4 text-light pb-2 mb-4 mb-lg-5">Contact Us</h1>
                </div>
            </div>
        </div>
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
                                <div>info@homecareservices.com</div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <i class="ai-phone text-primary fs-5 me-2"></i>
                                <div>+1 (555) 123-4567</div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <i class="ai-map-pin text-primary fs-5 me-2"></i>
                                <div>123 Care Street<br>Anytown, ST 12345</div>
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
                                <i class="ai-twitter"></i>
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
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215355499376!2d-73.98776738459377!3d40.758980279326224!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6480299%3A0x55194ec5a1ae072e!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1650276825000!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>
</main>

<?php include_once "system/inc/footer.php"; ?>