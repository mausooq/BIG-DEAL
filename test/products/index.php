<?php
require_once '../config/config.php';

// Get selected filters from URL parameters
$mysqli = getMysqliConnection();
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedListing = isset($_GET['listing']) ? $_GET['listing'] : '';
$selectedCity = isset($_GET['city']) ? $_GET['city'] : '';
$isFeaturedOnly = isset($_GET['featured']) && $_GET['featured'] === '1';

// Get additional filter parameters
$selectedPropertyType = isset($_GET['propertyType']) ? $_GET['propertyType'] : '';
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

// Load categories for Types of Property filter
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

// Add property type filter (category-based)
if (!empty($selectedPropertyType)) {
    $query .= " AND c.name = '" . $mysqli->real_escape_string($selectedPropertyType) . "'";
}

// Add bedroom configuration filter
if (!empty($selectedBedrooms)) {
    $query .= " AND p.configuration = '" . $mysqli->real_escape_string($selectedBedrooms) . "'";
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

if (!empty($selectedPropertyType)) {
    $countQuery .= " AND c.name = '" . $mysqli->real_escape_string($selectedPropertyType) . "'";
}

if (!empty($selectedBedrooms)) {
    $countQuery .= " AND p.configuration = '" . $mysqli->real_escape_string($selectedBedrooms) . "'";
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
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
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
    <section class="filter-section" id="propertyTypeSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="propertyTypeSection">
        Types of property
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list" id="propertyTypeTags">
          <?php foreach ($categories as $category): ?>
            <div class="tag <?php echo ($selectedPropertyType === $category['name']) ? 'active' : ''; ?>" data-filter="propertyType" data-value="<?php echo htmlspecialchars($category['name']); ?>">
              <?php echo htmlspecialchars($category['name']); ?> <span class="add-icon">+</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- No of Bedrooms -->
    <section class="filter-section" id="bedroomsSection">
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
          <button type="button" class="btn btn-primary" onclick="shareAllProperties()">Share all</button>
        </div>
        <?php endif; ?>

        <div class="aproperty-cards">
          <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
              <div class="aproperty-card" data-property-id="<?php echo (int)$property['id']; ?>" style="display: flex; align-items: center; gap: 20px; cursor: pointer;" onclick="goToPropertyDetails(<?php echo $property['id']; ?>)">
                <img src="<?php echo !empty($property['main_image']) ? '../../uploads/properties/' . $property['main_image'] : ''; ?>" 
                     alt="<?php echo htmlspecialchars($property['title']); ?>" class="property-image" 
                     style="width: 300px; height: 228px; object-fit: cover; border-radius: 8px; flex-shrink: 0;" />
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
                            data-image="<?php echo !empty($property['main_image']) ? '../../uploads/properties/' . $property['main_image'] : ''; ?>"
                          >Share</button>
                        <button class="btn btn-contact" onclick="event.stopPropagation(); contactProperty(<?php echo $property['id']; ?>)">Contact us</button>
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
     if (current === listing) {
         url.searchParams.delete('listing');
     } else {
         url.searchParams.set('listing', listing);
         // Clear category to avoid conflicting UI states
         url.searchParams.delete('category');
     }
     url.searchParams.delete('page');
     window.location.href = url.toString();
 }
function filterByCategory(category) {
    const url = new URL(window.location);
    const current = url.searchParams.get('category');
    // Toggle: if already active, remove the filter
    if (current === category) {
        url.searchParams.delete('category');
        // Sync with property type filter - remove active state
        syncNavWithPropertyType(category, false);
    } else {
        url.searchParams.set('category', category);
        // Sync with property type filter - add active state
        syncNavWithPropertyType(category, true);
    }
    window.location.href = url.toString();
}

// Function to sync nav-tabs-custom with property type filters
function syncNavWithPropertyType(category, isActive) {
    // Find matching property type tag
    const propertyTypeTag = document.querySelector(`[data-filter="propertyType"][data-value="${category}"]`);
    if (propertyTypeTag) {
        if (isActive) {
            // Remove active from all property type tags
            document.querySelectorAll('#propertyTypeSection .tag').forEach(t => t.classList.remove('active'));
            // Add active to matching tag
            propertyTypeTag.classList.add('active');
        } else {
            // Remove active from matching tag
            propertyTypeTag.classList.remove('active');
        }
    }
}

// Function to sync property type filters with nav-tabs-custom
function syncPropertyTypeWithNav(propertyType, isActive) {
    // Find matching nav button
    const navButton = document.querySelector(`.nav-tabs-custom button[onclick*="${propertyType}"]`);
    if (navButton) {
        if (isActive) {
            // Remove active from all nav buttons
            document.querySelectorAll('.nav-tabs-custom button').forEach(btn => btn.classList.remove('active'));
            // Add active to matching button
            navButton.classList.add('active');
        } else {
            // Remove active from matching button
            navButton.classList.remove('active');
        }
    }
}

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
        const detailsUrl = window.location.origin + '/test/products/product-details.php?id=' + propertyId;
        // Try to find the card to extract richer info and image
        const card = document.querySelector(`.aproperty-card[data-property-id="${propertyId}"]`);
        let title = 'Property Details';
        let text = 'Check out this property';
        let imageUrl = '';
        if (card) {
            const titleEl = card.querySelector('h3');
            const subtitleEl = titleEl ? titleEl.querySelector('span') : null;
            const descEl = card.querySelector('p');
            const detailsSpans = card.querySelectorAll('.property-details span');
            const imgEl = card.querySelector('img.property-image');
            title = titleEl ? (titleEl.childNodes[0]?.textContent || titleEl.textContent || title) : title;
            const subtitle = subtitleEl ? subtitleEl.textContent.trim() : '';
            const desc = descEl ? descEl.textContent.trim() : '';
            const confPrice = detailsSpans[0] ? detailsSpans[0].textContent.trim() : '';
            const areaTxt = detailsSpans[1] ? detailsSpans[1].textContent.trim() : '';
            const possTxt = detailsSpans[2] ? detailsSpans[2].textContent.trim() : '';
            imageUrl = imgEl ? imgEl.getAttribute('src') || '' : '';

            const lines = [
                title.trim(),
                subtitle ? subtitle : '',
                confPrice ? confPrice : '',
                areaTxt ? areaTxt : '',
                possTxt ? possTxt : '',
                desc ? '\n' + desc : '',
                '\nView details: ' + detailsUrl
            ].filter(Boolean);
            text = lines.join('\n');
        } else {
            text = text + '\n' + detailsUrl;
        }

        // Attempt Web Share with image file
        if (navigator.share) {
            const shareData = { title: title.trim(), text, url: detailsUrl };
            const absImageUrl = imageUrl ? new URL(imageUrl, window.location.href).href : '';
            if (absImageUrl && window.File && window.Blob) {
                try {
                    const res = await fetch(absImageUrl);
                    const blob = await res.blob();
                    const file = new File([blob], 'property.jpg', { type: blob.type || 'image/jpeg' });
                    if (('canShare' in navigator) ? navigator.canShare({ files: [file] }) : false) {
                        shareData.files = [file];
                        delete shareData.url; // keep text clean when sending files
                    } else {
                        // If files not supported, include image URL in text
                        shareData.text = text + (absImageUrl ? '\nImage: ' + absImageUrl : '');
                    }
                } catch (_) {
                    // If fetch fails, include image URL in text
                    shareData.text = text + (absImageUrl ? '\nImage: ' + absImageUrl : '');
                }
            }
            await navigator.share(shareData);
            return;
        }

        // Fallback: copy composed text + link and image URL
        const absImageUrl = imageUrl ? new URL(imageUrl, window.location.href).href : '';
        const fallbackText = text + (absImageUrl ? '\nImage: ' + absImageUrl : '');
        await navigator.clipboard.writeText(fallbackText);
        alert('Property details copied. Paste to share!');
    } catch (err) {
        try { window.open('/test/products/product-details.php?id=' + propertyId, '_blank'); } catch (_) {}
    }
}

