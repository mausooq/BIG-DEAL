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
          <button class="active" type="button">Buy</button>
          <button type="button">Rent</button>
          <button type="button">Commercial</button>
          <button type="button">PG/Co Living</button>
          <button type="button">1BHK/Studio</button>
          <button type="button">Plot</button>
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
        <h2>1085 results | Property in <span class="highlight-city">Mangaluru</span> for Sale</h2>

        <div class="aproperty-cards">
              
         <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px;">
            <img src="../assets/images/prop/aprop1.png" alt="Pooja Apartment" class="property-image" />
            <div class="property-info" style="flex: 1;">
              <h3>Pooja Apartment <br>
              <span>3 BHK House in kadri, Mangaluru</span></h3>
              <p>Nearby: St Theresa's ICSE School - 0.1 km • KMC Hospital Mangaluru - 0.4 km • Machali - 0.1 km • City Centre Mall - 0.1 km</p>
              <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                <span>3 BHK ₹98 L</span>
                <span>1000 sq.ft Builtup area</span>
                <span>Ready to move Possession status</span>
              </div>
              <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                <p class="property-time">3mo ago</p>
                  <div class="btn-grp" style="display: flex; gap: 10px;">
                      <button class="btn btn-share">Share</button>
                    <button class="btn btn-contact">Contact us</button>
                  </div>
              </div>
           </div>
          </div>
       
          <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px;">
            <img src="../assets/images/prop/aprop1.png" alt="Pooja Apartment" class="property-image" />
            <div class="property-info" style="flex: 1;">
              <h3>Pooja Apartment <br>
              <span>3 BHK House in kadri, Mangaluru</span></h3>
              <p>Nearby: St Theresa's ICSE School - 0.1 km • KMC Hospital Mangaluru - 0.4 km • Machali - 0.1 km • City Centre Mall - 0.1 km</p>
              <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                <span>3 BHK ₹98 L</span>
                <span>1000 sq.ft Builtup area</span>
                <span>Ready to move Possession status</span>
              </div>
              <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                <p class="property-time">3mo ago</p>
                  <div class="btn-grp" style="display: flex; gap: 10px;">
                      <button class="btn btn-share">Share</button>
                    <button class="btn btn-contact">Contact us</button>
                  </div>
              </div>
           </div>
          </div>
          
          <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px;">
            <img src="../assets/images/prop/aprop2.png" alt="Pooja Apartment" class="property-image" />
            <div class="property-info" style="flex: 1;">
              <h3>Pooja Apartment <br>
              <span>3 BHK House in kadri, Mangaluru</span></h3>
              <p>Nearby: St Theresa's ICSE School - 0.1 km • KMC Hospital Mangaluru - 0.4 km • Machali - 0.1 km • City Centre Mall - 0.1 km</p>
              <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                <span>3 BHK ₹98 L</span>
                <span>1000 sq.ft Builtup area</span>
                <span>Ready to move Possession status</span>
              </div>
              <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                <p class="property-time">3mo ago</p>
                  <div class="btn-grp" style="display: flex; gap: 10px;">
                      <button class="btn btn-share">Share</button>
                    <button class="btn btn-contact">Contact us</button>
                  </div>
              </div>
           </div>
          </div>
          <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px;">
            <img src="../assets/images/prop/aprop3.png" alt="Pooja Apartment" class="property-image" />
            <div class="property-info" style="flex: 1;">
              <h3>Pooja Apartment <br>
              <span>3 BHK House in kadri, Mangaluru</span></h3>
              <p>Nearby: St Theresa's ICSE School - 0.1 km • KMC Hospital Mangaluru - 0.4 km • Machali - 0.1 km • City Centre Mall - 0.1 km</p>
              <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                <span>3 BHK ₹98 L</span>
                <span>1000 sq.ft Builtup area</span>
                <span>Ready to move Possession status</span>
              </div>
              <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                <p class="property-time">3mo ago</p>
                  <div class="btn-grp" style="display: flex; gap: 10px;">
                      <button class="btn btn-share">Share</button>
                    <button class="btn btn-contact">Contact us</button>
                  </div>
              </div>
           </div>
          </div>

          <div class="aproperty-card" style="display: flex; align-items: center; gap: 20px;">
            <img src="../assets/images/prop/aprop4.png" alt="Pooja Apartment" class="property-image" />
            <div class="property-info" style="flex: 1;">
              <h3>Pooja Apartment <br>
              <span>3 BHK House in kadri, Mangaluru</span></h3>
              <p>Nearby: St Theresa's ICSE School - 0.1 km • KMC Hospital Mangaluru - 0.4 km • Machali - 0.1 km • City Centre Mall - 0.1 km</p>
              <div class="property-details" style="display: flex; justify-content: space-between; max-width: 600px;">
                <span>3 BHK ₹98 L</span>
                <span>1000 sq.ft Builtup area</span>
                <span>Ready to move Possession status</span>
              </div>
              <div class="property-actions" style="display: flex; justify-content: space-between; align-items: center; max-width: 600px;">
                <p class="property-time">3mo ago</p>
                  <div class="btn-grp" style="display: flex; gap: 10px;">
                      <button class="btn btn-share">Share</button>
                    <button class="btn btn-contact">Contact us</button>
                  </div>
              </div>
           </div>
          </div>
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
</body>
</html>
