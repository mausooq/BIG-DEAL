<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/seo_config.php';
$mysqli = getMysqliConnection();

// Load featured properties BEFORE rendering the carousel
$featuredProperties = [];
try {
  $sqlFeatured = "
    SELECT 
      p.id,
      p.title,
      p.description,
      p.configuration,
      p.parking,
      p.area,
      p.furniture_status,
      p.balcony,
      p.price,
      (
        SELECT pi.image_url 
        FROM property_images pi 
        WHERE pi.property_id = p.id 
        ORDER BY pi.id ASC 
        LIMIT 1
      ) AS cover_image_url
    FROM properties p
    INNER JOIN features f ON p.id = f.property_id
    WHERE p.status = 'Available'
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 3
  ";
  if (isset($mysqli) && $result = $mysqli->query($sqlFeatured)) {
    while ($row = $result->fetch_assoc()) {
      $featuredProperties[] = $row;
    }
    $result->free();
  }
} catch (Throwable $e) {
  error_log('Featured properties load error: ' . $e->getMessage());
}

// Load categories to validate static search buttons against DB
$availableCategoryNames = [];
try {
  if (isset($mysqli)) {
    $resCats = $mysqli->query("SELECT name FROM categories");
    if ($resCats) {
      while ($r = $resCats->fetch_assoc()) {
        $availableCategoryNames[strtolower(trim($r['name']))] = true;
      }
      $resCats->free();
    }
  }
} catch (Throwable $e) {
  error_log('Categories load error: ' . $e->getMessage());
}

// Selected city from query (for preserving selection in dropdown)
$selectedCity = isset($_GET['city']) ? trim((string)$_GET['city']) : '';

