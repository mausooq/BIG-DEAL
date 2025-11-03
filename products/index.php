<?php
require_once __DIR__ . '/../config/config.php';

// Get selected filters from URL parameters
$mysqli = getMysqliConnection();
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedListing = isset($_GET['listing']) ? ucfirst(strtolower($_GET['listing'])) : '';
$selectedFurnished = isset($_GET['furnished']) ? $_GET['furnished'] : '';
$selectedCity = isset($_GET['city']) ? $_GET['city'] : '';
$isFeaturedOnly = isset($_GET['featured']) && $_GET['featured'] === '1';

// Get additional filter parameters
$selectedBedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$selectedConstructionStatus = isset($_GET['constructionStatus']) ? $_GET['constructionStatus'] : '';
$selectedLocalities = isset($_GET['localities']) ? $_GET['localities'] : '';
$minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : 0;
$maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : 0;
$minArea = isset($_GET['minArea']) ? (float)$_GET['minArea'] : 0;
$maxArea = isset($_GET['maxArea']) ? (float)$_GET['maxArea'] : 0;

// Load cities for the city select
$cities = [];
try {
    $citySql = "SELECT id, name FROM cities ORDER BY name";
    if ($resCity = $mysqli->query($citySql)) {
        while ($row = $resCity->fetch_assoc()) { $cities[] = $row; }
        $resCity->free();
    }
} catch (Throwable $e) { error_log('Cities load error: ' . $e->getMessage()); }

// Load listing types for Types of Property filter
$listingTypes = [];
try {
    $listingTypeSql = "SELECT DISTINCT listing_type FROM properties WHERE listing_type IS NOT NULL AND status = 'Available' ORDER BY listing_type";
    if ($resListingType = $mysqli->query($listingTypeSql)) {
        while ($row = $resListingType->fetch_assoc()) { $listingTypes[] = $row['listing_type']; }
        $resListingType->free();
    }
} catch (Throwable $e) { error_log('Listing types load error: ' . $e->getMessage()); }

// Load furnished statuses for Furnished Status filter
$furnishedStatuses = [];
try {
    $furnSql = "SELECT DISTINCT furniture_status FROM properties WHERE furniture_status IS NOT NULL AND furniture_status <> '' ORDER BY furniture_status";
    if ($resFurn = $mysqli->query($furnSql)) {
        while ($row = $resFurn->fetch_assoc()) { $furnishedStatuses[] = $row['furniture_status']; }
        $resFurn->free();
    }
} catch (Throwable $e) { error_log('Furnished statuses load error: ' . $e->getMessage()); }

// Load categories for Category filter
$categories = [];
try {
    $categorySql = "SELECT id, name FROM categories ORDER BY name";
    if ($resCategory = $mysqli->query($categorySql)) {
        while ($row = $resCategory->fetch_assoc()) { $categories[] = $row; }
        $resCategory->free();
    }
} catch (Throwable $e) { error_log('Categories load error: ' . $e->getMessage()); }

// Load bedroom configurations for No of Bedrooms filter
$configurations = [];
try {
    $configSql = "SELECT DISTINCT configuration FROM properties WHERE configuration IS NOT NULL AND configuration != '' ORDER BY configuration";
    if ($resConfig = $mysqli->query($configSql)) {
        while ($row = $resConfig->fetch_assoc()) { $configurations[] = $row['configuration']; }
        $resConfig->free();
    }
} catch (Throwable $e) { error_log('Configurations load error: ' . $e->getMessage()); }

// Load localities (towns) for Localities filter
$localities = [];
try {
    $localitySql = "SELECT t.id, t.name, c.name as city_name FROM towns t 
                    JOIN cities c ON t.city_id = c.id 
                    ORDER BY c.name, t.name";
    if ($resLocality = $mysqli->query($localitySql)) {
        while ($row = $resLocality->fetch_assoc()) { $localities[] = $row; }
        $resLocality->free();
    }
} catch (Throwable $e) { error_log('Localities load error: ' . $e->getMessage()); }

// Get dynamic price range from database
$priceRange = ['min' => 0, 'max' => 10000000];
try {
    $priceSql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM properties WHERE price > 0";
    if ($resPrice = $mysqli->query($priceSql)) {
        $priceData = $resPrice->fetch_assoc();
        if ($priceData && $priceData['min_price'] && $priceData['max_price']) {
            $priceRange['min'] = (int)$priceData['min_price'];
            $priceRange['max'] = (int)$priceData['max_price'];
        }
        $resPrice->free();
    }
} catch (Throwable $e) { error_log('Price range load error: ' . $e->getMessage()); }

// Get dynamic area range from database
$areaRange = ['min' => 0, 'max' => 10000];
try {
    $areaSql = "SELECT MIN(area) as min_area, MAX(area) as max_area FROM properties WHERE area > 0";
    if ($resArea = $mysqli->query($areaSql)) {
        $areaData = $resArea->fetch_assoc();
        if ($areaData && $areaData['min_area'] && $areaData['max_area']) {
            $areaRange['min'] = (int)$areaData['min_area'];
            $areaRange['max'] = (int)$areaData['max_area'];
        }
        $resArea->free();
    }
} catch (Throwable $e) { error_log('Area range load error: ' . $e->getMessage()); }

// Fetch properties with their images
$properties = [];

$query = "SELECT p.*, c.name as category_name,
          EXISTS (SELECT 1 FROM features f WHERE f.property_id = p.id) AS is_featured,
          (SELECT image_url FROM property_images WHERE property_id = p.id ORDER BY id ASC LIMIT 1) as main_image
          FROM properties p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'Available'";

// Add category filter if selected
if (!empty($selectedCategory)) {
    $query .= " AND c.name = '" . $mysqli->real_escape_string($selectedCategory) . "'";
}

// Add listing type filter for Types of Property (reuse existing selectedListing logic)
// This is now handled above in the listing filter section

// Add city filter if selected (match against location and landmark)
if (!empty($selectedCity)) {
    $city = $mysqli->real_escape_string($selectedCity);
    $query .= " AND (p.location = '" . $city . "' OR p.location LIKE '%" . $city . "%' OR p.landmark LIKE '%" . $city . "%')";
}

// Add listing type filter (Buy / Rent / PG/Co-living)
if (!empty($selectedListing)) {
    $listing = $mysqli->real_escape_string($selectedListing);
    $query .= " AND p.listing_type = '" . $listing . "'";
}

// Add bedroom configuration filter
if (!empty($selectedBedrooms)) {
    $query .= " AND p.configuration = '" . $mysqli->real_escape_string($selectedBedrooms) . "'";
}

// Add furnished status filter
if (!empty($selectedFurnished)) {
    $query .= " AND p.furniture_status = '" . $mysqli->real_escape_string($selectedFurnished) . "'";
}

// Construction status filter removed

// Add locality filter (match against location and landmark)
if (!empty($selectedLocalities)) {
    $locality = $mysqli->real_escape_string($selectedLocalities);
    $query .= " AND (p.location LIKE '%" . $locality . "%' OR p.landmark LIKE '%" . $locality . "%')";
}

// Add price range filter
if ($minPrice > 0) {
    $query .= " AND p.price >= " . $minPrice;
}
if ($maxPrice > 0) {
    $query .= " AND p.price <= " . $maxPrice;
}

// Add area range filter
if ($minArea > 0) {
    $query .= " AND p.area >= " . $minArea;
}
if ($maxArea > 0) {
    $query .= " AND p.area <= " . $maxArea;
}

