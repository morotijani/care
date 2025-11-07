<?php
    $title = "About Us - ";
    require_once "system/DatabaseConnector.php";
    include "system/inc/head.php";
    include "system/inc/nav.php";
?>

<section class="bg-dark text-white py-5 text-center">
  <div class="container">
    <h1 class="display-5 fw-bold">About Our Home Care Agency</h1>
    <p class="lead">Delivering care with compassion, respect, and professionalism.</p>
  </div>
</section>

<section class="container py-5">
  <div class="row align-items-center">
    <div class="col-md-6">
      <h2 class="fw-bold mb-3">Who We Are</h2>
      <p>We are a trusted provider of home care and personal support services dedicated to enhancing the independence and quality of life of our clients. Our mission is to make every day easier, safer, and more comfortable for those we care for.</p>
      <p>With a team of trained caregivers and healthcare professionals, we focus on reliability, safety, and personalized care that meets each client's unique needs.</p>
    </div>
    <div class="col-md-6">
      <img src="assets/img/about-us.jpg" alt="About Home Care" class="img-fluid rounded-4 shadow">
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="fw-bold mb-4">Our Core Values</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Compassion</h5>
            <p class="card-text">We treat every client with empathy, dignity, and kindness.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Reliability</h5>
            <p class="card-text">Our clients can depend on us for consistent, quality care.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title">Excellence</h5>
            <p class="card-text">We uphold the highest standards in service and professionalism.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include "system/inc/footer.php"; ?>
