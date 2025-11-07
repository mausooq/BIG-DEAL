<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Our Services - Big Deal Ventures</title>
  <!-- Favicon for Google Search Console - must use root /favicon.ico -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
  <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/about.css" />
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/services.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
  <link href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons@3.3.1/css/all/all.min.css" rel="stylesheet">
                
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<style>
  @media (max-width: 480px) {
    .welcome-text .services-hero-content2 {
      font-size: 1.4rem !important;
    }
    .about-page .about-img .img-upper {
    width: 275px;
  }

  .about-page .about-img .img-lower {
    width: 375px;
    height: 50%;
    margin-left: 4rem;
    margin-top: 9rem;
  }
  
  }
</style>
</head>


<body class="about-page">
 
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

 <section class="hero-banner">
    <div class="centered">
     
      <h1>Our Services</h1>
       <div class="breadcrumbs">
        <a href="../index.php">Home</a> > <span>Our Services</span>
      </div>


      <div class="welcome-text">
        <h2 class="hero-content1 about-hero-content1">COMPREHENSIVE</h2>
        <h2 class="gugi hero-content2 about-hero-content2 services-hero-content2">Property Services</h2>
      </div>
    </div>
</section>


<div class="container">
    <div class="row">
        <div class="col-md-5" >
          <div class="about-img">
            <img src="../assets/images/sp3.jpg" alt="services-img1"  class="img-upper">
            <img src="../assets/images/sp1.jpg" alt="services-img2"  class="img-lower">
        </div>
        </div>
        <div class="col-md-5 about-content">
          <h3>
            Complete Property Solutions From Search to Success
          </h3>

          <p style="text-align: justify;">
          From initial property search to final handover, we provide end-to-end real estate services. 
          Our tech-driven approach combines AI-powered recommendations with expert guidance, ensuring 
          you find the perfect property and secure the best deal possible.
          </p>
          <a href="#services-section" class="view-all-btn">
            Explore Services <span>→</span>
          </a>

   

        </div>
       
    </div>
</div>


<?php include '../components/why-choose-us.php'; ?> 

<?php include '../components/trusted-clients.php'; ?>


<!-- Services Section -->
<section id="services-section">
    <?php include '../components/service.php'; ?>
</section>

<!-- Other Services (cards) -->
<section class="other-services-section py-5">
  <div class="container other-services-wrap">
    <div class="property-title-section text-left">
      <div class="property-title">Our Additional Services</div>
      <div class="property-subtitle">Enhancing every detail with design, planning, and execution</div>
    </div>
    <div class="row g-4 align-items-stretch">
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/planning.png" alt="Blueprint"></span>
          <div class="svc-title">Perfect Planning</div>
          <div class="svc-desc">Smart property management with tenant screening, rent collection, and seamless upkeep to ensure maximum returns.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/house-design.png" alt="Interior Design"></span>
          <div class="svc-title">Professional Design</div>
          <div class="svc-desc">From stylish renovations to smart automation, we create modern layouts that blend comfort with elegance.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/living-room.png" alt="Interior"></span>
          <div class="svc-title">Premium Interiors</div>
          <div class="svc-desc">Turnkey interior and furnishing solutions delivered by trusted experts — tailored to your unique taste and lifestyle.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/sustainable.png" alt="Furniture"></span>
          <div class="svc-title">Sustainable Furniture</div>
          <div class="svc-desc">Eco-friendly and durable furniture designs for homes, offices, and commercial spaces that inspire smarter living.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/trade.png" alt="Decoration"></span>
          <div class="svc-title">Complete Decoration</div>
          <div class="svc-desc">Expert styling and décor solutions — from concept to execution — that transform spaces into functional masterpieces.</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="svc-card">
          <span class="svc-icon"><img src="../assets/images/icon/exterior.png" alt="Exterior"></span>
          <div class="svc-title">Exterior Design</div>
          <div class="svc-desc">Creative outdoor designs and landscaping ideas that add elegance, value, and curb appeal to your property.</div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- Interior Designs -->
<?php include '../components/our-works.php'; ?>

<!-- Testimonials -->
<?php include '../components/testimonial.php'; ?>

    
 <!-- contact  -->
<?php include '../components/letsconnect.php' ?>;


    
<?php include '../components/footer.php'; ?>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  });
 </script>
</body>
</html>
