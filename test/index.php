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

  <?php $asset_path = 'assets/'; include __DIR__ . '/components/navbar.php'; ?>
   <section class=" hero-section">
    <div class="text-center  headh  figtree ">
      <h1 class="fw-bold">Find your Beautiful House</h1>
      <p class="sfpro subtitle">From breathtaking views to exquisite furnishings, our accommodations <br>redefine luxury and offer an experience beyond compare.</p>
      <img src="assets/images/hero-image.jpg" alt="hero-image" class="hero-image">
    </div>
    </section>
    
   
  <div class="place">
   <div class="container">
   
        <div class="row  ">
        <div class="nav-tabs-custom mx-auto ">
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

 
       
    <div class="container">
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
    <section class="contact-section ">
      <h2 class="blac2 col-md-6 sfpro">
        Connect With Our Experts
      </h2>
      <p class="blac2-sub poppins">
        Our team is here to help you find your perfect home.
      </p>
       <div class="btn-stack">
    <button class="btn-black"></button>
    <button class="btn-red">Contact Us Now</button>
  </div>
      <div class="d-flex align-items-center flex-column flex-md-row">
        <hr class="section-divider">
        <img class="house-img" src="assets/images/prop/prop4.png" alt="Modern House" />
      </div>
    </section>


    <!-- Testimonials -->
    <section class="testimonials">
      <div class="container">
        <h2 class="test-head">Testimonials</h2>
        <p class="test-sub">Stories from people who found their perfect space</p>
        
        <div class="testimonial-content">
          <div class="testimg">
            <img src="assets/images/icon/quote.svg" alt="quote" class="quote">
            <img src="assets/images/prop/prop5.png" alt="House" class="img-fluid">
          </div>

          <div class="testimonial-carousel">
            <div class="testimonial-slide active">
              <div class="testimonial-author">
                <img src="assets/images/avatar/test1.png" alt="Munazza">
                <div class="testimonial-author-info">
                  <h5>Munazza</h5>
                  <p>Software Developer</p>
                </div>
              </div>

              <p>Exquisite sophisticated iconic cutting-edge laborum deserunt esse bureaux cupidatat id minim. Sharp classic the best commodo nostrud delightful.</p>

              <div class="stars">
                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
              </div>
            </div>

            <div class="testimonial-slide">
              <div class="testimonial-author">
                <img src="assets/images/avatar/test2.png" alt="John Doe">
                <div class="testimonial-author-info">
                  <h5>John Doe</h5>
                  <p>Designer</p>
                </div>
              </div>

              <p>Amazing experience finding my dream home. The team was professional and helped me every step of the way. Highly recommended!</p>

              <div class="stars">
                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
              </div>
            </div>

            <div class="testimonial-slide">
              <div class="testimonial-author">
                <img src="assets/images/avatar/test3.png" alt="Jane Smith">
                <div class="testimonial-author-info">
                  <h5>Jane Smith</h5>
                  <p>Entrepreneur</p>
                </div>
              </div>

              <p>Outstanding service and beautiful properties. I found the perfect office space for my business. Thank you for making it so easy!</p>

              <div class="stars">
                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
              </div>
            </div>
          </div>

          <div class="testimonial-nav">
            <img src="assets/images/icon/prev.svg" alt="previous" id="testimonial-prev">
            <img src="assets/images/icon/next.svg" alt="next" id="testimonial-next">
          </div>
        </div>
    </section>


  <!-- about section  -->


    <section class="container ">
      <div class="about-section">
  <div class="about-image">
    <img src="assets/images/prop/bhouse2.png" alt="aboutimg" />
  </div>
  <div class="about-content">
    <h1>
      Hello City We are <br> leader in <span class="highlight">properties.</span>
       <img src="assets/images/ARROW.png" alt="arrow" class="arrow">
    </h1>
     <p>
      Your dream home isn’t just a vision — it’s a reality waiting for you. As leaders in real estate, we specialize in crafting experiences where luxury meets comfort, and investment meets trust. Explore our handpicked collection of properties designed for modern lifestyles and lasting value.
    </p>
    <button class="btn-arrow">
      View More <span>→</span>
    </button>
    <div class="about-stats">
       <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label">Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
    </div>
  </div>
  </div>
    </section>

<section class="blog-section ">
  <div class="section-header">
    <h2>Explore our latest blogs for<br>real estate insights</h2>
    <button class="view-all-btn">View all <span>→</span></button>
  </div>
  <div class="blog-feature">
    <div class="blog-image">
      <img src="assets/images/prop/bhouse3.png" alt="Blogimage1">
    </div>
    <div class="blog-card">
      <span class="blog-read">7 min read</span>
      <h3>High-end properties</h3>
      <p>
        Experience the pinnacle of luxury living with our exclusive collection of high-end properties. Featuring elegant villas, premium apartments, and penthouses in prime locations, these homes are designed with world-class amenities and modern architecture to deliver unmatched comfort, sophistication, and lifestyle value.
      </p>
      <div class="blog-author">
        <img src="assets/images/avatar/test1.png" class="author-img" alt="Admin">
        <div>
          <span class="author-name">Admin</span><br>
          <span class="author-role">Software Dev</span>
        </div>
      </div>
    </div>
  </div>
<div class="d-flex blog2">
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse4.png" alt="Living Room">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse5.png" alt="Modern TV Unit">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse6.png" alt="Contemporary Kitchen">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  </div>

</section>




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
