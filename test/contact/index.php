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
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
                
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>


<body class="contact-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

 <section class="hero-banner">
    <div class="centered">
     
      <h1>Contact Us</h1>
       <div class="breadcrumbs">
        <a href="#">Home</a> > <span>Contact Us</span>
      </div>
    </div>

    <div class=" container-fluid forms px-5">
        <div class="row ">
            <div class="col-md-7">
                <form action="" class="contact-form">
                    <div class=" row g-3">
                        <div class="col-md-6">
                            <input type="text" placeholder="First name" required class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <input type="text" placeholder="Last name" class="form-control" />
                        </div>
                    </div>
                    <div class="form-item">
                        <input type="email" placeholder="email@example.com" required class="form-control" />
                    </div>
                    <div class="form-item">
                        <textarea placeholder="Enter your question or message" rows="8" required class="form-control"></textarea>
                    </div>
                    <div class="form-item">
                        <button type="submit" class="btn btn-dark w-100">Submit</button>
                    </div>
                </form>
            </div>
            <div class="col-md-5  ">
                <div class="contact-map shadow rounded-4 overflow-hidden" style="height: 100%;">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox=74.8447%2C12.8815%2C74.8557%2C12.8865&amp;layer=mapnik"
                        style="border:0; width:100%; height:100%;">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
  </section>

  <div class=" container">
    <div class="row ">
      <div class="col-4 col-md-4  contact-info-item">
        <img src="../assets/images/icon/phone.svg" alt="phone">
        <div class="contact-info-text">
          <p class="contact-info-title">Call Now</p>
          <a href="tel:+91 8197458962" class="contact-info-detail">+91 8197458962</a>
        </div>
      </div>
      <div class="col-3 col-md-4  contact-info-item">
        <img src="../assets/images/icon/location.svg" alt="location" >
        <div class="contact-info-text">
          <p class="contact-info-title">Location</p>
          <p class="contact-info-detail">Kankanady Gate Building</p>
        </div>
      </div>
      <div class="col-5 col-md-4 contact-info-item">
        <img src="../assets/images/icon/mail.svg" alt="email">
        <div class="contact-info-text">
          <p class="contact-info-title">Email Now</p>
          <a href="mailto:munazzabegam11@gmail.com" class="contact-info-detail">munazzabegam11@gmail.com</a>
        </div>
      </div>
    </div>
  </div>


    <section class="contact-section ">
      <h2 class="blac2 col-md-6 sfpro">
        Connect With Our Experts
      </h2>
      <p class="blac2-sub poppins">
        Our team is here to help you find your perfect home.
      </p>
       <div class="btn-stack">
    <button class="btn-black"></button>
    <button class="btn-red">Contact Us Now</button>
  </div>
      <div class="d-flex align-items-center flex-column flex-md-row">
        <hr class="section-divider">
        <img class="house-img" src="../assets/images/prop/prop4.png" alt="Modern House" />
      </div>
    </section>




<?php include '../components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/scripts.js"></script>
</body>
</html>