// Add featured-only filter if requested
if ($isFeaturedOnly) {
    $query .= " AND EXISTS (SELECT 1 FROM features f WHERE f.property_id = p.id)";
}

$query .= " ORDER BY p.created_at DESC";

// Pagination settings
$propertiesPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $propertiesPerPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM properties p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'Available'";

// Add the same filters to count query
if (!empty($selectedCategory)) {
    $countQuery .= " AND c.name = '" . $mysqli->real_escape_string($selectedCategory) . "'";
}

if (!empty($selectedCity)) {
    $city = $mysqli->real_escape_string($selectedCity);
    $countQuery .= " AND (p.location = '" . $city . "' OR p.location LIKE '%" . $city . "%' OR p.landmark LIKE '%" . $city . "%')";
}

if (!empty($selectedListing)) {
    $listing = $mysqli->real_escape_string($selectedListing);
    $countQuery .= " AND p.listing_type = '" . $listing . "'";
}

// PropertyType filter removed - now handled by listing parameter

if (!empty($selectedBedrooms)) {
    $countQuery .= " AND p.configuration = '" . $mysqli->real_escape_string($selectedBedrooms) . "'";
}

if (!empty($selectedFurnished)) {
    $countQuery .= " AND p.furniture_status = '" . $mysqli->real_escape_string($selectedFurnished) . "'";
}

if (!empty($selectedLocalities)) {
    $locality = $mysqli->real_escape_string($selectedLocalities);
    $countQuery .= " AND (p.location LIKE '%" . $locality . "%' OR p.landmark LIKE '%" . $locality . "%')";
}

if ($minPrice > 0) {
    $countQuery .= " AND p.price >= " . $minPrice;
}
if ($maxPrice > 0) {
    $countQuery .= " AND p.price <= " . $maxPrice;
}

if ($minArea > 0) {
    $countQuery .= " AND p.area >= " . $minArea;
}
if ($maxArea > 0) {
    $countQuery .= " AND p.area <= " . $maxArea;
}

if ($isFeaturedOnly) {
    $countQuery .= " AND EXISTS (SELECT 1 FROM features f WHERE f.property_id = p.id)";
}

$totalProperties = 0;
if ($countResult = $mysqli->query($countQuery)) {
    $countRow = $countResult->fetch_assoc();
    $totalProperties = isset($countRow['total']) ? (int)$countRow['total'] : 0;
    $countResult->free();
}

// Add pagination to main query
$query .= " LIMIT $propertiesPerPage OFFSET $offset";

$result = $mysqli->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}


// Function to format price in Lakhs
function formatPrice($price) {
    if ($price >= 100000) {
        return '₹' . round($price / 100000, 1) . ' L';
    }
    return '₹' . number_format($price);
}

// Function to calculate time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    return floor($time/31536000) . 'y ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <?php require_once __DIR__ . '/../config/seo_config.php'; echo SEOConfig::generateFaviconTags(); ?>
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
  <link rel="stylesheet" href="../assets/css/product.css" />
  
  <style>
    /* Enhanced dropdown interactions */
  </style>
  <script src="../assets/js/custom-dropdown.js" defer></script>
