<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/contact.css" />
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
 <section class="hero-banner " style="
    margin-top: 16px;
" >
    <div class="centered">
     
      <h1>Contact Us</h1>
       <div class="breadcrumbs">
        <a href="../index.php">Home</a> > <span>Contact Us</span>
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
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.5891905270946!2d74.85795507572209!3d12.86978921710093!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba35b42801d3787%3A0x6382971e18a25583!2sBig%20Deal%20Ventures%20India!5e0!3m2!1sen!2sin!4v1759227103128!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    
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
          <a href="tel:+91 80888 55555" class="contact-info-detail">+91 80888 55555</a>
        </div>
      </div>
      <div class="col-3 col-md-4  contact-info-item">
        <img src="../assets/images/icon/location.svg" alt="location" >
        <div class="contact-info-text">
          <p class="contact-info-title">Location</p>
          <p class="contact-info-detail">First Floor, Gate Building, Kankanady Bypass Rd, Kankanady, Mangaluru, Karnataka 575002</p>
        </div>
      </div>
      <div class="col-5 col-md-4 contact-info-item">
        <img src="../assets/images/icon/mail.svg" alt="email">
        <div class="contact-info-text">
          <p class="contact-info-title">Email Now</p>
          <a href="mailto:office@bigdeal.property" class="contact-info-detail">office@bigdeal.property</a>
        </div>
      </div>
    </div>
  </div>
  <?php include '../components/search-banner.php'; ?>

    <?php include '../components/process.php';?> 
<!-- contact  -->
<?php include '../components/letsconnect.php'; ?>

<?php include '../components/faq.php'; ?>

  <!-- footer -->
<?php include '../components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/scripts.js"></script>
</body>
</html>
