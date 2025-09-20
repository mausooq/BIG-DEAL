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
      <div class="d-flex align-items-center flex-column flex-md-row">
        <hr class="section-divider">
        <img class="house-img" src="<?php echo $asset_path; ?>images/prop/prop4.png" alt="">
      </div>
</section>

<style>
  .contact-section .contact-btn-title{
    font-size: 1em;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 5em 0;
    margin: -20%;
    text-align: center;
    width: 100%;
  }
  .btn-stack {
    margin-left: 1%;
  }
  .btn-black {
    padding-left: 11.5em;
    display: flex;
    justify-content: end;
  }
  .btn-red {
    padding-left: -10em;
    width: 140px;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .btn-black .arrow-btn{
    display: flex;
    justify-content: center;
  }
  .btn-black .arrow-btn img {
    width:  1.4em;
    margin-right: 90%;
    border: none;
  }

  /* For small devices (phones, portrait) */
  @media (max-width: 480px) {
    .d-flex.align-items-center.flex-column.flex-md-row {
      justify-content: space-between;
      align-items: flex-end;
    }
    .blac2{
      margin: 0;
      width: 20em;;
    }
    .btn-stack {
      margin-left: 0em;
    }
    .btn-black {
      display: flex;
      justify-content: start;
      width: 100px;
      position: relative;
      margin: -8px;
      padding: 0 5em;
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
    .btn-black .arrow-btn {
      position: absolute;
      right: 10%;
      top: 50%;
      transform: translateY(-50%);
    }
    .btn-black .arrow-btn img {
      width: 1em;
      margin-right: 0;
    }
    .house-img {
      width: 250px;
      max-width: 250px;
      align-self: flex-end;
      margin-top: -4em;
      z-index: 2;
      position: relative;
    }
    .section-divider {
      margin: -1em 10px 0 10px;
      z-index: 1;
      position: relative;
      top: 8em;
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
      /* margin-right: 65%; */
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
</style>