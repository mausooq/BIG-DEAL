<footer class="container-fluid">
  <style>
    /* Footer Base Styles */
    .footer p {
      font-weight: 500;
      font-size: 16px;
      line-height: 150%;
      vertical-align: middle;
      padding-left: 1.2em;
    }
    
    .footer .social-links {
      padding-left: 0.4em;
    }
    
    .footer-column {
      position: relative;
      padding-top: 30px;
      font-family: "inter", sans-serif;
    }
    
    .footer-column h3 {
      font-family: var(--font-heading);
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 30px;
      position: relative;
      display: inline-block;
    }
    
    .footer-links {
      list-style: none;
      color: #6a6666;
      padding-left: 0;
    }
    
    .footer-links li {
      margin-bottom: 30px;
    }
    
    .footer-links a {
      color: #6a6666;
      text-decoration: none;
      font-size: 16px;
      font-weight: 500;
      display: inline-block;
      position: relative;
    }
    
    .footer-links a::before {
      content: "→";
      position: absolute;
      left: -5px;
      opacity: 0;
      transition: var(--transition);
      color: var(--primary);
    }
    
    .footer-links a:hover {
      color: var(--lighter);
      padding-left: 1.5rem;
    }
    
    .footer-links a:hover::before {
      left: 0;
      opacity: 1;
    }
    
    /* Footer bottom section styling */
    .footer-bottom {
      padding: 15px 2rem; /* left/right spacing */
    }
    
    .copyright-text {
      font-size: 14px;
      color: #666;
      margin: 0;
    }
    
    .developer-text {
      font-size: 14px;
      color: #666;
      margin: 0;
      display: flex;
      align-items: center;
    }
    
    /* UrbanBiz link styling */
    .urbanbiz-link {
      font-weight: bold;
      color: var(--primary, #bf1d1d);
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .urbanbiz-link:hover {
      color: var(--lighter, #d63384);
      text-decoration: underline;
    }
    
    /* BrandWeave link styling */
    .brandweave-link {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .brandweave-link:hover {
      transform: scale(1.05);
    }
    
    .brandweave-logo {
      height: 20px;
      margin-left: 5px;
      transition: all 0.3s ease;
    }
    
    .social-icon {
      filter: grayscale(100%);
      padding: 10px;
    }
    
    #search-wrapper {
      display: flex;
      align-items: stretch;
      border-radius: 50px;
      background-color: #fff;
      overflow: hidden;
      max-width: 400px;
      margin: 10px 10px;
    }
    
    #search {
      border: none;
      width: 350px;
      font-size: 15px;
      padding: 10px 20px;
      color: #999;
      background-color: #ffe3e3;
    }
    
    #search:focus {
      outline: none;
    }
    
    #search-button {
      border: none;
      cursor: pointer;
      color: #fff;
      background-color: #bf1d1d;
      padding: 0px 10px;
    }
    
    /* Footer Logo Styles */
    .footer img[alt="footer logo"] {
      width: 8rem;
      height: auto;
      max-width: 200px;
      margin-bottom: 20px;
      transition: all 0.3s ease;
    }
    
    /* For small devices (phones, portrait) */
    @media (max-width: 480px) {
      footer.container-fluid {
        padding: 1em 0.75em;
      }
      .footer p,
      .footer-links a,
      .footer-links li {
        font-size: 14px;
        line-height: 1.5;
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
      .footer-column h3{
        font-size: 18px;
      }
      .footer img[alt="footer logo"] {
        width: 5rem;
        max-width: 120px;
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
        font-size: 16px; /* avoid iOS zoom */
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
      .footer-bottom {
        padding: 10px 0.75em; /* tighter padding on very small screens */
      }
      .copyright-text,
      .developer-text {
        font-size: 10px;
      }
      /* Reduce hover-shift on touch devices */
      .footer-links a { padding-left: 0; }
      .footer-links a:hover { padding-left: 0.5rem; }
      /* Long address wrapping */
      .footer-links li { word-break: break-word; }
    }

    /* For larger phones (landscape) */
    @media (max-width: 768px) {
      footer.container-fluid {
        padding: 1.25em 1em;
      }
      .footer img[alt="footer logo"]{
        width: 6rem;
        max-width: 150px;
        height: auto;
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
      .footer img[alt="footer logo"] {
        width: 7rem;
        max-width: 180px;
        height: auto;
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
      <?php
        // Ensure DB connection is available
        if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
          if (!function_exists('getMysqliConnection')) {
            $cfg = __DIR__ . '/../config/config.php';
            if (file_exists($cfg)) { require_once $cfg; }
          }
          if (function_exists('getMysqliConnection')) {
            try { $mysqli = getMysqliConnection(); } catch (Throwable $e) { $mysqli = null; }
          }
        }

        $socialLinks = [];
        if (isset($mysqli) && $mysqli instanceof mysqli) {
          try {
            if ($res = $mysqli->query("SELECT platform, url FROM social_links")) {
              while ($row = $res->fetch_assoc()) { $socialLinks[] = $row; }
              $res->free();
            }
          } catch (Throwable $e) { /* silently ignore in footer */ }
        }

        // Map platform name to icon URL (icons8) – adjust if you switch to local icons
        $iconMap = [
          'facebook' => 'https://img.icons8.com/material-rounded/25/facebook-new.png',
          'instagram' => 'https://img.icons8.com/material-rounded/25/instagram-new.png',
          'linkedin' => 'https://img.icons8.com/material-rounded/25/linkedin--v1.png',
          'youtube' => 'https://img.icons8.com/material-rounded/25/youtube-play.png',
          'twitter' => 'https://img.icons8.com/material-rounded/25/twitter.png',
          'x' => 'https://img.icons8.com/material-rounded/25/twitter.png'
        ];

        foreach ($socialLinks as $link) {
          $platform = strtolower(trim((string)($link['platform'] ?? '')));
          $url = trim((string)($link['url'] ?? ''));
          if ($url === '') { continue; }
          $icon = $iconMap[$platform] ?? 'https://img.icons8.com/material-rounded/25/link.png';
          echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
             . '<img src="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" class="social-icon"></a>';
        }
      ?>
    </div>
  </div>

   <div class=" col-md-2 footer-column">
                        <h3>Navigation</h3>
                        <ul class="footer-links">
                            <?php
                              // Reuse navbar path logic to build correct links from any directory
                              if (!isset($asset_path)) { $asset_path = 'assets/'; }
                              $site_base_path = preg_replace('~assets/?$~', '', $asset_path);
                            ?>
                            <li><a href="<?php echo $site_base_path; ?>about/">About</a></li>
                            <li><a href="<?php echo $site_base_path; ?>services/">Services</a></li>
                            <li><a href="<?php echo $site_base_path; ?>blog/">Blog</a></li>
                            <li><a href="<?php echo $site_base_path; ?>contact/">Contact us</a></li>
                        </ul>
      </div>

      <div class=" col-md-3 footer-column">
                        <h3>Contact</h3>
                        <ul class="footer-links">
                            <li>+91 80888 55555</li>
                            <li>office@bigdeal.property</li>
                            <li>First Floor, Gate Building, Kankanady Bypass Rd, Kankanady, Mangaluru, Karnataka 575002
                            </li>  
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
      <div class="col-md-12 d-flex justify-content-between align-items-center footer-bottom">
      <p class="copyright-text">&copy;<script>document.write(new Date().getFullYear());</script> Big Deal PROPERTY - Subsidiary of <a href="https://www.urbanbiz.in/" target="_blank" rel="noopener" class="urbanbiz-link">UrbanBiz Ventures Pvt Ltd</a></p>
 <p class="developer-text">   Developed by <a href="https://thebrandweave.com" target="_blank" rel="noopener" class="brandweave-link"><img src="<?php echo $asset_path; ?>images/brandweave.png" alt="The Brand Weave" class="brandweave-logo"></a></p>
          </div>

</footer>