function contactProperty(propertyId) {
    // Call the phone number directly
    const phoneNumber = '80888 55555';
    window.location.href = 'tel:' + phoneNumber;
}

// Share full card: title, description, attributes and image
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

        const detailsUrl = window.location.origin + '/test/products/product-details.php?id=' + propertyId;

        // Build share text
        const lines = [
            `${title}`,
            location ? `Location: ${location}` : '',
            config ? `Configuration: ${config}` : '',
            area ? `Area: ${area} sq.ft` : '',
            price ? `Price: ${price}` : '',
            furniture ? `Furniture: ${furniture}` : '',
            desc ? `\n${desc}` : '',
            `\nView details: ${detailsUrl}`
        ].filter(Boolean);
        const text = lines.join('\n');

        // Try Web Share Level 2 with files (if supported and image available)
        if (navigator.share) {
            const shareData = { title, text, url: detailsUrl };

            // Attempt image share if fetchable and File constructor exists
            if (imageUrl && window.File && window.Blob) {
                try {
                    const absImageUrl = new URL(imageUrl, window.location.href).href;
                    const res = await fetch(absImageUrl);
                    const blob = await res.blob();
                    const file = new File([blob], 'property.jpg', { type: blob.type || 'image/jpeg' });
                    if ('files' in navigator.canShare ? navigator.canShare({ files: [file] }) : false) {
                        shareData.files = [file];
                        delete shareData.url; // with files, keep text clean
                    }
                } catch (_) {
                    // Include image URL in text if attachment fails
                    shareData.text = text + (imageUrl ? '\nImage: ' + new URL(imageUrl, window.location.href).href : '');
                }
            }

            await navigator.share(shareData);
            return;
        }

        // Fallback: copy composed text + link (+image URL) to clipboard
        const absImageUrl = imageUrl ? new URL(imageUrl, window.location.href).href : '';
        await navigator.clipboard.writeText(text + (absImageUrl ? '\nImage: ' + absImageUrl : ''));
        alert('Property details copied. Paste to share!');
    } catch (err) {
        try {
            // Last fallback: open details page
            window.open('/test/products/product-details.php?id=' + propertyId, '_blank');
        } catch (_) {}
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
    
    // Initial sync between nav-tabs-custom and property type filters
    syncInitialStates();

    // Optional: attach Share All shortcut on keyboard (Ctrl/Cmd+Shift+S)
    document.addEventListener('keydown', function(e){
        const isMac = navigator.platform.toUpperCase().indexOf('MAC')>=0;
        if ((isMac ? e.metaKey : e.ctrlKey) && e.shiftKey && (e.key === 'S' || e.key === 's')) {
            e.preventDefault();
            shareAllProperties();
        }
    });
});

