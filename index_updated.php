<?php
    require_once "system/DatabaseConnector.php";
    $title = "Home Care Services";
    include "system/inc/head.php";
    include "system/inc/nav.php";
?>

<!-- Hero Section -->
<section class="position-relative min-vh-100 d-flex align-items-center bg-light text-dark py-5">
  <div class="container text-center">
    <h1 class="display-4 fw-bold mb-3">Compassionate Home Care You Can Trust</h1>
    <p class="lead mb-4">We provide personalized care and support services to help you or your loved one live safely, comfortably, and independently at home.</p>
    <a href="services.php" class="btn btn-primary btn-lg">Explore Our Services</a>
  </div>
</section>

<!-- About Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <img src="assets/img/home-care.jpg" class="img-fluid rounded-4 shadow" alt="Home Care">
      </div>
      <div class="col-md-6">
        <h2 class="fw-bold mb-3">Dedicated to Quality Care</h2>
        <p>We are a compassionate team committed to improving the quality of life for those we serve. Our professional caregivers provide support tailored to meet the unique needs of each client, ensuring comfort, safety, and dignity every day.</p>
        <a href="about.php" class="btn btn-outline-primary mt-3">Learn More</a>
      </div>
    </div>
  </div>
</section>

<!-- Services Preview -->
<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="fw-bold mb-4">Our Core Services</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Non-Medical Transportation</h5>
            <p class="card-text">Safe and reliable transport for local and out-of-town appointments.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Home Services</h5>
            <p class="card-text">Comprehensive support for daily living, meal preparation, and home upkeep.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Companionship</h5>
            <p class="card-text">Meaningful companionship that nurtures emotional well-being.</p>
          </div>
        </div>
      </div>
    </div>
    <a href="services.php" class="btn btn-primary mt-4">View All Services</a>
  </div>
</section>

<?php include "system/inc/footer.php"; ?>
