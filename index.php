<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/seo_config.php';
$mysqli = getMysqliConnection();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <?php echo SEOConfig::generateMetaTags('home'); ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonhets.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  
  <style>
   @media (max-width: 480px) {
    .location-grid-mobile{
      position: relative;
      right: 2em;
      gap: 1rem !important;
    } 
   }
.feature-stats-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 3rem;
    padding: 0.7rem;
    gap: 16px;
    border-radius: 10px;
    background-color:rgb(37, 36, 36);
    color: #fff;
    flex-wrap: wrap;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 0 0 auto;
}

.feature-icon-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid rgb(212, 211, 211);
    flex-shrink: 0;
}

.feature-icon {
    width: 10px;
    height: 10px;
    color: rgb(212, 211, 211);
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.feature-text {
    font-size: 0.9rem;
    white-space: nowrap;
    letter-spacing: 0.3px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 200;
    line-height: 100%;
    vertical-align: middle;
    flex-shrink: 1;
}

@media (max-width: 1250px) {
    .feature-stats-container {
      width: 60vw;
      margin-top: 0.5rem;
    }
  }
/* Tablet styles (768px - 1024px) */
@media (max-width: 1024px) {
    .feature-stats-container {
      display:none;
      
    }
  }

  /* ===== MODERN SEARCH SECTION ===== */
  .modern-search-section {
    background: transparent;
    padding: 20px 0;
    margin-bottom: 40px;
  }

  .modern-nav-tabs {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    padding: 0 15px;
  }

  .modern-nav-tabs button {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    font-family: 'DM Sans', sans-serif;
  }

  .modern-nav-tabs button:hover,
  .modern-nav-tabs button.active {
    color: #fff;
  }

  .modern-nav-tabs button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 20px;
    right: 20px;
    height: 2px;
    background: #cc1a1a;
    border-radius: 2px;
  }

  .modern-search-bar-wrapper {
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    z-index: 10;
  }

  .modern-search-bar {
    background: #ffffff;
    border-radius: 50px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
  }

  .modern-search-bar:focus-within {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
  }

  .modern-search-bar select {
    flex: 1;
    border: none;
    outline: none;
    font-size: 16px;
    color: #333;
    background: transparent;
    font-family: 'DM Sans', sans-serif;
    font-weight: 400;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    padding-right: 30px;
  }

  .modern-search-bar select:focus {
    outline: none;
  }

  /* ===== MODERN CAROUSEL STYLES ===== */
  .modern-carousel-wrapper {
    position: relative;
    width: 100%;
    margin: 40px 0;
    padding: 0 15px;
  }

  .modern-carousel-container {
    position: relative;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    height: 650px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modern-carousel-slide {
    position: absolute;
    width: 480px;
    max-width: 90%;
    height: 600px;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
    opacity: 0;
    z-index: 1;
    pointer-events: none;
    cursor: pointer;
  }

  .modern-carousel-slide.active {
    transform: translateX(0) scale(1);
    opacity: 1;
    z-index: 3;
    pointer-events: auto;
  }

  .modern-carousel-slide.next {
    transform: translateX(200px) scale(0.85);
    opacity: 0.7;
    z-index: 2;
    pointer-events: auto;
    filter: blur(2px);
  }

  .modern-carousel-slide.prev {
    transform: translateX(-200px) scale(0.85);
    opacity: 0.7;
    z-index: 2;
    pointer-events: auto;
    filter: blur(2px);
  }

  .modern-property-card {
    width: 100%;
    height: 100%;
    border-radius: 20px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .modern-carousel-slide.active .modern-property-card {
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .modern-property-image-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
  }

  .modern-property-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }

  .modern-carousel-slide:hover .modern-property-image {
    transform: scale(1.05);
  }

  .modern-property-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.7) 70%, transparent 100%);
    padding: 30px 25px 25px;
    color: #fff;
    border-radius: 0 0 20px 20px;
  }

  .modern-info-row {
    display: flex;
    gap: 20px;
    margin-bottom: 12px;
    flex-wrap: wrap;
  }

  .modern-info-row:last-of-type {
    margin-bottom: 15px;
  }

  .modern-info-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 300;
    color: rgba(255, 255, 255, 0.9);
    font-family: 'DM Sans', sans-serif;
  }

  .modern-info-icon {
    width: 16px;
    height: 16px;
    filter: brightness(0) invert(1);
    opacity: 0.9;
  }

  .modern-info-text {
    white-space: nowrap;
  }

  .modern-property-meta {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
  }

  .modern-price {
    font-size: 24px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 5px;
    font-family: 'DM Sans', sans-serif;
  }

  .modern-location {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 300;
    font-family: 'DM Sans', sans-serif;
  }

  /* Modern Navigation Dots */
  .modern-carousel-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    padding: 10px 0;
  }

  .modern-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
    outline: none;
  }

  .modern-dot:hover {
    background: rgba(255, 255, 255, 0.6);
    transform: scale(1.2);
  }

  .modern-dot.active {
    background: #cc1a1a;
    width: 12px;
    height: 12px;
    box-shadow: 0 0 10px rgba(204, 26, 26, 0.5);
  }

  /* ===== COMPACT FEATURED SECTION - NEW MINIMAL DESIGN ===== */
  .compact-featured-section {
    background: #f8f9fa;
    padding: 30px 15px;
    margin: 20px 0;
  }

  /* ===== COMPACT SEARCH BAR ===== */
  .compact-search-wrapper {
    max-width: 800px;
    margin: 0 auto 25px;
  }

  .compact-nav-buttons {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-bottom: 15px;
    flex-wrap: wrap;
  }

  .compact-nav-btn {
    background:rgb(26, 26, 26);
    border: 1px solid #e0e0e0;
    color:rgb(234, 235, 229);
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'DM Sans', sans-serif;
  }

  .compact-nav-btn:hover {
    background:rgb(243, 243, 243);
    border-color: #d0d0d0;
    color: #333;
    transform: translateY(-1px);
  }

  .compact-nav-btn.active {
    background: #cc1a1a;
    border-color: #cc1a1a;
    color: #fff;
    box-shadow: 0 2px 8px rgba(204, 26, 26, 0.3);
  }

  .compact-nav-btn.active:hover {
    background: #b31717;
    border-color: #b31717;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(204, 26, 26, 0.4);
  }

  .compact-search-bar {
    background: #fff;
    border-radius: 50px;
    padding: 14px 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    position: relative;
  }

  .compact-search-bar:focus-within {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    border-color: #cc1a1a;
  }

  .compact-search-bar select {
    width: 100%;
    border: none;
    outline: none;
    font-size: 15px;
    color: #333;
    background: transparent;
    font-family: 'DM Sans', sans-serif;
    font-weight: 400;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="none" height="20" stroke="%23999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" width="20"><polyline points="6 9 12 15 18 9"></polyline></svg>');
    background-repeat: no-repeat;
    background-position: right 0 center;
    padding-right: 32px;
    padding-left: 0;
  }

  .compact-search-bar select:focus {
    outline: none;
  }

  .compact-search-bar select option {
    padding: 12px 20px;
    font-size: 15px;
    color: #333;
    background: #fff;
  }

  .compact-search-bar select option:hover,
  .compact-search-bar select option:checked {
    background: #f5f5f5;
    color: #cc1a1a;
  }

  /* ===== COMPACT TITLE ===== */
  .compact-title-section {
    text-align: center;
    margin-bottom: 25px;
  }

  .compact-title {
    font-size: 32px;
    font-weight: 400;
    color: #333;
    margin: 0;
    font-family: 'Gugi', sans-serif;
  }
  
  .compact-title.gugi {
    font-family: 'Gugi', sans-serif;
    font-size: 32px;
    font-weight: 400;
    letter-spacing: 0.5px;
  }

  /* ===== COMPACT HORIZONTAL CAROUSEL ===== */
  .compact-carousel-section {
    max-width: 1200px;
    margin: 0 auto;
    overflow: hidden;
    position: relative;
  }

  .compact-carousel-track {
    display: flex;
    gap: 20px;
    width: fit-content;
    will-change: transform;
  }

  .compact-property-card {
    flex: 0 0 auto;
    width: 320px;
    min-width: 280px;
    max-width: 380px;
  }

  .compact-property-card:hover {
    transform: translateY(-4px);
  }

  .compact-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
  }

  .compact-card-image {
    position: relative;
    width: 100%;
    height: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  }

  .compact-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }

  .compact-property-card:hover .compact-card-image img {
    transform: scale(1.05);
  }

  .compact-card-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
    padding: 15px;
    color: #fff;
  }

  .compact-card-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .compact-info-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .compact-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
  }

  .compact-badge svg {
    width: 12px;
    height: 12px;
    stroke: currentColor;
  }

  .compact-card-price {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
  }

  .compact-card-location {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
    font-family: 'DM Sans', sans-serif;
  }

  /* Compact Navigation Dots */
  .compact-carousel-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
    padding: 10px 0;
  }

  .compact-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: none;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
    outline: none;
  }

  .compact-dot:hover {
    background: #cc1a1a;
    transform: scale(1.2);
  }

  .compact-dot.active {
    background: #cc1a1a;
    width: 24px;
    border-radius: 4px;
  }

  /* Responsive Styles */
  @media (max-width: 1024px) {
    .modern-carousel-container {
      height: 550px;
    }

    .modern-carousel-slide {
      width: 420px;
      height: 500px;
    }

    .modern-carousel-slide.next {
      transform: translateX(150px) scale(0.8);
    }

    .modern-carousel-slide.prev {
      transform: translateX(-150px) scale(0.8);
    }

    .compact-property-card {
      flex: 0 0 calc(50% - 10px);
      min-width: 250px;
    }

    .compact-card-image {
      height: 200px;
    }
  }

  @media (max-width: 768px) {
    .compact-featured-section {
      padding: 20px 10px;
    }

    .compact-nav-btn {
      font-size: 11px;
      padding: 6px 12px;
    }

    .compact-title {
      font-size: 20px;
    }

    .compact-property-card {
      flex: 0 0 calc(50% - 10px);
      min-width: 240px;
    }

    .compact-card-image {
      height: 180px;
    }

    .compact-card-price {
      font-size: 16px;
    }

    .modern-carousel-container {
      height: 500px;
    }

    .modern-carousel-slide {
      width: 90%;
      max-width: 380px;
      height: 450px;
    }

    .modern-carousel-slide.next,
    .modern-carousel-slide.prev {
      display: none;
    }

    .modern-property-info {
      padding: 20px 15px 15px;
    }

    .modern-info-row {
      gap: 15px;
    }

    .modern-price {
      font-size: 20px;
    }
  }

  @media (max-width: 480px) {
    .compact-featured-section {
      padding: 15px 8px;
    }

    .compact-nav-buttons {
      gap: 4px;
    }

    .compact-nav-btn {
      font-size: 10px;
      padding: 5px 10px;
    }

    .compact-title {
      font-size: 18px;
      margin-bottom: 15px;
    }

    .compact-property-card {
      flex: 0 0 85%;
      min-width: 200px;
    }

    .compact-card-image {
      height: 160px;
    }

    .compact-card-price {
      font-size: 15px;
    }

    .compact-badge {
      font-size: 10px;
      padding: 3px 8px;
    }

    .modern-nav-tabs {
      gap: 4px;
    }

    .modern-nav-tabs button {
      font-size: 11px;
      padding: 8px 12px;
    }

    .modern-search-bar {
      padding: 12px 20px;
    }

    .modern-search-bar select {
      font-size: 14px;
    }

    .modern-carousel-container {
      height: 450px;
    }

    .modern-carousel-slide {
      height: 400px;
    }

    .modern-info-row {
      gap: 10px;
      font-size: 12px;
    }

    .modern-property-info {
      padding: 15px 12px 12px;
    }
  }
  </style>
  <script src="assets/js/custom-dropdown.js" defer></script>
  
  <!-- Structured Data -->
  <?php 
  // Prepare properties data for structured data
  $propertiesForStructuredData = [];
  if (!empty($featuredProperties)) {
    foreach ($featuredProperties as $property) {
      $propertiesForStructuredData[] = [
        'title' => $property['title'],
        'description' => $property['description'],
        'price' => $property['price'],
        'image' => !empty($property['cover_image_url']) ? 'uploads/properties/' . $property['cover_image_url'] : 'assets/images/prop/prop1.png'
      ];
    }
  }
  echo SEOConfig::generateStructuredData('home', $propertiesForStructuredData);
  ?>