</head>
<body class="article-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

    <div class="container">
      <div class="row">
        <div class="nav-tabs-custom mx-auto justify-content-evenly flex-wrap gap-1">
          <button class="<?php echo ($selectedListing === 'Buy') ? 'active' : ''; ?>" type="button" onclick="filterByListing('Buy')">Buy</button>
          <button class="<?php echo ($selectedListing === 'Rent') ? 'active' : ''; ?>" type="button" onclick="filterByListing('Rent')">Rent</button>
          <button class="<?php echo (empty($selectedListing) && $selectedCategory === 'Plot') ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Plot')">Plot</button>
          <button class="<?php echo (empty($selectedListing) && $selectedCategory === 'Commercial') ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Commercial')">Commercial</button>
          <button class="<?php echo ($selectedListing === 'PG/Co-living') ? 'active' : ''; ?>" type="button" onclick="filterByListing('PG/Co-living')">PG/Co-living</button>
          <button class="<?php echo (empty($selectedListing) && $selectedCategory === '1BHK/Studio') ? 'active' : ''; ?>" type="button" onclick="filterByCategory('1BHK/Studio')">1BHK/Studio</button>
        </div>
      </div>

      <!-- Select city with enhanced UI -->
      <div class="custom-select-wrapper">
        <select class="custom-select" name="city" id="city-select" aria-label="Select city" onchange="onCityChange(this.value)">
          <option value="" <?php echo $selectedCity === '' ? 'selected' : ''; ?>>All Cities</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?php echo htmlspecialchars($city['name']); ?>" <?php echo $selectedCity === $city['name'] ? 'selected' : ''; ?> data-city-id="<?php echo (int)$city['id']; ?>">
              <?php echo htmlspecialchars($city['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

  <!-- Mobile Filters toggle button -->
  <div class="container d-md-none">
    <div class="d-flex justify-content-end">
      <button type="button" class="filter-toggle-btn" aria-controls="filterSidebar" aria-expanded="false">Filters</button>
    </div>
  </div>
    </div>
  

  <!-- Property Listing Section -->
  <section class="property-listing-section container-fluid ">
    <div class="container">
      <div class="row d-flex justify-content-center gx-4">

      <!-- Sidebar Filters -->
      <div class="col-lg-4 col-md-5">
       
        <aside class="filter-sidebar" id="filterSidebar" aria-label="Property filters">
    <button type="button" class="filter-close-btn d-md-none" aria-label="Close filters">✕</button>

    <!-- Applied Filters + Clear All -->
    <div class="filter-header">
      <h2>Applied Filters</h2>
      <button class="clear-btn" id="clearAllBtn">Clear All</button>
    </div>

    <!-- Applied filters tags container - Hidden as we now use active states on filter items -->
    <div class="applied-filters" id="appliedFiltersContainer" style="display: none;">
      <!-- Filter tags are now shown as active states on the filter items themselves -->
    </div>

    <!-- Budget Section -->
    <section class="filter-section" id="budgetSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="budgetSection">
        Budget
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
          <div class="double-range" id="doubleRange1">
    <input type="range" id="minRange1" min="<?php echo $priceRange['min']; ?>" max="<?php echo $priceRange['max']; ?>" step="10000" value="<?php echo $minPrice > 0 ? $minPrice : $priceRange['min']; ?>" />
    <input type="range" id="maxRange1" min="<?php echo $priceRange['min']; ?>" max="<?php echo $priceRange['max']; ?>" step="10000" value="<?php echo $maxPrice > 0 ? $maxPrice : $priceRange['max']; ?>" />
    <div class="slider-range" id="sliderRange1"></div>
  </div>
  <div class="range-labels" id="rangeLabels1">
    <span id="minLabel1"><?php 
      $minVal = $minPrice > 0 ? $minPrice : $priceRange['min'];
      if ($minVal >= 100000) {
        echo '₹' . round($minVal / 100000) . 'L';
      } else if ($minVal >= 1000) {
        echo '₹' . round($minVal / 1000) . 'K';
      } else {
        echo '₹' . $minVal;
      }
    ?></span>
    <span id="maxLabel1"><?php 
      $maxVal = $maxPrice > 0 ? $maxPrice : $priceRange['max'];
      if ($maxVal >= 100000) {
        echo '₹' . round($maxVal / 100000) . 'L';
      } else if ($maxVal >= 1000) {
        echo '₹' . round($maxVal / 1000) . 'K';
      } else {
        echo '₹' . $maxVal;
      }
    ?></span>
  </div>


      </div>
    </section>

    <!-- Types of property -->
    <section class="filter-section" id="listingSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="listingSection">
        Types of property
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list" id="listingTags">
          <?php foreach ($listingTypes as $listingType): ?>
            <div class="tag <?php echo ($selectedListing === $listingType) ? 'active' : ''; ?>" data-filter="listing" data-value="<?php echo htmlspecialchars($listingType); ?>">
              <?php echo htmlspecialchars($listingType); ?> <span class="add-icon">+</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Category Section -->
    <section class="filter-section" id="categorySection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="categorySection">
        Category
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list" id="categoryTags">
          <?php foreach ($categories as $category): ?>
            <div class="tag <?php echo ($selectedCategory === $category['name']) ? 'active' : ''; ?>" data-filter="category" data-value="<?php echo htmlspecialchars($category['name']); ?>">
              <?php echo htmlspecialchars($category['name']); ?> <span class="add-icon">+</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Furnished Status -->
    <section class="filter-section<?php echo ($selectedCategory === 'Plot') ? ' disabled' : ''; ?>" id="furnishedSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="furnishedSection">
        Furnished Status
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list" id="furnishedTags">
          <?php foreach ($furnishedStatuses as $status): ?>
            <div class="tag <?php echo ($selectedFurnished === $status) ? 'active' : ''; ?>" data-filter="furnished" data-value="<?php echo htmlspecialchars($status); ?>">
              <?php echo htmlspecialchars($status); ?> <span class="add-icon">+</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- No of Bedrooms -->
    <section class="filter-section<?php echo ($selectedCategory === 'Plot') ? ' disabled' : ''; ?>" id="bedroomsSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="bedroomsSection">
        No of Bedrooms
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list bedroom-tags" id="bedroomTags">
          <?php foreach ($configurations as $config): ?>
            <div class="tag <?php echo ($selectedBedrooms === $config) ? 'active' : ''; ?>" data-filter="bedrooms" data-value="<?php echo htmlspecialchars($config); ?>">
              <span class="add-icon">+</span> <?php echo htmlspecialchars($config); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    

    <!-- Area Section -->
    <section class="filter-section" id="areaSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="areaSection">
        Area
        <span class="caret"></span>
      </div>
       <div class="filter-section-content">
          <div class="double-range" id="doubleRange2">
    <input type="range" id="minRange2" min="<?php echo $areaRange['min']; ?>" max="<?php echo $areaRange['max']; ?>" step="50" value="<?php echo $minArea > 0 ? $minArea : $areaRange['min']; ?>" />
    <input type="range" id="maxRange2" min="<?php echo $areaRange['min']; ?>" max="<?php echo $areaRange['max']; ?>" step="50" value="<?php echo $maxArea > 0 ? $maxArea : $areaRange['max']; ?>" />
    <div class="slider-track"></div>
    <div class="slider-range" id="sliderRange2"></div>
  </div>
  <div class="range-labels" id="rangeLabels2">
    <span id="minLabel2"><?php 
      $minAreaVal = $minArea > 0 ? $minArea : $areaRange['min'];
      if ($minAreaVal >= 1000) {
        echo round($minAreaVal / 1000) . 'K sq.ft';
      } else {
        echo $minAreaVal . ' sq.ft';
      }
    ?></span>
    <span id="maxLabel2"><?php 
      $maxAreaVal = $maxArea > 0 ? $maxArea : $areaRange['max'];
      if ($maxAreaVal >= 1000) {
        echo round($maxAreaVal / 1000) . 'K sq.ft';
      } else {
        echo $maxAreaVal . ' sq.ft';
      }
    ?></span>
  </div>


      </div>
    </section>

    <!-- Localities Section -->
    <section class="filter-section" id="localitiesSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="localitiesSection">
        Localities
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="locality-list" id="localitiesList">
          <?php 
          $displayCount = 5; // Show first 5 localities
          $totalLocalities = count($localities);
          for ($i = 0; $i < min($displayCount, $totalLocalities); $i++): 
            $locality = $localities[$i];
            $isChecked = ($selectedLocalities === $locality['name']);
          ?>
            <label>
              <input type="checkbox" data-filter="localities" value="<?php echo htmlspecialchars($locality['name']); ?>" <?php echo $isChecked ? 'checked' : ''; ?> /> 
              <?php echo htmlspecialchars($locality['name']); ?>
            </label>
          <?php endfor; ?>
        </div>
        <?php if ($totalLocalities > $displayCount): ?>
          <div class="more-localities" id="toggleMoreLocalities">+ More Localities</div>
          <div class="locality-list" id="extraLocalities" style="display:none; margin-top: 12px;">
            <?php for ($i = $displayCount; $i < $totalLocalities; $i++): 
              $locality = $localities[$i];
              $isChecked = ($selectedLocalities === $locality['name']);
            ?>
              <label>
                <input type="checkbox" data-filter="localities" value="<?php echo htmlspecialchars($locality['name']); ?>" <?php echo $isChecked ? 'checked' : ''; ?> /> 
                <?php echo htmlspecialchars($locality['name']); ?>
              </label>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
    
    <!-- Apply Filter Button inside filter card -->
    <div class="filter-section" id="applyFilterSection">
      <button type="button" class="apply-filter-btn-card" id="applyAllFiltersCard" disabled>Apply Filter</button>
    </div>
    
  </aside>  
      </div>

      <!-- Property Results -->
      <div  class="col-lg-8 col-md-7 aproperty" id="results">
        <?php 
          $hasAnyFilter = (
            !empty($selectedCategory) || !empty($selectedCity) || !empty($selectedPropertyType) ||
            !empty($selectedBedrooms) || !empty($selectedLocalities) ||
            ($minPrice > 0) || ($maxPrice > 0) || ($minArea > 0) || ($maxArea > 0) || $isFeaturedOnly
          );
          if (count($properties) > 0 && $hasAnyFilter):
        ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h2 class="m-0">
            <?php echo count($properties); ?> results | 
            <?php echo !empty($selectedCategory) ? $selectedCategory . ' Properties' : 'All Properties'; ?>
            <?php if ($selectedCity !== ''): ?>
              in <span class="highlight-city"><?php echo htmlspecialchars($selectedCity); ?></span>
            <?php endif; ?>
            for Sale
          </h2>
        </div>
        <?php endif; ?>

        <div class="aproperty-cards">
          <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
              <div class="aproperty-card" data-property-id="<?php echo (int)$property['id']; ?>" style="display: flex; align-items: center; gap: 20px; cursor: pointer;" onclick="goToPropertyDetails(<?php echo $property['id']; ?>)">
                <div style="position: relative; flex-shrink: 0;">
                  <img src="<?php echo !empty($property['main_image']) ? '../uploads/properties/' . $property['main_image'] : ''; ?>" 
                       alt="<?php echo htmlspecialchars($property['title']); ?>" class="property-image" 
                       style="width: 300px; height: 228px; object-fit: cover; border-radius: 8px;" />
                  <span class="listing-type-badge" style="position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.35); color:rgb(0, 0, 0); padding: 6px 12px; border: 1px solid rgba(0, 0, 0, 0.55); border-radius: 20px; font-size: 12px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.12); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                    <?php echo htmlspecialchars($property['listing_type']); ?>
                  </span>
                </div>
                <div class="property-info" style="flex: 1;">
                  <h3><?php echo htmlspecialchars($property['title']); ?> <br>
                  <span><?php echo htmlspecialchars($property['configuration']); ?> House in <?php echo htmlspecialchars($property['location']); ?></span></h3>
                  <p><?php echo htmlspecialchars($property['description']); ?></p>
                  <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                    <span><?php echo htmlspecialchars($property['configuration']); ?> <?php echo formatPrice($property['price']); ?></span>
                    <span><?php echo number_format($property['area']); ?> sq.ft Builtup area</span>
                    <span><?php echo htmlspecialchars($property['furniture_status']); ?> Possession status</span>
                  </div>
                  <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                    <p class="property-time"><?php echo timeAgo($property['created_at']); ?></p>
                      <div class="btn-grp" style="display: flex; gap: 10px;">
                          <button
                            class="btn btn-share"
                            onclick="event.stopPropagation(); sharePropertyFromBtn(this, <?php echo $property['id']; ?>)"
                            data-id="<?php echo (int)$property['id']; ?>"
                            data-title="<?php echo htmlspecialchars($property['title']); ?>"
                            data-desc="<?php echo htmlspecialchars($property['description']); ?>"
                            data-config="<?php echo htmlspecialchars($property['configuration']); ?>"
                            data-price="<?php echo formatPrice($property['price']); ?>"
                            data-area="<?php echo number_format($property['area']); ?>"
                            data-furniture="<?php echo htmlspecialchars($property['furniture_status']); ?>"
                            data-location="<?php echo htmlspecialchars($property['location']); ?>"
                            data-image="<?php echo !empty($property['main_image']) ? '../uploads/properties/' . $property['main_image'] : ''; ?>"
                          >Share</button>
                        <button class="btn btn-contact" onclick="event.stopPropagation(); contactProperty(<?php echo $property['id']; ?>)">Contact us</button>
                        <button class="btn btn-whatsapp btn-contact" onclick="event.stopPropagation(); whatsappProperty(<?php echo $property['id']; ?>)" 
                          data-title="<?php echo htmlspecialchars($property['title']); ?>"
                          data-desc="<?php echo htmlspecialchars($property['description']); ?>"
                          data-config="<?php echo htmlspecialchars($property['configuration']); ?>"
                          data-price="<?php echo formatPrice($property['price']); ?>"
                          data-area="<?php echo number_format($property['area']); ?>"
                          data-furniture="<?php echo htmlspecialchars($property['furniture_status']); ?>"
                          data-location="<?php echo htmlspecialchars($property['location']); ?>"
                          style="background-color: #25D366; color: white; border: none; padding: 8px 16px; border-radius: 20px; display: flex; align-items: center; gap: 6px;">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                          </svg>
                          Talk to Us
                        </button>
                      </div>
                  </div>
               </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-properties" style="text-align: center; padding: 40px;">
              <h3>No properties found</h3>
              <p>Try adjusting your filters to see more results.</p>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Property Pagination Navigation -->
        <?php if ($totalProperties > $propertiesPerPage): ?>
          <div class="property-pagination">
            <div class="pagination-arrows">
              <?php if ($currentPage > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" 
                   class="pagination-arrow prev-arrow">
                  ←
                </a>
              <?php else: ?>
                <span class="pagination-arrow disabled">
                  ←
                </span>
              <?php endif; ?>
              
              <?php 
              $totalPages = ceil($totalProperties / $propertiesPerPage);
              if ($currentPage < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" 
                   class="pagination-arrow next-arrow">
                  →
                </a>
              <?php else: ?>
                <span class="pagination-arrow disabled">
                  →
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
      </div>
      </div>
    </div>
    
  </section>

  <!-- Backdrop for mobile filters -->
  <div class="filter-backdrop d-md-none" hidden></div>

  

 <!-- contact  -->
    <?php include '../components/letsconnect.php'; ?>


    
  <!-- about section  -->
<?php include '../components/about.php'; ?>


<?php include '../components/footer.php'; ?>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js" defer></script>

<script>
 function filterByListing(listing) {
     const url = new URL(window.location);
     // Toggle: clicking active clears it
     const current = url.searchParams.get('listing');
     
     // Clear all other nav button filters first (mutual exclusivity)
     url.searchParams.delete('category');
     
     if (current === listing) {
         url.searchParams.delete('listing');
     } else {
         url.searchParams.set('listing', listing);
     }
     url.searchParams.delete('page');
     window.location.href = url.toString();
 }
function filterByCategory(category) {
    const url = new URL(window.location);
    const current = url.searchParams.get('category');
    
    // Clear all other nav button filters first (mutual exclusivity)
    url.searchParams.delete('listing');
    
    // Toggle: if already active, remove the filter
    if (current === category) {
        url.searchParams.delete('category');
    } else {
        url.searchParams.set('category', category);
    }
    window.location.href = url.toString();
}

// Function to sync nav-tabs-custom with property type filters (no longer needed since sidebar now uses listing_type)

// Function to sync property type filters with nav-tabs-custom (no longer needed since sidebar now uses listing_type)

function onCityChange(city) {
    const url = new URL(window.location);
    if (city) {
        url.searchParams.set('city', city);
    } else {
        url.searchParams.delete('city');
    }
    window.location.href = url.toString();
}

function goToPropertyDetails(propertyId) {
    // Generate random characters for the URL
    const randomChars = generateRandomString(12);
    // Navigate to product-details.php with property ID and random characters
    window.location.href = 'product-details.php?id=' + propertyId + '&share=' + randomChars;
}

function generateRandomString(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

async function shareProperty(propertyId) {
    try {
        // Build proper absolute URL for sharing
        const protocol = window.location.protocol;
        const host = window.location.host;
        const detailsUrl = `${protocol}//${host}/products/product-details.php?id=${propertyId}`;
        
        // Try to find the card to extract richer info and image
        const card = document.querySelector(`.aproperty-card[data-property-id="${propertyId}"]`);
        let title = 'Property Details';
        let desc = '';
        let config = '';
        let price = '';
        let area = '';
        let furniture = '';
        let location = '';
        let imageUrl = '';
        
        if (card) {
            const titleEl = card.querySelector('h3');
            const subtitleEl = titleEl ? titleEl.querySelector('span') : null;
            const descEl = card.querySelector('p');
            const detailsSpans = card.querySelectorAll('.property-details span');
            const imgEl = card.querySelector('img.property-image');
            
            title = titleEl ? (titleEl.childNodes[0]?.textContent || titleEl.textContent || title) : title;
            const subtitle = subtitleEl ? subtitleEl.textContent.trim() : '';
            desc = descEl ? descEl.textContent.trim() : '';
            const confPrice = detailsSpans[0] ? detailsSpans[0].textContent.trim() : '';
            const areaTxt = detailsSpans[1] ? detailsSpans[1].textContent.trim() : '';
            const possTxt = detailsSpans[2] ? detailsSpans[2].textContent.trim() : '';
            imageUrl = imgEl ? imgEl.getAttribute('src') || '' : '';
            
            // Extract location from subtitle
            if (subtitle && subtitle.includes('in ')) {
                location = subtitle.split('in ')[1] || '';
            }
            
            // Extract configuration and price from confPrice
            if (confPrice) {
                const parts = confPrice.split(' ');
                if (parts.length >= 2) {
                    config = parts[0];
                    price = parts.slice(1).join(' ');
                }
            }
            
            // Extract area from areaTxt
            if (areaTxt) {
                area = areaTxt.replace(' sq.ft Builtup area', '');
            }
            
            // Extract furniture from possTxt
            if (possTxt) {
                furniture = possTxt.replace(' Possession status', '');
            }
        }

        // Build share text with emojis for better visual appeal
        const shareText = `${title}\n\n` +
            (location ? `📍 ${location}\n` : '') +
            (config ? `🏠 ${config}\n` : '') +
            (price ? `💰 ${price}\n` : '') +
            (area ? `📐 ${area} sq.ft\n` : '') +
            (furniture ? `🛋️ ${furniture}\n` : '') +
            (desc ? `\n${desc}\n` : '') +
            `\n👉 View details: ${detailsUrl}`;

        // Try Web Share API (works on mobile)
        if (navigator.share) {
            try {
                // Try sharing with image file
                if (imageUrl) {
                    const absImageUrl = imageUrl.startsWith('http') ? imageUrl : `${protocol}//${host}${imageUrl}`;
                    
                    try {
                        const response = await fetch(absImageUrl);
                        const blob = await response.blob();
                        const file = new File([blob], 'property.jpg', { type: blob.type });
                        
                        // Check if we can share files
                        if (navigator.canShare && navigator.canShare({ files: [file] })) {
                            await navigator.share({
                                title: title,
                                text: shareText,
                                files: [file]
                            });
                            return;
                        }
                    } catch (e) {
                        console.log('Image sharing not supported, falling back to URL share');
                    }
                }
                
                // Fallback: share without image
                await navigator.share({
                    title: title,
                    text: shareText,
                    url: detailsUrl
                });
                return;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    throw err;
                }
                return; // User cancelled
            }
        }

        // Fallback for desktop: copy to clipboard
        await navigator.clipboard.writeText(shareText);
        alert('Property details copied. Paste to share!');
        
    } catch (err) {
        console.error('Share failed:', err);
        // Last fallback: open in new tab
        try { 
            window.open(`/products/product-details.php?id=${propertyId}`, '_blank'); 
        } catch (_) {}
    }
}

function contactProperty(propertyId) {
    // Call the phone number directly
    const phoneNumber = '99018 05505';
    window.location.href = 'tel:' + phoneNumber;
}

function whatsappProperty(propertyId) {
    const phoneNumber = '919901805505'; // WhatsApp number without + and spaces
    const btn = event.target.closest('.btn-whatsapp');
    const propertyTitle = btn.getAttribute('data-title') || 'Property';
    const propertyPrice = btn.getAttribute('data-price') || '';
    const propertyConfig = btn.getAttribute('data-config') || '';
    const propertyArea = btn.getAttribute('data-area') || '';
    const propertyFurniture = btn.getAttribute('data-furniture') || '';
    const propertyLocation = btn.getAttribute('data-location') || '';
    const propertyDesc = btn.getAttribute('data-desc') || '';
    const propertyUrl = window.location.origin + '/products/product-details.php?id=' + propertyId;
    
    const message = `Hi! I'm interested in this property:

🏠 *${propertyTitle}*
💰 Price: ${propertyPrice}
🏘️ Configuration: ${propertyConfig}
📐 Area: ${propertyArea} sq.ft
🛋️ Furniture: ${propertyFurniture}
📍 Location: ${propertyLocation}

${propertyDesc.length > 200 ? propertyDesc.substring(0, 200) + '...' : propertyDesc}

View details: ${propertyUrl}

Please provide more information about this property.`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
    
    window.open(whatsappUrl, '_blank');
}

// Enhanced sharing function with proper image handling
async function sharePropertyFromBtn(btn, propertyId) {
    try {
        const title = btn.getAttribute('data-title') || 'Property';
        const desc = btn.getAttribute('data-desc') || '';
        const config = btn.getAttribute('data-config') || '';
        const price = btn.getAttribute('data-price') || '';
        const area = btn.getAttribute('data-area') || '';
        const furniture = btn.getAttribute('data-furniture') || '';
        const location = btn.getAttribute('data-location') || '';
        const imageUrl = btn.getAttribute('data-image') || '';

        // Build proper absolute URL for sharing
        const protocol = window.location.protocol;
        const host = window.location.host;
        const detailsUrl = `${protocol}//${host}/products/product-details.php?id=${propertyId}`;

        // Build share text with emojis for better visual appeal
        const shareText = `${title}\n\n` +
            (location ? `📍 ${location}\n` : '') +
            (config ? `🏠 ${config}\n` : '') +
            (price ? `💰 ${price}\n` : '') +
            (area ? `📐 ${area} sq.ft\n` : '') +
            (furniture ? `🛋️ ${furniture}\n` : '') +
            (desc ? `\n${desc}\n` : '') +
            `\n👉 View details: ${detailsUrl}`;

        // Try Web Share API (works on mobile)
        if (navigator.share) {
            try {
                // Try sharing with image file
                if (imageUrl) {
                    const absImageUrl = imageUrl.startsWith('http') ? imageUrl : `${protocol}//${host}${imageUrl}`;
                    
                    try {
                        const response = await fetch(absImageUrl);
                        const blob = await response.blob();
                        const file = new File([blob], 'property.jpg', { type: blob.type });
                        
                        // Check if we can share files
                        if (navigator.canShare && navigator.canShare({ files: [file] })) {
                            await navigator.share({
                                title: title,
                                text: shareText,
                                files: [file]
                            });
                            return;
                        }
                    } catch (e) {
                        console.log('Image sharing not supported, falling back to URL share');
                    }
                }
                
                // Fallback: share without image
                await navigator.share({
                    title: title,
                    text: shareText,
                    url: detailsUrl
                });
                return;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    throw err;
                }
                return; // User cancelled
            }
        }

        // Fallback for desktop: copy to clipboard
        await navigator.clipboard.writeText(shareText);
        
        // Show feedback
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.backgroundColor = '#10b981';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.backgroundColor = '';
        }, 2000);
        
    } catch (err) {
        console.error('Share failed:', err);
        // Last fallback: open in new tab
        window.open(`/products/product-details.php?id=${propertyId}`, '_blank');
    }
}

// Add click handlers for better UX
document.addEventListener('DOMContentLoaded', function() {
    const categoryButtons = document.querySelectorAll('.nav-tabs-custom button');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
        });
    });

    // Initialize filter functionality
    syncUIFromParams();
    initializeFilters();
    updateApplyButtonState();
    
    // Initialize dropdown interactions
    initializeDropdowns();
    
    // Note: Property type filters now work independently (listing_type based)

});

