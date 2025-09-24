<?php
require_once '../config/config.php';

// Get selected category from URL parameter
$mysqli = getMysqliConnection();
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedCity = isset($_GET['city']) ? $_GET['city'] : ($selectedCity ?? '');
$isFeaturedOnly = isset($_GET['featured']) && $_GET['featured'] === '1';
$selectedCity = isset($_GET['city']) ? $_GET['city'] : '';

// Load cities for the city select
$cities = [];
try {
    $citySql = "SELECT id, name FROM cities ORDER BY name";
    if ($resCity = $mysqli->query($citySql)) {
        while ($row = $resCity->fetch_assoc()) { $cities[] = $row; }
        $resCity->free();
    }
} catch (Throwable $e) { error_log('Cities load error: ' . $e->getMessage()); }

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

// Add featured-only filter if requested
if ($isFeaturedOnly) {
    $query .= " AND EXISTS (SELECT 1 FROM features f WHERE f.property_id = p.id)";
}

$query .= " ORDER BY p.created_at DESC LIMIT 20";

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
  <link rel="icon" href="../assets/images/logo.png" type="image/png">
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
</head>
<body class="article-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

    <div class="container">
      <div class="row">
        <div class="nav-tabs-custom mx-auto">
          <button class="<?php echo $selectedCategory === 'Buy' || empty($selectedCategory) ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Buy')">Buy</button>
          <button class="<?php echo $selectedCategory === 'Rent' ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Rent')">Rent</button>
          <button class="<?php echo $selectedCategory === 'Plot' ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Plot')">Plot</button>
          <button class="<?php echo $selectedCategory === 'Commercial' ? 'active' : ''; ?>" type="button" onclick="filterByCategory('Commercial')">Commercial</button>
          <button class="<?php echo $selectedCategory === 'PG/Co Living' ? 'active' : ''; ?>" type="button" onclick="filterByCategory('PG/Co Living')">PG/Co Living</button>
          <button class="<?php echo $selectedCategory === '1BHK/Studio' ? 'active' : ''; ?>" type="button" onclick="filterByCategory('1BHK/Studio')">1BHK/Studio</button>
        </div>
      </div>

      <!-- Select city -->
      <div class="custom-select-wrapper">
        <select class="custom-select" name="city" id="city-select" aria-label="Select city" onchange="onCityChange(this.value)">
          <option value="" disabled <?php echo $selectedCity === '' ? 'selected' : ''; ?>>Select city</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?php echo htmlspecialchars($city['name']); ?>" <?php echo $selectedCity === $city['name'] ? 'selected' : ''; ?>>
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

    <!-- Applied filters tags container -->
    <div class="applied-filters" id="appliedFiltersContainer"></div>

    <!-- Budget Section -->
    <section class="filter-section" id="budgetSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="budgetSection">
        Budget
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
          <div class="double-range" id="doubleRange1">
    <input type="range" id="minRange1" min="0" max="5000" step="50" value="500" />
    <input type="range" id="maxRange1" min="0" max="5000" step="50" value="4000" />
    <div class="slider-track"></div>
    <div class="slider-range" id="sliderRange1"></div>
  </div>
  <div class="range-labels" id="rangeLabels1">
    <span id="minLabel1">500</span>
    <span id="maxLabel1">4000</span>
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
          <div class="tag" data-filter="propertyType" data-value="Residential Apartment">Residential Apartment   <span class="add-icon">+</span></div>
          <div class="tag" data-filter="propertyType" data-value="Residential Land">Residential Land <span class="add-icon">+</span></div>
          <div class="tag" data-filter="propertyType" data-value="Incorporated House/Villa">Incorporated House/Villa <span class="add-icon">+</span></div>
          <div class="tag" data-filter="propertyType" data-value="Residential Lands">Residential Lands <span class="add-icon">+</span></div>
          <div class="tag" data-filter="propertyType" data-value="Independent/Duplex Floor">Independent/Duplex Floor <span class="add-icon">+</span></div>
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
          <div class="tag" data-filter="bedrooms" data-value="1 RK/1 BHK"><span class="add-icon">+</span> 1 RK/1 BHK </div>
          <div class="tag" data-filter="bedrooms" data-value="2 BHK"><span class="add-icon">+</span> 2 BHK </div>
          <div class="tag" data-filter="bedrooms" data-value="3 BHK"><span class="add-icon">+</span> 3 BHK </div>
        </div>
      </div>
    </section>

    <!-- Construction status -->
    <section class="filter-section" id="constructionStatusSection">
      <div class="filter-section-header" tabindex="0" data-toggle-target="constructionStatusSection">
        Construction status
        <span class="caret"></span>
      </div>
      <div class="filter-section-content">
        <div class="tag-list" id="constructionStatusTags">
          <div class="tag" data-filter="constructionStatus" data-value="New Launch">New Launch <span class="add-icon">+</span></div>
          <div class="tag" data-filter="constructionStatus" data-value="Under Construction">Under Construction <span class="add-icon">+</span></div>
          <div class="tag" data-filter="constructionStatus" data-value="Ready to Move">Ready to Move <span class="add-icon">+</span></div>
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
    <input type="range" id="minRange2" min="0" max="5000" step="50" value="500" />
    <input type="range" id="maxRange2" min="0" max="5000" step="50" value="4000" />
    <div class="slider-track"></div>
    <div class="slider-range" id="sliderRange2"></div>
  </div>
  <div class="range-labels" id="rangeLabels2">
    <span id="minLabel2">500</span>
    <span id="maxLabel2">4000</span>
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
          <label><input type="checkbox" data-filter="localities" value="Kadri" /> Kadri</label>
          <label><input type="checkbox" data-filter="localities" value="Bejai" /> Bejai</label>
          <label><input type="checkbox" data-filter="localities" value="Surathkal" /> Surathkal</label>
          <label><input type="checkbox" data-filter="localities" value="Ujire" /> Ujire</label>
          <label><input type="checkbox" data-filter="localities" value="Mangalore" /> Mangalore</label>
        </div>
        <div class="more-localities" id="toggleMoreLocalities">+ More Localities</div>
        <div class="locality-list" id="extraLocalities" style="display:none; margin-top: 12px;">
          <label><input type="checkbox" data-filter="localities" value="Puttur" /> Puttur</label>
          <label><input type="checkbox" data-filter="localities" value="Moodbidri" /> Moodbidri</label>
          <label><input type="checkbox" data-filter="localities" value="Kankanady" /> Kankanady</label>
          <label><input type="checkbox" data-filter="localities" value="Deralakatte" /> Deralakatte</label>
        </div>
      </div>
    </section>
  </aside>  
      </div>

      <!-- Property Results -->
      <div  class="col-lg-8 col-md-7 aproperty">
        <h2>
          <?php echo count($properties); ?> results | 
          <?php echo !empty($selectedCategory) ? $selectedCategory . ' Properties' : 'All Properties'; ?>
          <?php if ($selectedCity !== ''): ?>
            in <span class="highlight-city"><?php echo htmlspecialchars($selectedCity); ?></span>
          <?php endif; ?>
          for Sale
        </h2>

        <div class="aproperty-cards">
          <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
              <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px; cursor: pointer;" onclick="goToPropertyDetails(<?php echo $property['id']; ?>)">
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
                          <button class="btn btn-share" onclick="event.stopPropagation(); shareProperty(<?php echo $property['id']; ?>)">Share</button>
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
function filterByCategory(category) {
    // Update URL with category parameter
    const url = new URL(window.location);
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    
    // Reload page with new category filter
    window.location.href = url.toString();
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
    // Navigate to product-details.php with property ID
    window.location.href = 'product-details.php?id=' + propertyId;
}

function shareProperty(propertyId) {
    // Handle share functionality
    if (navigator.share) {
        navigator.share({
            title: 'Property Details',
            text: 'Check out this property',
            url: window.location.origin + '/test/products/product-details.php?id=' + propertyId
        });
    } else {
        // Fallback: copy URL to clipboard
        const url = window.location.origin + '/test/products/product-details.php?id=' + propertyId;
        navigator.clipboard.writeText(url).then(() => {
            alert('Property link copied to clipboard!');
        });
    }
}

function contactProperty(propertyId) {
    // Handle contact functionality - you can customize this
    alert('Contact functionality for property ID: ' + propertyId);
    // You can redirect to a contact form or open a modal
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
});
</script>
</body>
</html>
