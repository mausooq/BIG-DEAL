<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <link rel="icon" href="../assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/about.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
                
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>


<body class="about-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

 <section class="hero-banner">
    <div class="centered">
     
      <h1>About Us</h1>
       <div class="breadcrumbs">
        <a href="#">Home</a> > <span>About Us</span>
      </div>


      <div class="welcome-text">
        <h2 class="hero-content1">WELCOME TO</h2>
        <h2 class="gugi hero-content2">Real Estate</h2>
      </div>
    </div>
</section>


<div class="container">
    <div class="row">
        <div class="col-md-5" >
          <div class="about-img">
            <img src="../assets/images/prop/aboutimg.png" alt="about-img1"  class="img-upper">
            <img src="../assets/images/prop/aboutimg2.png" alt="about-img2"  class="img-lower">
        </div>
        </div>
        <div class="col-md-5 about-content">
          <h3>
            We Create Best Architect Around The World With Inspiration
          </h3>

          <p>
          Your dream home isn’t just a vision — it’s a reality waiting for you. As leaders in real estate,
           we specialize in crafting experiences where luxury meets comfort, and investment meets trust.
           Explore our handpicked collection of properties designed for modern lifestyles and lasting value.
          </p>
          <button class="btn-arrow">
            View More <span>→</span>
          </button>

   

        </div>
       
    </div>
            <div class="about-stats d-flex justify-content-between text-center ">
              <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label">Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
            </div>
</div>
<div class="container">
<div class="latest-works">
  <h2 class="latest-works-title">See Our <br><span class="latest-works-highlight gugi">LATEST<span style="color: red;"> WORKS</span> </span></h2>
  
</div>

<div class="image-grid " >
<div class="div1"><img src="../assets/images/prop/aboutimg5.png" alt=""> </div>
<div class="div2"> <img src="../assets/images/prop/aboutimg2.png" alt=""> </div>
<div class="div3"><img src="../assets/images/prop/aboutimg.png" alt="">  </div>
<div class="div4"> <img src="../assets/images/prop/aboutimg4.png" alt="">  </div>
<div class="div5"> <img src="../assets/images/prop/aboutimg3.png" alt=""> </div>
</div>

<div class="latest-works-description row">
  <p class=" col-md-10 desc">A modern villa that blends elegant design with spacious comfort, offering a perfect balance of luxury and convenience.</p>
  <img src="../assets/images/icon/next.svg" alt="" class="about-arrow col-md-2" >
</div>
</div>


    <!-- Testimonials -->
<?php include '../components/testimonial.php'; ?>


    
 <!-- contact  -->
<?php include '../components/letsconnect.php' ?>;


    
<?php include '../components/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js" defer></script>
</body>
</html>
