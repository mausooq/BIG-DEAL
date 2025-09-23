<footer class="container-fluid">
  <style>
    /* For small devices (phones, portrait) */
    @media (max-width: 480px) {
      footer.container-fluid {
        padding: 1em 0.75em;
      }
      .row {
        margin: 0;
      }
      .footer, .footer-column {
        width: 100% !important;
        max-width: 100%;
        flex: 0 0 100%;
        text-align: left;
        margin-bottom: 1em;
      }
      .footer img {
        max-width: 8.75em;
        height: auto;
      }
      .social-links {
        display: flex;
        justify-content: flex-start;
        gap: 0.75em;
        margin-top: 0.5em;
      }
      .social-icon {
        width: 2.5em;
        height: 2.5em;
      }
      .footer-links {
        list-style: none;
        padding-left: 0;
      }
      .footer-links li {
        margin: 6px 0;
      }
      #search-wrapper {
        display: flex;
        width: 100%;
        max-width: 100%;
        justify-content: flex-start;
      }
      #search {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
        padding: 0.625em 0.75em;
        border-radius: 0.375em;
        border: none;
      }
      #search-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.625em 0.75em;
        border: none;
      }
      #search-button img {
        width: 1.125em;
        height: 1.125em;
      }
      .section-divider {
        margin: 1em 0;
      }
      .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        gap: 0.5em;
        text-align: left;
        align-items: flex-start;
      }
    }

    /* For larger phones (landscape) */
    @media (max-width: 768px) {
      footer.container-fluid {
        padding: 1.25em 1em;
      }
      .footer img{
        margin-left: -1.5em;
      }
      .footer p{
        padding-left: 0;
      }
      .footer, .footer-column {
        text-align: left;
      }
      .footer-column h3{
        margin-bottom: 0.5em;
      }
      .col-md-3 {
        padding: 0 0.625em;
      }
      .social-links {
        justify-content: flex-start;
        gap: 1.125em;
      }
      .social-icon { width: 3em; height: 3em; }
      #search-wrapper {
        max-width: 32.5em;
        margin: 0.8em 0;
      }
      #search {
        padding: 0.75em;
      }
      .d-flex.justify-content-between.align-items-center {
        flex-wrap: wrap;
        gap: 0.625em 1em;
        justify-content: flex-start !important;
        text-align: left;
      }
    }

    /* For tablets */
    @media (max-width: 1024px) {
      footer.container-fluid {
        padding: 1.5em 1.25em;
      }
      .footer img {
        max-width: 10em;
      }
      .footer-links li {
        margin: 0.5em 0;
      }
      #search-wrapper {
        max-width: 36.25em;
      }
      .section-divider {
        margin: 1.125em 0;
      }
    }
  </style>
  <hr class="section-divider">
  <div class="row ">

    <div class="col-md-4 footer">
    <img src="<?php echo $asset_path; ?>images/logo.png" alt="footer logo">
  <p>Big Deal Real Estate is your trusted partner in buying, selling, and investing in properties. 
    We focus on transparency, professionalism, and making your real estate journey simple and hassle-free.</p>

    <div class="social-links">
      <a href="https://facebook.com"><img src="https://img.icons8.com/material-rounded/25/facebook-new.png" class="social-icon"></a>
      <a href="https://instagram.com"><img src="https://img.icons8.com/material-rounded/25/instagram-new.png" class="social-icon"></a>
      <a href="https://linkedin.com"><img src="https://img.icons8.com/material-rounded/25/linkedin--v1.png" class="social-icon"></a>
      <a href="https://youtube.com"><img src="https://img.icons8.com/material-rounded/25/youtube-play.png" class="social-icon"></a>
    </div>
  </div>

   <div class=" col-md-2 footer-column">
                        <h3>Navigation</h3>
                        <ul class="footer-links">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Home</a></li>
                            
                        </ul>
      </div>

      <div class=" col-md-3 footer-column">
                        <h3>Contact</h3>
                        <ul class="footer-links">
                            <li>+918197458962</li>
                            <li>info@bigdeal.com</li>
                            <li>Kankanady Gate building, Mangalore</li>  
                        </ul>
      </div>

      <div class=" col-md-3 footer-column">
          <h3>Get the latest information</h3>
           <div id="search-wrapper">
            <input type="email" id="search" placeholder="Email address">
            <button id="search-button"><img src="<?php echo $asset_path; ?>images/icon/sendw.png" alt="send"></button>
          </div>
      </div>

    <hr class="section-divider">
      <div class="col-md-12 d-flex justify-content-between align-items-center">
      <p >&copy;<script>document.write(new Date().getFullYear());</script> Big Deal Real Estate </p>
 <p >   Developed by </p>
          </div>

</footer>