// Initial states function removed - property type filters now work independently with listing_type

// Filter functionality
function initializeFilters() {
    // Handle listing type filter tags (do NOT navigate immediately; apply on Apply Filter)
    const listingTags = document.querySelectorAll('[data-filter="listing"]');
    listingTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            const value = this.getAttribute('data-value');
            // Detect cross (remove) click region
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;
            const isCrossClick = clickX > (rect.width - 35) && clickY > (rect.height / 2 - 10) && clickY < (rect.height / 2 + 10);

            if (isCrossClick && this.classList.contains('active')) {
                this.classList.remove('active');
                updateApplyButtonState();
                return;
            }

            // Single-select behavior: one active at a time
            document.querySelectorAll('[data-filter="listing"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            updateApplyButtonState();
        });
    });

    // Handle category filter tags (do NOT navigate immediately; apply on Apply Filter)
    const categoryTags = document.querySelectorAll('[data-filter="category"]');
    categoryTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            const value = this.getAttribute('data-value');
            // Detect cross (remove) click region
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;
            const isCrossClick = clickX > (rect.width - 35) && clickY > (rect.height / 2 - 10) && clickY < (rect.height / 2 + 10);

            if (isCrossClick && this.classList.contains('active')) {
                this.classList.remove('active');
                updateApplyButtonState();
                return;
            }

            // Single-select behavior: one active at a time
            document.querySelectorAll('[data-filter="category"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Disable furnished and bedrooms when category is Plot
            const isPlot = (value === 'Plot');
            toggleFurnishedBedroomsDisabled(isPlot);
            updateApplyButtonState();
        });
    });

    // Handle furnished filter tags (do NOT navigate immediately)
    const furnishedTags = document.querySelectorAll('[data-filter="furnished"]');
    furnishedTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            // Prevent selection when disabled (Plot selected)
            if (document.getElementById('furnishedSection').classList.contains('disabled')) {
                e.preventDefault();
                return;
            }
            const value = this.getAttribute('data-value');
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;
            const isCrossClick = clickX > (rect.width - 35) && clickY > (rect.height / 2 - 10) && clickY < (rect.height / 2 + 10);

            if (isCrossClick && this.classList.contains('active')) {
                this.classList.remove('active');
                updateApplyButtonState();
                return;
            }

            document.querySelectorAll('[data-filter="furnished"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            updateApplyButtonState();
        });
    });

    // Handle bedroom filter tags (do NOT navigate immediately)
    const bedroomTags = document.querySelectorAll('[data-filter="bedrooms"]');
    bedroomTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            // Prevent selection when disabled (Plot selected)
            if (document.getElementById('bedroomsSection').classList.contains('disabled')) {
                e.preventDefault();
                return;
            }
            const value = this.getAttribute('data-value');
            // Check if cross button was clicked
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;
            const isCrossClick = clickX > (rect.width - 35) && clickY > (rect.height / 2 - 10) && clickY < (rect.height / 2 + 10);
            
            if (isCrossClick && this.classList.contains('active')) {
                this.classList.remove('active');
                updateApplyButtonState();
                return;
            }
            
            // Single-select behavior; apply only on Apply Filter
            document.querySelectorAll('#bedroomsSection .tag').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            updateApplyButtonState();
        });
    });

    // Construction status removed

    // Handle locality checkboxes (do NOT navigate immediately)
    const localityCheckboxes = document.querySelectorAll('[data-filter="localities"]');
    localityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateApplyButtonState();
        });
    });

    // Handle range sliders
    initializeRangeSliders();
}

