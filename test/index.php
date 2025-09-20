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
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'components/loader.php'; ?>

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



    <div class="container">
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
        <div class="row g-4">
            <!-- Property Card 1 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img  src="assets/images/prop/prop1.png" alt="Modern Family Villa" class="propimg">
                    <div class="card-body">
                        <div class="card-title">Modern Family Villa</div>
                        <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Property Card 2 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img src="assets/images/prop/prop2.png" alt="Modern Family Villa">
                    <div class="card-body">
                        <div class="card-title">Modern Family Villa</div>
                         <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Property Card 3 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img src="assets/images/prop/prop3.png" alt="Luxury City Apartment">
                    <div class="card-body">
                        <div class="card-title">Luxury City Apartment</div>
                        <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   
        

     <!-- Properties Grid -->
        <div class="row g-4">
            <!-- Property Card 1 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img  src="assets/images/prop/prop1.png" alt="Modern Family Villa" class="propimg">
                    <div class="card-body">
                        <div class="card-title">Modern Family Villa</div>
                        <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4 BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Property Card 2 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img src="assets/images/prop/prop2.png" alt="Modern Family Villa">
                    <div class="card-body">
                        <div class="card-title">Modern Family Villa</div>
                         <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Property Card 3 -->
            <div class="col-md-4">
                <div class="card property-card h-100">
                    <img src="assets/images/prop/prop3.png" alt="Luxury City Apartment">
                    <div class="card-body">
                        <div class="card-title">Luxury City Apartment</div>
                        <div class="property-attrs">
                            <div class="property-attr"><img src="assets/images/icon/home_dark.svg" class="svg" > 4BHK</div>
                            <div class="property-attr"><img src="assets/images/icon/park_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sqft_dark.svg" class="svg" > 4 sq. ft.</div>
                            <div class="property-attr"><img src="assets/images/icon/terrace_dark.svg" class="svg" > 4</div>
                            <div class="property-attr"><img src="assets/images/icon/sofa_dark.svg" class="svg" > Semi-furnished</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        <div class="container">
          <div class="location-grid">
            <div>
              <div class="city-card">
                <img src="assets/images/loc/blore.png" alt="Bengaluru">
                <div class="city-label">Bengaluru</div>
              </div>
            </div>
            <div>
              <div class="city-card">
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
        <div class="FaqQ-item ">
          <div class="FaqQ-title">
            <span>Lorem ipsum dolor sit amet, consectetur ?</span>
            <img src="assets/images/icon/arrowdown.svg" alt="arrow down" class="farrow down">
          </div>
          <div class="FaqQ-content">
            <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt</span>
          </div>
        </div>
        <div class="FaqQ-item">
          <div class="FaqQ-title">
            <span>Lorem ipsum dolor sit amet, consectetur ?</span>
            <img src="assets/images/icon/arrowdown.svg" alt="arrow up" class="farrow down">
          </div>

          <div class="FaqQ-content">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt
          </div>
        </div>

        <div class="FaqQ-item">
          <div class="FaqQ-title">
            <span>Lorem ipsum dolor sit amet, consectetur ?</span>
          <img src="assets/images/icon/arrowdown.svg" alt="arrow up" class="farrow down">
          </div>
          <div class="FaqQ-content">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt
          </div>
        </div>
        <div class="FaqQ-item">
          <div class="FaqQ-title">
            <span>Lorem ipsum dolor sit amet, consectetur ?</span>
          <img src="assets/images/icon/arrowdown.svg" alt="arrow up" class="farrow down">
          </div>
          <div class="FaqQ-content">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt
          </div>
        </div>
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