</head>

<body>
<?php $asset_path = 'assets/'; ?>
<?php include __DIR__ . '/components/loader.php'; ?>
  <?php include __DIR__ . '/components/navbar.php'; ?>
  <section class=" hero-section">
    <div class="text-center  headh ">
      <h1 class="fw-bold"><span style="color:rgba(230, 27, 27, 0.93)">Next-Gen </span>Real Estate</h1>
      <p class="sfpro subtitle">Discover homes that combine style, comfort, and future-ready living for a modern world.</p>
      <div class="hero-image-container">
        <img src="assets/images/hero-image.jpg" alt="hero-image" class="hero-image">
        <img src="assets/images/hero-image.jpg" alt="hero-image" class="hero-image">
      </div>
    </div>
  </section>

<?php 
  // Include featured properties carousel component with place div
  include __DIR__ . '/components/search-input.php'; 
  ?>


  <?php 
  // Include featured properties carousel component with place div
  include __DIR__ . '/components/featured-prop.php'; 
  ?>

<?php include 'components/certification.php'; ?>

  <!-- partner section -->
  <?php include 'components/trusted-clients.php'; ?>



  <div class="container" id="latest-properties">
    <!-- Title Section -->
    <div class="property-title-section text-left">
      <div class="property-title">
        Discover Latest Properties
      </div>
      <div class="property-subtitle">
        Newest Properties Around You
      </div>
    </div>
    <!-- Properties Grid -->
    <?php
    $properties = [];
    $propsLimit = 6; // show max 6 recently added properties
    $totalProps = 0;
    try {
      // Total count
      $sqlPropsCount = "SELECT COUNT(*) AS cnt FROM properties WHERE status = 'Available'";
      if (isset($mysqli) && ($resCnt = $mysqli->query($sqlPropsCount))) {
        if ($rowCnt = $resCnt->fetch_assoc()) {
          $totalProps = (int)$rowCnt['cnt'];
        }
        $resCnt->free();
      }
      $sqlProps = "
              SELECT 
                p.id,
                p.title,
                p.description,
                p.configuration,
                p.parking,
                p.area,
                p.furniture_status,
                (
                  SELECT pi.image_url 
                  FROM property_images pi 
                  WHERE pi.property_id = p.id 
                  ORDER BY pi.id ASC 
                  LIMIT 1
                ) AS cover_image_url
              FROM properties p
              WHERE p.status = 'Available'
              ORDER BY p.created_at DESC, p.id DESC
              LIMIT " . (int)$propsLimit . "
            ";
      if (isset($mysqli) && $result = $mysqli->query($sqlProps)) {
        while ($row = $result->fetch_assoc()) {
          $properties[] = $row;
        }
        $result->free();
      }
    } catch (Throwable $e) {
      error_log('Properties load error: ' . $e->getMessage());
    }

    // featured properties loaded at top of file

    // No image fallback; use first property image as cover when available
    ?>
    <div class="row g-4">
      <?php if (!empty($properties)): ?>
        <?php foreach ($properties as $i => $p): ?>
          <div class="col-md-4">
            <a href="products/product-details.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
            <div class="card property-card h-100">
              <?php
              $img = trim((string)($p['cover_image_url'] ?? ''));
              if ($img !== '') {
                // Check if it's already an absolute URL
                if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '/')) {
                  // leave as-is
                } else {
                  // Handle relative paths - assume they're from uploads/properties/
                  if (strpos($img, '/') === false) {
                    // Just a filename, check in uploads/properties/
                    $img = 'uploads/properties/' . $img;
                  } else {
                    // Has path, clean it up
                    $img = ltrim($img, './');
                    // If it doesn't start with uploads/, prepend it
                    if (!str_starts_with($img, 'uploads/')) {
                      $img = 'uploads/properties/' . basename($img);
                    }
                  }
                }
              }
              // Fallback to default image if empty
              if (empty($img)) {
                $img = 'assets/images/prop/prop1.png';
              }
              ?>
              <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($p['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?>" class="propimg" style="width: 100%; height: 277px; object-fit: cover; border-radius: 8px;">
              <div class="card-body">
                <div class="card-title"><?php echo htmlspecialchars($p['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="property-attrs">
                  <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg"> <?php echo htmlspecialchars($p['configuration'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg"> <?php echo ($p['parking'] ?? '') === 'Yes' ? '1' : '0'; ?></div>
                  <div class="property-attr attr-extra"><img src="assets/images/icon/sqft_dark.svg" class="svg"> <?php echo htmlspecialchars((string)($p['area'] ?? '—')); ?> sq. ft.</div>
                  <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg"> —</div>
                  <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg"> <?php echo htmlspecialchars($p['furniture_status'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    







    <!-- prime location  -->
    <div class="container location-cont">
      <div class="location-section">
        <div class="location-title">
          Prime Locations
        </div>
        <div class="location-subtitle">
          Trending areas you can’t miss
        </div>
        <?php
        // Load cities from DB; fall back to static if none
        $cities = [];
        try {
          $sqlCities = "SELECT id, name, image_url FROM cities ORDER BY id ASC LIMIT 5";
          if (isset($mysqli) && $result = $mysqli->query($sqlCities)) {
            while ($row = $result->fetch_assoc()) {
              $cities[] = $row;
            }
            $result->free();
          }
        } catch (Throwable $e) {
          error_log('Cities load error: ' . $e->getMessage());
        }
        ?>
        <div class="container">
          <div class="location-grid location-grid-mobile">
            <?php if (!empty($cities)): ?>
              <?php foreach ($cities as $idx => $city): ?>
                <div>
                  <div class="city-card<?php echo $idx === 0 ? ' location-extra1' : ($idx === 1 ? ' location-extra2' : ''); ?>">
                    <?php
                    $cimg = trim((string)($city['image_url'] ?? ''));
                    if ($cimg !== '') {
                      // Check if it's already an absolute URL
                      if (str_starts_with($cimg, 'http://') || str_starts_with($cimg, 'https://') || str_starts_with($cimg, '/')) {
                        // leave as-is
                      } else {
                        // Handle relative paths - assume they're from uploads/locations/
                        if (strpos($cimg, '/') === false) {
                          // Just a filename, check in uploads/locations/
                          $cimg = 'uploads/locations/' . $cimg;
                        } else {
                          // Has path, clean it up
                          $cimg = ltrim($cimg, './');
                          // If it doesn't start with uploads/, prepend it
                          if (!str_starts_with($cimg, 'uploads/')) {
                            $cimg = 'uploads/locations/' . basename($cimg);
                          }
                        }
                      }
                    }
                    // Fallback to default image if empty
                    if (empty($cimg)) {
                      $cimg = 'assets/images/loc/mlore.png';
                    }
                    ?>
                    <a href="products/index.php?city=<?php echo urlencode($city['name']); ?>" style="display: block; text-decoration: none; color: inherit;">
                      <img src="<?php echo htmlspecialchars($cimg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($city['name'] ?? 'City', ENT_QUOTES, 'UTF-8'); ?>">
                      <div class="city-label"><?php echo htmlspecialchars($city['name'] ?? 'City', ENT_QUOTES, 'UTF-8'); ?></div>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'components/our-works.php'; ?>

  <!-- Apartment     -->
  <div class="container  ">

    <div class="house-title">
      Apartments, Villas and more
    </div>
    <div class="row apt  ">
      <div class="col-md-3">
        <a href="products/index.php?category=Residential" style="display: block; text-decoration: none; color: inherit;">
          <img src="assets/images/prop/apt.png" alt="Residential">
          <p class="figtree">Residential</p>
        </a>
      </div>

      <div class="col-md-3">
        <a href="products/index.php?category=Independent%20House" style="display: block; text-decoration: none; color: inherit;">
          <img src="assets/images/prop/indhouse.png" alt="Independent house">
          <p class="figtree">Independent House</p>
        </a>
      </div>

      <div class="col-md-3">
        <a href="products/index.php?category=Studio" style="display: block; text-decoration: none; color: inherit;">
          <img src="assets/images/prop/wspace.png" alt="Studio">
          <p class="figtree">Studio</p>
        </a>
      </div>

      <div class="col-md-3">
        <a href="products/index.php?category=Plot" style="display: block; text-decoration: none; color: inherit;">
          <img src="assets/images/prop/plot.png" alt="Plot">
          <p class="figtree">Plot</p>
        </a>
      </div>
    </div>
  </div>




  <?php include 'components/invest-dubai.php'; ?>

  <?php include 'components/mini-banner-connect.php'; ?>




  
  
  <?php include 'components/footer.php'; ?>
  
  <script src="https://hammerjs.github.io/dist/hammer.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/scripts.js"></script>

  <script>
    function goToPropertyDetails(propertyId) {
      window.location.href = 'products/product-details.php?id=' + propertyId;
    }

    // Static search bar buttons → navigate to products with category
    function onStaticCategoryClick(categoryName) {
      if (!categoryName) return;
      var url = 'products/index.php?category=' + encodeURIComponent(categoryName);
      window.location.href = url;
    }

    // Listing type buttons (Buy / Rent / PG/Co-living)
    function onStaticListingClick(listingType) {
      if (!listingType) return;
      var url = 'products/index.php?listing=' + encodeURIComponent(listingType);
      window.location.href = url;
    }

    // City change on hero search: navigate to products page with city param
    function onHeroCityChange(selectEl) {
      var cityName = selectEl && selectEl.value ? selectEl.value.trim() : '';
      var selectedOption = selectEl ? selectEl.options[selectEl.selectedIndex] : null;
      var cityId = selectedOption ? selectedOption.getAttribute('data-city-id') : '';
      // Fallback for custom dropdown (option mirrored outside native select)
      if (!cityId) {
        try {
          var wrapper = selectEl.closest('.select-wrapper');
          var customSelected = wrapper ? wrapper.querySelector('.dropdown-option.selected') : null;
          if (customSelected && customSelected.dataset && customSelected.dataset.cityId) {
            cityId = customSelected.dataset.cityId;
          }
        } catch (e) {}
      }
      var base = 'products/index.php';
      if (!cityName) { window.location.href = base; return; }
      var url = base + '?city=' + encodeURIComponent(cityName);
      if (cityId) { url += '&city_id=' + encodeURIComponent(cityId); }
      window.location.href = url;
    }

    // Navigate to featured properties page
    function goToFeaturedProperties() {
      window.location.href = 'products/index.php?featured=1';
    }

    // Make functions available globally immediately
    window.onStaticCategoryClick = onStaticCategoryClick;
    window.onStaticListingClick = onStaticListingClick;
    window.onHeroCityChange = onHeroCityChange;
  </script>
</body>

</html>