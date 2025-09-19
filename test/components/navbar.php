<?php
  // Derive site base path from asset path so links work from any directory
  if (!isset($asset_path)) {
    $asset_path = 'assets/';
  }
  $site_base_path = preg_replace('~assets/?$~', '', $asset_path);
  $current_full_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
?>
<section class="container-fluid" >
    <div class="nav1">
  <nav class="navbar navbar-expand-md navbar-light bg-transparent">
    <div class="container-fluid">
     
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end " id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_full_path, '/products/') !== false ? 'active' : ''; ?>" href="<?php echo $site_base_path; ?>products/">For rent</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo $site_base_path; ?>products/">For buyers</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_full_path, '/about/') !== false ? 'active' : ''; ?>" href="<?php echo $site_base_path; ?>about/">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_full_path, '/blog/') !== false ? 'active' : ''; ?>" href="<?php echo $site_base_path; ?>blog/">Blog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_full_path, '/contact/') !== false ? 'active' : ''; ?>" href="<?php echo $site_base_path; ?>contact/">Contact us</a>
          </li>
        </ul>
      </div>

       
    </div>
  </nav>

   <a class="navbar-brand" href="<?php echo $site_base_path; ?>index.php">
        <img src="<?php echo $asset_path; ?>images/logo.png" alt="Big Deal Ventures Logo"  />
      </a>
      </div>

  </section>