function applyFilter(filterType, value) {
    const url = new URL(window.location);
    url.searchParams.set(filterType, value);
    window.location.href = url.toString();
}

function removeFilter(filterType, value) {
    const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
    if (isMobile) {
        // Only update UI on mobile; commit on Apply
        setFilterActiveState(filterType, value, false);
        updateApplyButtonState();
        return;
    }
    const url = new URL(window.location);
    url.searchParams.delete(filterType);
    window.location.href = url.toString();
}

function initializeRangeSliders() {
    // Budget range slider
    const minRange1 = document.getElementById('minRange1');
    const maxRange1 = document.getElementById('maxRange1');
    const minLabel1 = document.getElementById('minLabel1');
    const maxLabel1 = document.getElementById('maxLabel1');
    const sliderRange1 = document.getElementById('sliderRange1');

    function updateSliderVisual(minInput, maxInput, sliderEl) {
        if (!minInput || !maxInput || !sliderEl) return;
        const absMin = parseInt(minInput.min || '0', 10);
        const absMax = parseInt(maxInput.max || '100', 10);
        const curMin = Math.min(parseInt(minInput.value || String(absMin), 10), parseInt(maxInput.value || String(absMax), 10));
        const curMax = Math.max(parseInt(maxInput.value || String(absMax), 10), curMin);
        const span = absMax - absMin;
        if (span <= 0) { sliderEl.style.left = '0%'; sliderEl.style.width = '0%'; return; }
        const leftPct = ((curMin - absMin) / span) * 100;
        const rightPct = ((curMax - absMin) / span) * 100;
        sliderEl.style.left = leftPct + '%';
        sliderEl.style.width = Math.max(0, rightPct - leftPct) + '%';
    }

    if (minRange1 && maxRange1) {
        minRange1.addEventListener('input', function() {
            if (parseInt(this.value) >= parseInt(maxRange1.value)) {
                this.value = maxRange1.value;
            }
            minLabel1.textContent = formatPriceLabel(parseInt(this.value));
            // Don't apply filter immediately, just update the label
            updateApplyButtonState();
            updateSliderVisual(minRange1, maxRange1, sliderRange1);
        });

        maxRange1.addEventListener('input', function() {
            if (parseInt(this.value) <= parseInt(minRange1.value)) {
                this.value = minRange1.value;
            }
            maxLabel1.textContent = formatPriceLabel(parseInt(this.value));
            // Don't apply filter immediately, just update the label
            updateApplyButtonState();
            updateSliderVisual(minRange1, maxRange1, sliderRange1);
        });
        // Initial position
        updateSliderVisual(minRange1, maxRange1, sliderRange1);
    }

    // Area range slider
    const minRange2 = document.getElementById('minRange2');
    const maxRange2 = document.getElementById('maxRange2');
    const minLabel2 = document.getElementById('minLabel2');
    const maxLabel2 = document.getElementById('maxLabel2');
    const sliderRange2 = document.getElementById('sliderRange2');

    if (minRange2 && maxRange2) {
        minRange2.addEventListener('input', function() {
            if (parseInt(this.value) >= parseInt(maxRange2.value)) {
                this.value = maxRange2.value;
            }
            minLabel2.textContent = formatAreaLabel(parseInt(this.value));
            // Don't apply filter immediately, just update the label
            updateApplyButtonState();
            updateSliderVisual(minRange2, maxRange2, sliderRange2);
        });

        maxRange2.addEventListener('input', function() {
            if (parseInt(this.value) <= parseInt(minRange2.value)) {
                this.value = minRange2.value;
            }
            maxLabel2.textContent = formatAreaLabel(parseInt(this.value));
            // Don't apply filter immediately, just update the label
            updateApplyButtonState();
            updateSliderVisual(minRange2, maxRange2, sliderRange2);
        });
        // Initial position
        updateSliderVisual(minRange2, maxRange2, sliderRange2);
    }
}

