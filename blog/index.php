<?php
require_once __DIR__ . '/../config/seo_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <?php echo SEOConfig::generateMetaTags('blog'); ?>
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

  <!-- Structured Data -->
  <?php 
  $breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Blog', 'url' => '/blog/']
  ];
  echo SEOConfig::generateBreadcrumbData($breadcrumbs);
  echo SEOConfig::generateStructuredData('blog');
  ?>
</head>
<body class="blog-page">
<?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>


       <section class="hero-banner">
    <div class="centered">
     
      <h1>Blog</h1>
       <div class="breadcrumbs">
        <a href="<?php echo $site_base_path; ?>index.php">Home</a> > <span>Blog</span>
      </div>


      <div class="welcome-text">
        <h2 class="hero-content1">LATEST BLOG FOR</h2>
        <h2 class="gugi hero-content2">Real Estate</h2>
      </div>
    </div>
</section>


<?php include '../components/blog-list.php'; ?>
<?php include '../components/search-banner.php'; ?>


<!-- contact  -->
<?php include '../components/letsconnect.php'; ?>  


  <!-- footer -->
<?php include '../components/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/scripts.js" defer></script>

</body>
</html>
