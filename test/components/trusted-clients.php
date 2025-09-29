<?php
// Trusted Clients Component
// This component displays a continuous flowing carousel of client logos
?>

<!-- Owl Carousel CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

<!-- jQuery and Owl Carousel JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<div class="trusted-clients-component">
<section class="tc-clients-section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="tc-sec-heading">
          <h2>Our Clients</h2>
          <p class="tc-subtitle">Trusted by leading companies worldwide</p>
        </div>
      </div>
    </div>
    <div class="row" style="margin-top: 2em;">
      <div class="col-md-12">
        <div class="owl-carousel owl-theme tc-clients-carousel">
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
          <div class="item tc-logo-box">
            <img alt="client logo" class="tc-logo-img" src="<?php echo $asset_path; ?>images/logo.png">
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</div>

<style>
/* Trusted Clients Component Styles - Unique Classes */
.trusted-clients-component .tc-clients-section {
  padding: 3em;
  /* background: #f8f9fa; */
}

.trusted-clients-component .tc-sec-heading {
  /* margin-bottom: 2em; */
  letter-spacing: 0.5px;
}

.trusted-clients-component .tc-sec-heading h2 {
  font-family: 'DM Sans', sans-serif;
  font-size: 3em;
  font-weight: 700;
  color: #222;
  display: flex;
  justify-content: center;
}

.trusted-clients-component .tc-subtitle {
  font-family: 'DM Sans', sans-serif;
  font-size: 1.125em;
  color: #666;
  margin: 0;
  font-weight: 400;
  display: flex;
  justify-content: center;
}

.trusted-clients-component .tc-logo-box {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 10.5em;
  width: 17em;
  transition: transform 0.5s ease-in;
  margin: 0 0.625em;
  padding: 1.25em;
}

.trusted-clients-component .tc-logo-box img {
  max-width: 100%;
  max-height: 8em;
  width: auto;
  height: auto;
  object-fit: contain;
  filter: grayscale(100%);
  transition: all 0.3s ease;
}

.trusted-clients-component .tc-logo-box:hover {
  transform: scale(1.1);
}

.trusted-clients-component .tc-logo-box:hover img {
  filter: grayscale(0%);
}

/* Responsive Design */
@media (max-width: 768px) {
  .trusted-clients-component .tc-sec-heading {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.5em;
  }
  .trusted-clients-component .tc-sec-heading h2 {
    font-size: 2.25em;
  }
  
  .trusted-clients-component .tc-subtitle {
    font-size: 1em;
  }
  
  .trusted-clients-component .tc-clients-section {
    padding: 2.5em 0;
  }
  
  .trusted-clients-component .tc-logo-box {
    width: 13.5em;
    height: 8.5em;
  }
  
  .trusted-clients-component .tc-logo-box img {
    max-height: 6em;
  }
}

@media (max-width: 576px) {
  .trusted-clients-component .tc-sec-heading h2 {
    font-size: 1.75em;
  }
  
  .trusted-clients-component .tc-subtitle {
    font-size: 0.875em;
  }
  
  .trusted-clients-component .tc-clients-section {
    padding: 1.5em 0;
  }
  
  .trusted-clients-component .tc-logo-box {
    width: 12em;
    height: 7em;
  }
  
  .trusted-clients-component .tc-logo-box img {
    max-height: 5em;
  }
}
</style>

<script>
$(document).ready(function() {
  $(".tc-clients-carousel").owlCarousel({
    autoplay: true,
    loop: true,
    margin: 20,
    dots: false,
    nav: false,
    slideTransition: "linear",
    autoplayTimeout: 3000,
    autoplayHoverPause: true,
    autoplaySpeed: 3000,
    center: false,
    responsive: {
      0: {
        items: 2,
        margin: 10
      },
      500: {
        items: 3,
        margin: 15
      },
      768: {
        items: 4,
        margin: 20
      },
      1024: {
        items: 5,
        margin: 20
      },
      1200: {
        items: 6,
        margin: 20
      }
    }
  });
});
</script>
