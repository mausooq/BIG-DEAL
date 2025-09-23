<?php
require_once __DIR__ . '/config/config.php';
$mysqli = getMysqliConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <link rel="icon" href="assets/images/logo.png" type="image/png">
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
</head>
<body>


  <?php $asset_path = 'assets/'; include __DIR__ . '/components/navbar.php'; ?>
   <section class=" hero-section">
    <div class="text-center  headh  figtree ">
      <h1 class="fw-bold">Find your Beautiful House</h1>
      <p class="sfpro subtitle">From breathtaking views to exquisite furnishings, our accommodations <br>redefine luxury and offer an experience beyond compare.</p>
      <div class="hero-image-container">
        <img src="assets/images/hero-image.jpg" alt="hero-image" class="hero-image">
        <img src="assets/images/hero-image.jpg" alt="hero-image" class="hero-image">
      </div>
    </div>
    </section>
    
   
   
  <div class="place">
   <div class="container">
   
        <div class="row  ">
        <div class="nav-tabs-custom mx-auto d-flex justify-content-evenly flex-wrap gap-1">
          <button class="active" type="button">Buy</button>
          <button type="button">Rent</button>
          <button type="button">Plot</button>
          <button type="button">Commercial</button>
          <button type="button">PG/Co Living</button>
          <button type="button">1BHK/Studio</button>
        </div>
        </div>

          <!-- Select city -->
        <div class="custom-select-wrapper">
          <select class="custom-select" name="city" id="city-select" aria-label="Select city">
            <option disabled selected>Select city</option>
            <option value="newyork">New York</option>
            <option value="losangeles">Los Angeles</option>
            <option value="chicago">Chicago</option>
            <option value="houston">Houston</option>
            <option value="miami">Miami</option>
          </select>
        </div>

   

    <div class="d-flex row blackprop">
        <div class="col-md-4">
          <img src="assets/images/black_hero.png" alt="prop1" class="">
        </div>
        <div class="inter col-md-7">
          <div class="small-text">Check out</div>
          <div class="large-text">
            <span class="gugi">Featured <span style="color: red;">Properties</span></span>
            <img src="assets/images/ARROW.png" alt="arrow" class="arrow">
          </div>
          </div>
    </div>


      <div class="carousel-container">
        <div class="carousel-slide active">
          <img src="assets/images/slider1/DHP1.png" alt="House 1" class="imgs">
          <div class="info-box">
            <div class="info-top">
              <div class="info-item" title="4 Bedrooms" aria-label="4 bedrooms">
              <img src="assets/images/icon/home.svg" class="svg">
                4 BHK
              </div>
              <div class="info-item" title="4 Parking Spots" aria-label="4 parking spots">
                <img src="assets/images/icon/park.svg" class="svg">
                4 Cars
              </div>
              <div class="info-item" title="4 Square Feet" aria-label="4 square feet">
                <img src="assets/images/icon/sqft.svg" class="svg">
                4 sq.ft.
              </div>
            </div>
            <div class="info-bottom">
              <div class="info-item" title="4 Floors" aria-label="4 floors">
                <img src="assets/images/icon/terrace.svg" class="svg">            4 Floors
              </div>
              <div class="info-item" title="Semi-furnished" aria-label="semi furnished">
              <img src="assets/images/icon/sofa.svg" class="svg">
                Semi-furnished
              </div>
            </div>
          </div>
        </div>
        
        <div class="carousel-slide next">
          <img src="assets/images/slider1/DHP5.png" alt="House 2" class="imgs">
           <div class="info-box">
            <div class="info-top">
              <div class="info-item" title="4 Bedrooms" aria-label="4 bedrooms">
              <img src="assets/images/icon/home.svg" class="svg">
                4 BHK
              </div>
              <div class="info-item" title="4 Parking Spots" aria-label="4 parking spots">
                <img src="assets/images/icon/park.svg" class="svg">
                4 Cars
              </div>
              <div class="info-item" title="4 Square Feet" aria-label="4 square feet">
                <img src="assets/images/icon/sqft.svg" class="svg">
                4 sq.ft.
              </div>
            </div>
            <div class="info-bottom">
              <div class="info-item" title="4 Floors" aria-label="4 floors">
                <img src="assets/images/icon/terrace.svg" class="svg">            4 Floors
              </div>
              <div class="info-item" title="Semi-furnished" aria-label="semi furnished">
              <img src="assets/images/icon/sofa.svg" class="svg">
                Semi-furnished
              </div>
            </div>
          </div>
        </div>

        
        <div class="carousel-slide ">
          <img src="assets/images/slider1/DHP4.png" alt="House 2" class="imgs">
           <div class="info-box">
            <div class="info-top">
              <div class="info-item" title="4 Bedrooms" aria-label="4 bedrooms">
              <img src="assets/images/icon/home.svg" class="svg">
                4 BHK
              </div>
              <div class="info-item" title="4 Parking Spots" aria-label="4 parking spots">
                <img src="assets/images/icon/park.svg" class="svg">
                4 Cars
              </div>
              <div class="info-item" title="4 Square Feet" aria-label="4 square feet">
                <img src="assets/images/icon/sqft.svg" class="svg">
                4 sq.ft.
              </div>
            </div>
            <div class="info-bottom">
              <div class="info-item" title="4 Floors" aria-label="4 floors">
                <img src="assets/images/icon/terrace.svg" class="svg">            4 Floors
              </div>
              <div class="info-item" title="Semi-furnished" aria-label="semi furnished">
              <img src="assets/images/icon/sofa.svg" class="svg">
                Semi-furnished
              </div>
            </div>
          </div>
        </div>
      </div>
  <!-- Navigation dots -->
      <div class="carousel-dots">
        <span class="dot active" onclick="showSlide(0)"></span>
        <span class="dot" onclick="showSlide(1)"></span>
        <span class="dot" onclick="showSlide(2)"></span>
        
      </div>
  
    </div>
    </div>



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
          $showAllProps = isset($_GET['more']) && $_GET['more'] == '1';
          $propsLimit = $showAllProps ? 1000 : 6;
          $totalProps = 0;
          try {
            // Total count
            $sqlPropsCount = "SELECT COUNT(*) AS cnt FROM properties WHERE status = 'Available'";
            if (isset($mysqli) && ($resCnt = $mysqli->query($sqlPropsCount))) {
              if ($rowCnt = $resCnt->fetch_assoc()) { $totalProps = (int)$rowCnt['cnt']; }
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
              while ($row = $result->fetch_assoc()) { $properties[] = $row; }
              $result->free();
            }
          } catch (Throwable $e) { error_log('Properties load error: ' . $e->getMessage()); }
          // No image fallback; use first property image as cover when available
        ?>
        <div class="row g-4">
          <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $i => $p): ?>
              <div class="col-md-4">
                <div class="card property-card h-100">
                  <?php
                    $img = trim((string)($p['cover_image_url'] ?? ''));
                    if ($img !== '') {
                      $isAbsolute = str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '/');
                      if ($isAbsolute) {
                        // leave as-is
                      } else {
                        // If it's just a filename, try to resolve against known folders
                        $hasSlash = strpos($img, '/') !== false;
                        $resolved = '';
                        if (!$hasSlash) {
                          $name = basename($img);
                          $root = dirname(__DIR__); // project root
                          $candidates = [
                            'uploads/properties/' . $name,
                            'uploads/' . $name,
                            'assets/images/prop/' . $name,
                          ];
                          foreach ($candidates as $relPath) {
                            if (file_exists($root . '/' . $relPath)) {
                              $resolved = '../' . $relPath; // from test/index.php to root
                              break;
                            }
                          }
                          $img = $resolved !== '' ? $resolved : '';
                        } else {
                          // relative path provided; make it work from /test/
                          $img = '../' . ltrim($img, './');
                        }
                      }
                    }
                  ?>
                  <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($p['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?>" class="propimg">
                  <div class="card-body">
                    <div class="card-title"><?php echo htmlspecialchars($p['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="property-attrs">
                      <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > <?php echo htmlspecialchars($p['configuration'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > <?php echo ($p['parking'] ?? '') === 'Yes' ? '1' : '0'; ?></div>
                      <div class="property-attr attr-extra"><img src="assets/images/icon/sqft_dark.svg" class="svg" > <?php echo htmlspecialchars((string)($p['area'] ?? '—')); ?> sq. ft.</div>
                      <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > —</div>
                      <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > <?php echo htmlspecialchars($p['furniture_status'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php if (!$showAllProps && $totalProps > $propsLimit): ?>
          <div class="text-center" style="margin: 20px 0;">
            <a href="?more=1#latest-properties" class="view-all-btn">View More →</a>
          </div>
        <?php endif; ?>
   
        


        


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
              while ($row = $result->fetch_assoc()) { $cities[] = $row; }
              $result->free();
            }
          } catch (Throwable $e) { error_log('Cities load error: ' . $e->getMessage()); }
        ?>
        <div class="container">
          <div class="location-grid">
            <?php if (!empty($cities)): ?>
              <?php foreach ($cities as $idx => $city): ?>
                <div>
                  <div class="city-card<?php echo $idx === 0 ? ' location-extra1' : ($idx === 1 ? ' location-extra2' : ''); ?>">
                    <?php
                      $cimg = trim((string)($city['image_url'] ?? ''));
                      if ($cimg !== '') {
                        $isAbs = str_starts_with($cimg, 'http://') || str_starts_with($cimg, 'https://') || str_starts_with($cimg, '/');
                        if ($isAbs) {
                          // leave as-is
                        } else {
                          $hasSlash = strpos($cimg, '/') !== false;
                          $resolved = '';
                          if (!$hasSlash) {
                            $name = basename($cimg);
                            $root = dirname(__DIR__);
                            $candidates = [
                              'uploads/locations/' . $name,
                              'uploads/' . $name,
                              'assets/images/loc/' . $name,
                            ];
                            foreach ($candidates as $rel) {
                              if (file_exists($root . '/' . $rel)) { $resolved = '../' . $rel; break; }
                            }
                            $cimg = $resolved !== '' ? $resolved : '';
                          } else {
                            $cimg = '../' . ltrim($cimg, './');
                          }
                        }
                      }
                      if ($cimg === '') { $cimg = 'assets/images/loc/black_hero.png'; }
                    ?>
                    <img src="<?php echo htmlspecialchars($cimg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($city['name'] ?? 'City', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="city-label"><?php echo htmlspecialchars($city['name'] ?? 'City', ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div>
                <div class="city-card location-extra1">
                  <img src="assets/images/loc/blore.png" alt="Bengaluru">
                  <div class="city-label">Bengaluru</div>
                </div>
              </div>
              <div>
                <div class="city-card location-extra2">
                  <img src="assets/images/loc/mysore.png" alt="Mysuru">
                  <div class="city-label">Mysuru</div>
                </div>
              </div>
              <div>
                <div class="city-card">
                  <img src="assets/images/loc/mlore.png" alt="Mangaluru">
                  <div class="city-label">Mangaluru</div>
                </div>
              </div>
              <div>
                <div class="city-card">
                  <img src="assets/images/loc/chikm.png" alt="Chikkamagaluru">
                  <div class="city-label">Chikkamagaluru</div>
                </div>
              </div>
              <div>
                <div class="city-card">
                  <img src="assets/images/loc/kgd.png" alt="Kasaragod">
                  <div class="city-label">Kasaragod</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
  </div>

    </div>
    </div>


    
      <!-- Apartment     -->
      <div class="container  ">

                <div class="house-title">
                Apartments, Villas and more
            </div>
            <div class="row apt  ">
              <div class="col-md-3">
                <img src="assets/images/prop/apt.png" alt="Residential">
                <p class="figtree">Residential</p>
              </div>

              <div class="col-md-3">
                <img src="assets/images/prop/indhouse.png" alt="Independent house">
                <p class="figtree">Independent House</p>
              </div>

              <div class="col-md-3">
                <img src="assets/images/prop/wspace.png" alt="Working Space">
                <p class="figtree">Working Space</p>
              </div>

              <div class="col-md-3">
                <img src="assets/images/prop/plot.png" alt="Plot">
                <p class="figtree">Plot</p>
              </div>
            </div>
     </div>




     <!-- contact  -->
    <?php include 'components/letsconnect.php'; ?>


    <!-- Testimonials -->
    <?php include 'components/testimonial.php'; ?>


    <!-- about section  -->
    <?php include 'components/about.php'; ?>

    <!-- blog section  -->
    <?php include 'components/blog.php'; ?>




  <!-- Faq  -->
<?php
  // Load FAQs from database (reuse connection from top)
  $faqs = [];
  try {
    $sql = "SELECT id, question, answer FROM faqs ORDER BY COALESCE(order_id, 1000000), id";
    if (isset($mysqli) && $result = $mysqli->query($sql)) {
      while ($row = $result->fetch_assoc()) {
        $faqs[] = $row;
      }
      $result->free();
    }
  } catch (Throwable $e) {
    // Fail silently in UI; optionally log
    error_log('FAQ load error: ' . $e->getMessage());
  }
?>
<section class="container-fluid faq">
  <div class="row">

    <div class="col-md-5">
      <button class="btn-faq">FAQs</button>
      <h3>
        Your <span style="color: red;">questions</span> <br> answered
      </h3>

      <p>
        Here are the most common questions<br> clients ask.
      </p>

      <button class="btn-arrow">
      Get in Touch <span>→</span>
    </button>

    </div>

    <div class="col-md-7">
      
      <div class="FaqQ">
        <?php if (!empty($faqs)): ?>
          <?php foreach ($faqs as $index => $faq): ?>
            <div class="FaqQ-item<?php echo $index === 0 ? ' ' : ''; ?>">
              <div class="FaqQ-title">
                <span><?php echo htmlspecialchars($faq['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                <img src="assets/images/icon/arrowdown.svg" alt="arrow" class="farrow down">
              </div>
              <div class="FaqQ-content">
                <span><?php echo htmlspecialchars($faq['answer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="FaqQ-item">
            <div class="FaqQ-title">
              <span>No FAQs available right now.</span>
              <img src="assets/images/icon/arrowdown.svg" alt="arrow" class="farrow down">
            </div>
            <div class="FaqQ-content">
              <span>Please check back later.</span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>




<script src="https://hammerjs.github.io/dist/hammer.js"></script>

 
<?php include 'components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/scripts.js"></script>
</body>
</html>