// Function to sync initial states on page load
function syncInitialStates() {
    // Check if any nav button is active
    const activeNavButton = document.querySelector('.nav-tabs-custom button.active');
    if (activeNavButton) {
        const category = activeNavButton.textContent.trim();
        // Sync with property type filter
        syncNavWithPropertyType(category, true);
    }
    
    // Check if any property type tag is active
    const activePropertyTypeTag = document.querySelector('#propertyTypeSection .tag.active');
    if (activePropertyTypeTag) {
        const propertyType = activePropertyTypeTag.getAttribute('data-value');
        // Sync with nav-tabs-custom
        syncPropertyTypeWithNav(propertyType, true);
    }
}

// Filter functionality
function initializeFilters() {
    // Handle property type filter tags (do NOT navigate immediately; apply on Apply Filter)
    const propertyTypeTags = document.querySelectorAll('[data-filter="propertyType"]');
    propertyTypeTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            const value = this.getAttribute('data-value');
            // Detect cross (remove) click region
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;
            const isCrossClick = clickX > (rect.width - 35) && clickY > (rect.height / 2 - 10) && clickY < (rect.height / 2 + 10);

            if (isCrossClick && this.classList.contains('active')) {
                this.classList.remove('active');
                syncPropertyTypeWithNav(value, false);
                updateApplyButtonState();
                return;
            }

            // Single-select behavior: one active at a time
            document.querySelectorAll('#propertyTypeSection .tag').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            syncPropertyTypeWithNav(value, true);
            updateApplyButtonState();
        });
    });

    // Handle bedroom filter tags (do NOT navigate immediately)
    const bedroomTags = document.querySelectorAll('[data-filter="bedrooms"]');
    bedroomTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
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
    const keepParams = ['category', 'city', 'featured'];
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

    // Selected tags (only overwrite when a selection is explicitly active in UI)
    const activePropertyType = document.querySelector('#propertyTypeSection .tag.active');
    if (activePropertyType) {
        url.searchParams.set('propertyType', activePropertyType.getAttribute('data-value'));
    }

    const activeBedrooms = document.querySelector('#bedroomsSection .tag.active');
    if (activeBedrooms) {
        url.searchParams.set('bedrooms', activeBedrooms.getAttribute('data-value'));
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
        const sectionMap = {
            'propertyType': '#propertyTypeSection',
            'bedrooms': '#bedroomsSection'
        };
        const selector = sectionMap[filterType];
        if (selector) {
            const tag = document.querySelector(`${selector} .tag[data-value="${value}"]`);
            if (tag) {
                if (isActive) {
                    // Remove active from all tags in section
                    document.querySelectorAll(`${selector} .tag`).forEach(t => t.classList.remove('active'));
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
            { key: 'propertyType', section: '#propertyTypeSection' },
            { key: 'bedrooms', section: '#bedroomsSection' }
        ];
        map.forEach(({ key, section }) => {
            const val = params.get(key);
            if (!val) return;
            const target = document.querySelector(section + ' .tag[data-value="' + CSS.escape ? CSS.escape(val) : val + '"]');
            if (target) target.classList.add('active');
        });

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

        // Any active tag selections
        const hasActiveTag = document.querySelector('#propertyTypeSection .tag.active, #bedroomsSection .tag.active') !== null;
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

// Share all currently visible properties: titles, details, description and attempt attaching a few images
async function shareAllProperties() {
  try {
    const cards = Array.from(document.querySelectorAll('.aproperty-card'));
    if (cards.length === 0) {
      alert('No properties to share.');
      return;
    }

    // Build text block with each property's info
    const items = cards.map(card => {
      const id = card.getAttribute('data-property-id') || '';
      const titleEl = card.querySelector('h3');
      const subtitleEl = titleEl ? titleEl.querySelector('span') : null;
      const descEl = card.querySelector('p');
      const detailsSpans = card.querySelectorAll('.property-details span');
      const imgEl = card.querySelector('img.property-image');

      const title = titleEl ? (titleEl.childNodes[0]?.textContent || titleEl.textContent || '').trim() : 'Property';
      const subtitle = subtitleEl ? subtitleEl.textContent.trim() : '';
      const desc = descEl ? descEl.textContent.trim() : '';
      const confPrice = detailsSpans[0] ? detailsSpans[0].textContent.trim() : '';
      const areaTxt = detailsSpans[1] ? detailsSpans[1].textContent.trim() : '';
      const possTxt = detailsSpans[2] ? detailsSpans[2].textContent.trim() : '';
      const imageUrl = imgEl ? imgEl.getAttribute('src') : '';

      const detailsUrl = window.location.origin + '/test/products/product-details.php?id=' + id;

      const lines = [
        title,
        subtitle ? subtitle : '',
        confPrice ? confPrice : '',
        areaTxt ? areaTxt : '',
        possTxt ? possTxt : '',
        desc ? '\n' + desc : '',
        '\nView details: ' + detailsUrl
      ].filter(Boolean);

      return { id, title, text: lines.join('\n'), imageUrl };
    });

    // Combine into one share text
    const header = 'Property list from Big Deal Ventures\n\n';
    const combinedText = header + items.map((it, idx) => (idx+1) + '. ' + it.text).join('\n\n');

    // Try Web Share with 1 image (first property's image) if supported
    if (navigator.share) {
      const shareData = { title: 'Property list', text: combinedText };

      // Attempt fetching the first available image to include
      const firstImageUrl = (items.find(i => i.imageUrl) || {}).imageUrl;
      if (firstImageUrl && window.File && window.Blob) {
        try {
          const res = await fetch(firstImageUrl, { mode: 'cors' });
          const blob = await res.blob();
          const fname = 'property.jpg';
          const file = new File([blob], fname, { type: blob.type || 'image/jpeg' });
          if ('canShare' in navigator && navigator.canShare({ files: [file] })) {
            shareData.files = [file];
            delete shareData.url;
          }
        } catch (_) { /* ignore image fetch failure; continue with text only */ }
      }

      await navigator.share(shareData);
      return;
    }

    // Fallback: copy to clipboard
    await navigator.clipboard.writeText(combinedText);
    alert('Property list copied. Paste to share!');
  } catch (err) {
    // Last fallback: open current page, allowing manual share
    try { window.open(window.location.href, '_blank'); } catch (_) {}
  }
}
</script>
</body>
</html>