// Load cities for the hero search select
$allCityNames = [];
try {
  if (isset($mysqli)) {
    if ($resAllCities = $mysqli->query("SELECT name FROM cities ORDER BY name")) {
      while ($row = $resAllCities->fetch_assoc()) {
        $allCityNames[] = $row['name'];
      }
      $resAllCities->free();
    }
  }
} catch (Throwable $e) {
  error_log('All cities load error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <?php echo SEOConfig::generateMetaTags('home'); ?>
  <link rel="icon" href="assets/images/favicon.png" type="image/png">
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



  <div class="place">
    <div class="container">
      
      <div class="row">
        <div class="nav-tabs-custom mx-auto justify-content-evenly flex-wrap gap-1">
          <button type="button" data-category="Buy" onclick="onStaticListingClick('Buy')">Buy</button>
          <button type="button" data-category="Rent" onclick="onStaticListingClick('Rent')">Rent</button>
          <button type="button" data-category="Plot" onclick="onStaticCategoryClick('Plot')">Plot</button>
          <button type="button" data-category="Commercial" onclick="onStaticCategoryClick('Commercial')">Commercial</button>
          <button type="button" data-category="PG/Co-living" onclick="onStaticListingClick('PG/Co-living')">PG/Co-living</button>
          <button type="button" data-category="1BHK/Studio" onclick="onStaticCategoryClick('1BHK/Studio')">1BHK/Studio</button>
        </div>
      </div>

      <!-- Select city with enhanced UI -->
      <div class="custom-select-wrapper">
        <select class="custom-select" name="city" id="city-select" aria-label="Select city" onchange="onHeroCityChange(this.value)">
          <option value="" <?php echo $selectedCity === '' ? 'selected' : ''; ?>>All Cities</option>
          <?php foreach ($allCityNames as $cityName): ?>
            <option value="<?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($selectedCity, $cityName) === 0 ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>



      <div class="d-flex row blackprop">
        <div class="col-md-4">
          <img src="assets/images/black_hero.png" alt="prop1" class="">
        </div>
        <div class="inter col-md-7">
          <div class="small-text">Check out</div>
          <div class="large-text" onclick="goToFeaturedProperties()" style="cursor: pointer;">
            <span class="gugi">Featured <span style="color: red;">Properties</span></span>
            <img src="assets/images/ARROW.png" alt="arrow" class="arrow">
            <div class="feature-stats-container" style="margin-bottom: 20px;">
          <div class="feature-item">
              <div class="feature-icon-circle">
                  <svg class="feature-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
              </div>
              <span class="feature-text">250+ New Listings Weekly</span>
          </div>
          <div class="feature-item">
              <div class="feature-icon-circle">
                  <svg class="feature-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
              </div>
              <span class="feature-text">Advanced Search Tools</span>
          </div>
          <div class="feature-item">
              <div class="feature-icon-circle">
                  <svg class="feature-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
              </div>
              <span class="feature-text">Top-Rated Local Agents</span>
          </div>
      </div>
          </div>
        </div>
      </div>

    

      <div class="carousel-container">
        <?php if (!empty($featuredProperties)): ?>
          <?php foreach ($featuredProperties as $index => $featured): ?>
            <?php
            $img = trim((string)($featured['cover_image_url'] ?? ''));
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
            <a href="products/product-details.php?id=<?php echo (int)$featured['id']; ?>" class="carousel-slide <?php echo $index === 0 ? 'active' : ($index === 1 ? 'next' : ''); ?>" style="display: block;">
              <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($featured['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?>" class="imgs">
              <div class="info-box">
                <div class="info-top">
                  <div class="info-item" title="<?php echo htmlspecialchars($featured['configuration'] ?? 'Configuration', ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($featured['configuration'] ?? 'Configuration', ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="assets/images/icon/Home.svg" class="svg">
                    <?php echo htmlspecialchars($featured['configuration'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                  <div class="info-item" title="Parking" aria-label="parking">
                    <img src="assets/images/icon/park.svg" class="svg">
                    <?php echo ($featured['parking'] ?? '') === 'Yes' ? '1' : '0'; ?> Cars
                  </div>
                  <div class="info-item" title="Area" aria-label="area">
                    <img src="assets/images/icon/sqft.svg" class="svg">
                    <?php echo htmlspecialchars((string)($featured['area'] ?? '—')); ?> sq.ft.
                  </div>
                </div>
                <div class="info-bottom">
                  <div class="info-item" title="Balcony" aria-label="balcony">
                    <img src="assets/images/icon/terrace.svg" class="svg">
                    <?php echo $featured['balcony'] ?? '—'; ?> Balcony
                  </div>
                  <div class="info-item" title="Furniture Status" aria-label="furniture status">
                    <img src="assets/images/icon/sofa.svg" class="svg">
                    <?php echo htmlspecialchars($featured['furniture_status'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <!-- Navigation dots -->
      <div class="carousel-dots">
        <?php if (!empty($featuredProperties)): ?>
          <?php foreach ($featuredProperties as $index => $featured): ?>
            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="showSlide(<?php echo $index; ?>)"></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>

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




  <script src="https://hammerjs.github.io/dist/hammer.js"></script>


  <?php include 'components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/scripts.js"></script>

  <script>
    function goToPropertyDetails(propertyId) {
      window.location.href = 'products/product-details.php?id=' + propertyId;
    }

    // Static search bar buttons → navigate to products with category, validated vs DB categories
    function onStaticCategoryClick(categoryName) {
      try {
        var available = <?php echo json_encode(array_keys($availableCategoryNames), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        // We always navigate; the products page will filter by category if it exists in DB
        var url = 'products/index.php?category=' + encodeURIComponent(categoryName);
        window.location.href = url;
      } catch (e) {
        window.location.href = 'products/index.php?category=' + encodeURIComponent(categoryName);
      }
    }

    // Listing type buttons (Buy / Rent / PG/Co-living)
    function onStaticListingClick(listingType) {
      try {
        var url = 'products/index.php?listing=' + encodeURIComponent(listingType);
        window.location.href = url;
      } catch (e) {
        window.location.href = 'products/index.php?listing=' + encodeURIComponent(listingType);
      }
    }

    // City change on hero search: navigate to products page with city param
    function onHeroCityChange(cityName) {
      const url = 'products/index.php' + (cityName ? ('?city=' + encodeURIComponent(cityName)) : '');
      window.location.href = url;
    }

    // Navigate to featured properties page
    function goToFeaturedProperties() {
      window.location.href = 'products/index.php?featured=1';
    }
  </script>
</body>

</html>