function applyRangeFilter(filterType, value) {
    const url = new URL(window.location);
    url.searchParams.set(filterType, value);
    window.location.href = url.toString();
}

// Helper functions for formatting labels
function formatPriceLabel(price) {
    if (price >= 100000) {
        return '₹' + Math.round(price / 100000) + 'L';
    } else if (price >= 1000) {
        return '₹' + Math.round(price / 1000) + 'K';
    }
    return '₹' + price;
}

function formatAreaLabel(area) {
    if (area >= 1000) {
        return Math.round(area / 1000) + 'K sq.ft';
    }
    return area + ' sq.ft';
}

// Apply button functions for range filters
function applyPriceRange() {
    const minRange1 = document.getElementById('minRange1');
    const maxRange1 = document.getElementById('maxRange1');
    
    if (minRange1 && maxRange1) {
        const url = new URL(window.location);
        
        // Set the price range parameters
        url.searchParams.set('minPrice', minRange1.value);
        url.searchParams.set('maxPrice', maxRange1.value);
        
        // Reload page with new filters
        window.location.href = url.toString();
    }
}

function applyAreaRange() {
    const minRange2 = document.getElementById('minRange2');
    const maxRange2 = document.getElementById('maxRange2');
    
    if (minRange2 && maxRange2) {
        const url = new URL(window.location);
        
        // Set the area range parameters
        url.searchParams.set('minArea', minRange2.value);
        url.searchParams.set('maxArea', maxRange2.value);
        
        // Reload page with new filters
        window.location.href = url.toString();
    }
}

