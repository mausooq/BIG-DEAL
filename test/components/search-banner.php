<?php
// Search Banner Component
// This component displays a modern interior with a white overlay banner for search functionality
?>

<section class="search-banner-body">
<div class="container-fluid">
<section class="search-banner">
  <div class="search-banner-container">
    <!-- Background Interior Image -->
    <div class="interior-background">
      <img src="../assets/images/sp2.jpg" alt="Modern Interior" class="interior-image" decoding="async" fetchpriority="high">
    </div>
    
    <!-- White Overlay Banner -->
    <div class="white-banner-overlay">
      <!-- Banner Content -->
      <div class="banner-content">
        <div class="content-wrapper">
          <div class="text-content">
            <h2 class="banner-headline">
              Find Your Perfect Dream Home
            </h2>
            
            <p class="banner-description">
              Discover premium properties with our advanced search platform. Get personalized recommendations and expert guidance every step of the way.
            </p>
            
            <a href="/BIG-DEAL/test/products/index.php" class="search-now-btn">
              EXPLORE PROPERTIES
              <span>→</span>
            </a>
          </div>
          
          <!-- <div class="image-content">
            <img src="../assets/images/black_hero.png" alt="Property Hero" class="hero-image">
          </div> -->
        </div>
      </div>
    </div>
  </div>
</section>
</div>
</section>

<style>
/* Search Banner Component Styles */
.search-banner-body {
  margin: 40px 0;
}

.search-banner {
  position: relative;
  width: 100%;
  height: 30em;
  overflow: hidden;
  border-radius: 12px;
}

.search-banner-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.interior-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.interior-image {
  width: 100%;
  height: 150%;
  object-fit: cover;
  object-position: center;
  will-change: transform;
  transform: translate3d(0, 0, 0);
}

.white-banner-overlay {
  background: rgba(0, 0, 0, 0.4);
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 100%;
  padding: 40px;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-content {
  width: 100%;
  max-width: 1200px;
}

.content-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 40px;
}

.text-content {
  flex: 1;
  text-align: left;
}

.image-content {
  flex: 0 0 auto;
}

.hero-image {
  max-width: 300px;
  height: auto;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.hero-image.scroll-effect {
  transform: translateY(-20px);
}

.banner-headline {
  margin: 0 0 20px 0;
  color:rgb(250, 250, 250);
  font-family: 'DM Sans', sans-serif;
  font-weight: 700;
  line-height: 1.2;
  font-size: 48px;
}

.banner-description {
  margin: 0 0 30px 0;
  color:rgb(250, 250, 250);
  font-family: 'DM Sans', sans-serif;
  font-size: 16px;
  line-height: 1.6;
  font-weight: 400;
  font-size: 24px;
}

.search-now-btn {
  background:rgba(255, 255, 255, 0.75);
  color: #000;
  border: none;
  font-family: 'DM Sans', sans-serif;
  font-weight: 800;
  border-radius: 15px;
  height: 44px;
  padding: 0 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
}

.search-now-btn:hover {
  background: #333;
  color: #fff;
  transform: translateY(-2px);
}

.search-now-btn span {
  margin-left: 8px;
  display: inline-block;
}

/* Responsive Design */
@media (max-width: 768px) {
  .search-banner {
    height: 18em;
  }
  
  .interior-image {
    height: 100%;
  }
  
  .white-banner-overlay {
    height: 100%;
    padding: 30px 20px;
  }
  
  .content-wrapper {
    flex-direction: column;
    gap: 30px;
    text-align: center;
  }
  
  .text-content {
    text-align: center;
  }
  
  .hero-image {
    max-width: 150px;
  }
  
  .banner-headline {
    font-size: 32px;
  }
  
  .banner-description {
    font-size: 16px;
    margin-bottom: 25px;
  }
  
  .search-now-btn {
    font-size: 14px;
    padding: 12px 24px;
  }
}

@media (max-width: 576px) {
  .search-banner {
    height: 16em;
  }
  
  .interior-image {
    height: 100%;
  }
  
  .white-banner-overlay {
    height: 100%;
    padding: 25px 15px;
  }
  
  .content-wrapper {
    gap: 20px;
  }
  
  .hero-image {
    max-width: 200px;
  }
  
  .banner-headline {
    font-size: 24px;
  }
  
  .banner-description {
    font-size: 14px;
    margin-bottom: 20px;
  }
  
  .search-now-btn {
    font-size: 13px;
    padding: 10px 20px;
  }
}

/* Fallback for missing image */
.interior-image {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.interior-image[src=""] {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const interiorImage = document.querySelector('.interior-image');
  const searchBanner = document.querySelector('.search-banner');
  
  if (interiorImage && searchBanner) {
    let ticking = false;

    const updateParallax = () => {
      const scrollY = window.pageYOffset;
      const viewportBottom = scrollY + window.innerHeight;
      const bannerRect = searchBanner.getBoundingClientRect();
      const bannerTop = bannerRect.top + scrollY;
      const bannerHeight = bannerRect.height;

      const progressFromViewportBottom = Math.max(0, viewportBottom - bannerTop);
      const clampedProgress = Math.min(progressFromViewportBottom, bannerHeight);
      const parallaxOffset = clampedProgress * 0.3;
      interiorImage.style.transform = `translate3d(0, ${ -parallaxOffset }px, 0)`;
      ticking = false;
    };

    const onScroll = () => {
      if (!ticking) {
        window.requestAnimationFrame(updateParallax);
        ticking = true;
      }
    };

    // Only observe when banner is near viewport to avoid unnecessary work
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          window.addEventListener('scroll', onScroll, { passive: true });
          updateParallax();
        } else {
          window.removeEventListener('scroll', onScroll);
        }
      });
    }, { root: null, rootMargin: '200px', threshold: 0 });

    io.observe(searchBanner);
  } else {
    // Elements missing; no-op
  }
});
</script>
