<!-- Connect With Our Experts Section -->
<section class="contact-section">
  <h2 class="blac2 col-md-6">
    Connect With Our Experts
  </h2>
  <p class="blac2-sub">
    Our team is here to help you find your perfect home.
  </p>
   <div class="btn-stack d-flex justify-content-start">
    <a href="#" class="text-decoration-none">
    <div class="btn-black d-flex align-items-center">
    <div class="btn-red">
      <h1 class="contact-btn-title">
        Contact Us Now
      </h1>
    </div>
    <div class="arrow-btn">
      <img src="<?php echo $asset_path; ?>images/icon/rightarrow.svg" alt="">
      </div>
    </div>
  </a>
  </div>
      <div class="d-flex align-items-center">
        <hr class="section-divider">
        <img class="house-img" src="<?php echo $asset_path; ?>images/prop/prop4.png" alt="">
      </div>
</section>
<style>
  .btn-stack {
    margin-left: 1%;
  }
  .btn-black {
    width: 200px;
    display: flex;
    justify-content: end;
  }
  .btn-red {
    width: 100px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 8em;
  }
  .btn-red .contact-btn-title{
    font-size: 0.8em;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 5em 0;
    margin: -20%;
    text-align: center;
    width: 100%;
  } 
  .btn-black .arrow-btn{
    display: flex;
    justify-content: center;
  }
  .btn-black .arrow-btn img {
    width: 1.4em;
    margin-right: 90%;
    border: none;
  }
  
  .section-divider {
    flex: 1;
    margin: 0 20px;
    border: none;
    border-top: 1px solid #ddd;
  }
  
  .house-img {
    width: 120px;
    height: auto;
    max-width: 120px;
  }

  /* For small devices (phones, portrait) */
  @media (max-width: 480px) {
    .blac2{
      margin: 0;
      width: 20em;;
    }
    .btn-stack {
      margin-left: 1em;
    }
    .btn-black {
      display: flex;
      justify-content: start;
      width: 150px;
    }
    .btn-red {
      width: 120px;
      padding: 0 2em;
      margin: 0;
    }
    .btn-red .contact-btn-title{
      font-size: 0.7em !important;
      padding: 2.5em 0;
      margin: -15%;
      white-space: nowrap;
    }
    .btn-black .arrow-btn{
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .btn-black .arrow-btn img {
      width: 1em;
      margin-right: -50%;
    }
    .house-img {
      width: 250px;
      max-width: 250px;
      margin: -2em 0;
    }
    .section-divider {
      margin: 0 10px;
    }
    .contact-section h2 {
      font-size: 1.2rem;
    }
    .contact-section p {
      font-size: 0.8rem;
      margin-bottom: 15px;
    }
  }

  /* For larger phones (landscape) */
  @media (max-width: 768px) and (min-width: 481px) {
    .btn-stack {
      margin-left: 0;
    }
    .btn-black {
      display: flex;
      justify-content: start;
      width: 100%;
    }
    .btn-red {
      width: 130px;
      padding: 0 3em;
    }
    .btn-red .contact-btn-title{
      font-size: 0.75em !important;
      padding: 3em 0;
      margin: -18%;
      white-space: nowrap;
    }
    .btn-black .arrow-btn{
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .btn-black .arrow-btn img {
      width: 1.1em;
      margin-right: 65%;
    }
    .house-img {
      width: 100px;
      max-width: 100px;
    }
    .section-divider {
      margin: 0 15px;
    }
    .contact-section h2 {
      font-size: 1.4rem;
    }
    .contact-section p {
      font-size: 0.85rem;
      margin-bottom: 20px;
    }
    .section-divider {
      margin: 15px 0;
    }
  }

  /* For tablets */
  @media (max-width: 1024px) and (min-width: 769px) {
    .btn-stack {
      margin-left: 1%;
    }
    .btn-black {
      display: flex;
      justify-content: end;
    }
    .btn-red {
      width: 100px;
      padding: 0 4em;
    }
    .btn-red .contact-btn-title{
      font-size: 0.8em !important;
      padding: 4em 0;
      margin: -20%;
    }
    .btn-black .arrow-btn{
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .btn-black .arrow-btn img {
      width: 1.3em;
      margin-right: 85%;
    }
    .house-img {
      width: 110px;
      max-width: 110px;
    }
    .section-divider {
      margin: 0 18px;
    }
    .contact-section h2 {
      font-size: 1.8rem;
    }
    .contact-section p {
      font-size: 0.95rem;
    }
  }

  /* For laptops and desktops */
  @media (max-width: 1280px) and (min-width: 1025px) {
    /* Using default desktop styles - no changes needed */
  }

  /* For large screens */
  @media (min-width: 1440px) {
    /* Using default desktop styles - enhanced for large screens */
    .btn-red {
      padding: 0 6em;
    }
    .btn-red .contact-btn-title{
      font-size: 0.85em;
      padding: 5.5em 0;
    }
    .btn-black .arrow-btn img {
      width: 1.5em;
    }
  }
</style>