// Initialize dropdown interactions
function initializeDropdowns() {
    // Handle filter section toggles
    const filterHeaders = document.querySelectorAll('.filter-section-header');
    filterHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle-target');
            const sectionEl = document.getElementById(targetId);
            if (!sectionEl) return;
            const content = sectionEl.querySelector('.filter-section-content');
            const isCollapsed = sectionEl.classList.contains('collapsed');

            // Toggle collapsed state on the section (CSS handles caret and spacing)
            sectionEl.classList.toggle('collapsed', !isCollapsed);
            this.classList.toggle('active', !isCollapsed);

            // Avoid relying on computed style empty string; set explicit display
            if (content) {
                content.style.display = isCollapsed ? 'block' : 'none';
            }
        });
    });
    
    // Handle "More Localities" toggle
    const moreLocalitiesBtn = document.getElementById('toggleMoreLocalities');
    const extraLocalities = document.getElementById('extraLocalities');
    
    if (moreLocalitiesBtn && extraLocalities) {
        moreLocalitiesBtn.addEventListener('click', function() {
            if (extraLocalities.style.display === 'none' || extraLocalities.style.display === '') {
                extraLocalities.style.display = 'block';
                this.textContent = '- Less Localities';
            } else {
                extraLocalities.style.display = 'none';
                this.textContent = '+ More Localities';
            }
        });
    }
    
    // Initialize mobile filter toggle
    const filterToggleBtn = document.querySelector('.filter-toggle-btn');
    const filterSidebar = document.getElementById('filterSidebar');
    const filterCloseBtn = document.querySelector('.filter-close-btn');
    const filterBackdrop = document.querySelector('.filter-backdrop');
    
    if (filterToggleBtn && filterSidebar) {
        filterToggleBtn.addEventListener('click', function() {
            filterSidebar.classList.add('show');
            filterBackdrop.hidden = false;
            const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
            if (isMobile) {
                document.body.style.overflow = '';
            } else {
                document.body.style.overflow = 'hidden';
            }
        });
    }
    
    if (filterCloseBtn && filterSidebar) {
        filterCloseBtn.addEventListener('click', function() {
            filterSidebar.classList.remove('show');
            filterBackdrop.hidden = true;
            document.body.style.overflow = '';
        });
    }
    
    if (filterBackdrop) {
        filterBackdrop.addEventListener('click', function() {
            filterSidebar.classList.remove('show');
            filterBackdrop.hidden = true;
            document.body.style.overflow = '';
        });
    }
    
    // Initialize Apply Filter button inside filter card
    const applyAllFiltersCardBtn = document.getElementById('applyAllFiltersCard');
    if (applyAllFiltersCardBtn) {
        applyAllFiltersCardBtn.addEventListener('click', function() {
            // Close sidebar only on mobile, but always apply filters
            const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
            if (isMobile && filterSidebar && filterBackdrop) {
                filterSidebar.classList.remove('show');
                filterBackdrop.hidden = true;
                document.body.style.overflow = '';
            }
            applyAllFilters();
        });
    }
}

function clearRangeFilter(type) {
    const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
    if (isMobile) {
        // On mobile: clear UI only, do not navigate; keep sidebar open
        if (type === 'price') {
            const minRange1 = document.getElementById('minRange1');
            const maxRange1 = document.getElementById('maxRange1');
            const minLabel1 = document.getElementById('minLabel1');
            const maxLabel1 = document.getElementById('maxLabel1');
            if (minRange1 && maxRange1) {
                // Reset to slider bounds
                minRange1.value = minRange1.min;
                maxRange1.value = maxRange1.max;
                if (minLabel1) minLabel1.textContent = formatPriceLabel(parseInt(minRange1.value));
                if (maxLabel1) maxLabel1.textContent = formatPriceLabel(parseInt(maxRange1.value));
            }
            // Remove chip if present
            removeAppliedTagByText('Budget:');
            updateApplyButtonState();
        } else if (type === 'area') {
            const minRange2 = document.getElementById('minRange2');
            const maxRange2 = document.getElementById('maxRange2');
            const minLabel2 = document.getElementById('minLabel2');
            const maxLabel2 = document.getElementById('maxLabel2');
            if (minRange2 && maxRange2) {
                minRange2.value = minRange2.min;
                maxRange2.value = maxRange2.max;
                if (minLabel2) minLabel2.textContent = formatAreaLabel(parseInt(minRange2.value));
                if (maxLabel2) maxLabel2.textContent = formatAreaLabel(parseInt(maxRange2.value));
            }
            // Remove chip if present
            removeAppliedTagByText('Area:');
            updateApplyButtonState();
        }
        return;
    }
    // Desktop: apply immediately via navigation
    const url = new URL(window.location);
    if (type === 'price') {
        url.searchParams.delete('minPrice');
        url.searchParams.delete('maxPrice');
    } else if (type === 'area') {
        url.searchParams.delete('minArea');
        url.searchParams.delete('maxArea');
    }
    window.location.href = url.toString();
}

function clearAllFilters() {
    const url = new URL(window.location);
    // Keep only essential parameters
    const keepParams = ['city', 'featured'];
    const newUrl = new URL(window.location.pathname, window.location.origin);
    
    keepParams.forEach(param => {
        if (url.searchParams.has(param)) {
            newUrl.searchParams.set(param, url.searchParams.get(param));
        }
    });
    
    window.location.href = newUrl.toString();
}

// Apply all filters function for mobile Apply Filter button
function applyAllFilters() {
    const url = new URL(window.location);
    const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;

    // Price range
    const minRange1 = document.getElementById('minRange1');
    const maxRange1 = document.getElementById('maxRange1');
    if (minRange1 && maxRange1) {
        const min1 = parseInt(minRange1.min || minRange1.value, 10);
        const max1 = parseInt(maxRange1.max || maxRange1.value, 10);
        const curMin1 = parseInt(minRange1.value, 10);
        const curMax1 = parseInt(maxRange1.value, 10);
        if (curMin1 !== min1 || curMax1 !== max1) {
            url.searchParams.set('minPrice', String(curMin1));
            url.searchParams.set('maxPrice', String(curMax1));
        } else {
            url.searchParams.delete('minPrice');
            url.searchParams.delete('maxPrice');
        }
    }

    // Area range
    const minRange2 = document.getElementById('minRange2');
    const maxRange2 = document.getElementById('maxRange2');
    if (minRange2 && maxRange2) {
        const min2 = parseInt(minRange2.min || minRange2.value, 10);
        const max2 = parseInt(maxRange2.max || maxRange2.value, 10);
        const curMin2 = parseInt(minRange2.value, 10);
        const curMax2 = parseInt(maxRange2.value, 10);
        if (curMin2 !== min2 || curMax2 !== max2) {
            url.searchParams.set('minArea', String(curMin2));
            url.searchParams.set('maxArea', String(curMax2));
        } else {
            url.searchParams.delete('minArea');
            url.searchParams.delete('maxArea');
        }
    }

    // Selected listing type (only overwrite when a selection is explicitly active in UI)
    const activeListing = document.querySelector('[data-filter="listing"].tag.active');
    if (activeListing) {
        url.searchParams.set('listing', activeListing.getAttribute('data-value'));
    } else {
        url.searchParams.delete('listing');
    }

    // Selected category (only overwrite when a selection is explicitly active in UI)
    const activeCategory = document.querySelector('[data-filter="category"].tag.active');
    if (activeCategory) {
        url.searchParams.set('category', activeCategory.getAttribute('data-value'));
    } else {
        url.searchParams.delete('category');
    }

    // Selected furnished status
    const activeFurnished = document.querySelector('[data-filter="furnished"].tag.active');
    if (activeFurnished && !document.getElementById('furnishedSection').classList.contains('disabled')) {
        url.searchParams.set('furnished', activeFurnished.getAttribute('data-value'));
    } else {
        url.searchParams.delete('furnished');
    }

    const activeBedrooms = document.querySelector('#bedroomsSection .tag.active');
    if (activeBedrooms && !document.getElementById('bedroomsSection').classList.contains('disabled')) {
        url.searchParams.set('bedrooms', activeBedrooms.getAttribute('data-value'));
    } else {
        url.searchParams.delete('bedrooms');
    }

    // construction status removed

    // Localities
    const selectedLocalities = document.querySelectorAll('input[data-filter="localities"]:checked');
    if (selectedLocalities.length > 0) {
        const localities = Array.from(selectedLocalities).map(el => el.value);
        url.searchParams.set('localities', localities.join(','));
    } else {
        url.searchParams.delete('localities');
    }

    // Jump back to results after reload on mobile
    if (isMobile) {
        url.hash = 'results';
    }

    window.location.href = url.toString();
}

// Filter active state management
function setFilterActiveState(filterType, value, isActive) {
    if (filterType === 'localities') {
        const checkbox = document.querySelector(`input[data-filter="localities"][value="${value}"]`);
        if (checkbox) {
            checkbox.checked = isActive;
        }
    } else {
        if (filterType === 'listing') {
            const tag = document.querySelector(`[data-filter="listing"].tag[data-value="${value}"]`);
            if (tag) {
                if (isActive) {
                    // Remove active from all listing tags
                    document.querySelectorAll('[data-filter="listing"].tag').forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                } else {
                    tag.classList.remove('active');
                }
            }
        } else if (filterType === 'category') {
            const tag = document.querySelector(`[data-filter="category"].tag[data-value="${value}"]`);
            if (tag) {
                if (isActive) {
                    // Remove active from all category tags
                    document.querySelectorAll('[data-filter="category"].tag').forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                } else {
                    tag.classList.remove('active');
                }
            }
        } else if (filterType === 'bedrooms') {
            const tag = document.querySelector(`#bedroomsSection .tag[data-value="${value}"]`);
            if (tag) {
                if (isActive) {
                    // Remove active from all bedroom tags
                    document.querySelectorAll('#bedroomsSection .tag').forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                } else {
                    tag.classList.remove('active');
                }
            }
        }
    }
}

// Minimal CSS.escape fallback for older browsers
function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
    return String(value).replace(/[\"\'\n\r\t\f\[\]\(\)\.#:]/g, '_');
}

// Sync initial UI selections from current URL params (needed for mobile apply)
function syncUIFromParams() {
    try {
        const url = new URL(window.location);
        const params = url.searchParams;
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) return;

        const map = [
            { key: 'listing', selector: '[data-filter="listing"]' },
            { key: 'category', selector: '[data-filter="category"]' },
            { key: 'furnished', selector: '[data-filter="furnished"]' },
            { key: 'bedrooms', section: '#bedroomsSection' }
        ];
        map.forEach(({ key, section, selector }) => {
            const val = params.get(key);
            if (!val) return;
            const target = document.querySelector((selector || section) + ' .tag[data-value="' + (window.CSS && window.CSS.escape ? CSS.escape(val) : val) + '"]');
            if (target) target.classList.add('active');
        });

        // If Plot is selected, disable furnished and bedrooms on load
        const selectedCategory = params.get('category');
        if (selectedCategory === 'Plot') {
            toggleFurnishedBedroomsDisabled(true);
        }

        const localities = params.get('localities');
        if (localities) {
            const values = localities.split(',');
            document.querySelectorAll('input[data-filter="localities"]').forEach(cb => {
                if (values.includes(cb.value)) cb.checked = true;
            });
        }
    } catch (_) {}
}

// Clear all button functionality
document.addEventListener('DOMContentLoaded', function() {
    const clearAllBtn = document.getElementById('clearAllBtn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', clearAllFilters);
    }
});

// Enable/disable Apply button based on whether there is any selection change on mobile
function updateApplyButtonState() {
    try {
        const btn = document.getElementById('applyAllFiltersCard');
        if (!btn) return;
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) { btn.disabled = false; return; }

        // Determine disabled states
        const furnishedEnabled = !document.getElementById('furnishedSection').classList.contains('disabled');
        const bedroomsEnabled = !document.getElementById('bedroomsSection').classList.contains('disabled');

        // Any active tag selections
        const hasActiveTag = (
            document.querySelector('[data-filter="listing"].tag.active, [data-filter="category"].tag.active') !== null ||
            (furnishedEnabled && document.querySelector('[data-filter="furnished"].tag.active') !== null) ||
            (bedroomsEnabled && document.querySelector('#bedroomsSection .tag.active') !== null)
        );
        // Any checked locality
        const hasCheckedLocality = document.querySelector('input[data-filter="localities"]:checked') !== null;
        // Any range moved away from defaults
        const minRange1 = document.getElementById('minRange1');
        const maxRange1 = document.getElementById('maxRange1');
        const minRange2 = document.getElementById('minRange2');
        const maxRange2 = document.getElementById('maxRange2');
        const priceChanged = !!(minRange1 && maxRange1 && (parseInt(minRange1.value) !== parseInt(minRange1.min) || parseInt(maxRange1.value) !== parseInt(maxRange1.max)));
        const areaChanged = !!(minRange2 && maxRange2 && (parseInt(minRange2.value) !== parseInt(minRange2.min) || parseInt(maxRange2.value) !== parseInt(maxRange2.max)));

        const shouldEnable = hasActiveTag || hasCheckedLocality || priceChanged || areaChanged;
        btn.disabled = !shouldEnable;
    } catch (_) {}
}

// Helper to disable/enable Furnished and Bedrooms when Plot is selected
function toggleFurnishedBedroomsDisabled(disabled) {
    const furn = document.getElementById('furnishedSection');
    const beds = document.getElementById('bedroomsSection');
    if (!furn || !beds) return;
    if (disabled) {
        furn.classList.add('disabled');
        beds.classList.add('disabled');
        // Clear any active tags visually
        document.querySelectorAll('#furnishedSection .tag.active, #bedroomsSection .tag.active').forEach(t => t.classList.remove('active'));
    } else {
        furn.classList.remove('disabled');
        beds.classList.remove('disabled');
    }
}

</script>
</body>
</html>

