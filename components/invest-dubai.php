<?php if (!isset($asset_path)) { $asset_path = 'assets/'; }
// Determine image path based on asset_path
if (strpos($asset_path, '../') === 0) {
  // We're in a subdirectory, use relative paths
  $image_path = '../assets/images/skyline.jpg'; }
  else {
    // We're at root level, use direct paths
     $image_path = 'assets/images/skyline.jpg';
    }

?> <section class="search-banner-body">
  <div class="container-fluid">
    <section class="search-banner">
      <div class="search-banner-container"> <!-- Background Dubai Skyline Image -->
        <div class="interior-background"> <img src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Invest in Dubai Property" class="interior-image" decoding="async" loading="lazy"> </div> <!-- Overlay Banner -->
        <div class="white-banner-overlay"> <!-- Banner Content -->
          <div class="banner-content">
            <div class="content-wrapper">
              <div class="text-content">
                <h2 class="banner-headline"> Invest Dubai Global Property Hub </h2>
                <p class="banner-description"> Experience unmatched growth opportunities in one of the world’s most dynamic property hubs. Explore luxurious apartments, beachfront villas, and commercial investments with exceptional returns. </p> <a href="/products/?category=Dubai%20Property" class="search-now-btn"> EXPLORE INVESTMENTS <span>→</span> </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</section>
<style>
  /* Invest in Dubai Property Styles */
  .search-banner-body {
    margin: 40px 0;
    padding: 0 15px;
  }

  .search-banner {
    position: relative;
    width: 100%;
    height: 30em;
    min-height: 400px;
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
    min-height: 100%;
    z-index: 1;
    overflow: hidden;
  }

  .interior-image {
    width: 100%;
    height: 100%;
    min-height: 100%;
    min-width: 100%;
    object-fit: cover;
    object-position: center center;
    will-change: transform;
    transform: translate3d(0, 0, 0);
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }

  /* Overlay: darker for luxury look */
  .white-banner-overlay {
    background: rgba(0, 0, 0, 0.6);
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
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
    padding: 0;
  }

  .content-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100%;
  }

  .text-content {
    flex: 1;
    width: 100%;
  }

  .banner-headline {
    margin: 0 0 20px 0;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-weight: 700;
    line-height: 1.2;
    font-size: clamp(28px, 4vw, 46px);
    word-wrap: break-word;
  }

  .banner-description {
    margin: 0 auto 30px auto;
    color: #F1FAEE;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(14px, 2vw, 20px);
    line-height: 1.6;
    font-weight: 400;
    max-width: 850px;
    padding: 0 10px;
  }

  .search-now-btn {
    background: linear-gradient(135deg, rgb(0, 0, 0), rgb(48, 46, 46));
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-weight: 800;
    border-radius: 25px;
    min-height: 44px;
    height: auto;
    padding: 12px 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: clamp(12px, 1.5vw, 16px);
    white-space: nowrap;
  }

  .search-now-btn:hover {
    background: #000;
    color: #fff;
    transform: translateY(-2px);
  }

  .search-now-btn span {
    margin-left: 8px;
    display: inline-block;
  }

  /* Responsive Design - Tablet */
  @media (max-width: 1024px) {
    .search-banner {
      height: 28em;
      min-height: 350px;
    }

    .white-banner-overlay {
      padding: 30px 25px;
    }

    .banner-headline {
      margin-bottom: 18px;
    }

    .banner-description {
      margin-bottom: 25px;
      line-height: 1.5;
    }
  }

  /* Responsive Design - Mobile Landscape / Small Tablets */
  @media (max-width: 768px) {
    .search-banner-body {
      margin: 30px 0;
      padding: 0 10px;
    }

    .search-banner {
      height: 25em;
      min-height: 300px;
      border-radius: 8px;
    }

    .interior-background {
      min-height: 100%;
      height: 100%;
    }

    .white-banner-overlay {
      padding: 25px 20px;
    }

    .interior-image {
      min-height: 100%;
      height: 100%;
      width: 100%;
    }

    .banner-headline {
      margin-bottom: 15px;
      line-height: 1.3;
      font-size: 18px;
    }

    .banner-description {
      margin-bottom: 20px;
      line-height: 1.5;
      padding: 0 5px;
      font-size: 9px;
    }

    .search-now-btn {
      padding: 10px 20px;
      min-height: 40px;
    }
  }

  /* Responsive Design - Mobile Portrait */
  @media (max-width: 576px) {
    .search-banner-body {
      margin: 20px 0;
      padding: 0 10px;
    }

    .search-banner {
      height: 22em;
      min-height: 280px;
      border-radius: 8px;
    }

    .interior-background {
      min-height: 100%;
      height: 100%;
    }

    .white-banner-overlay {
      padding: 20px 15px;
    }

    .interior-image {
      min-height: 100%;
      height: 100%;
      width: 100%;
    }

    .banner-headline {
      margin-bottom: 12px;
      line-height: 1.25;
    }

    .banner-description {
      margin-bottom: 18px;
      line-height: 1.4;
      padding: 0;
    }

    .search-now-btn {
      padding: 10px 18px;
      min-height: 38px;
      border-radius: 12px;
      width: auto;
      max-width: 90%;
    }
  }

  /* Responsive Design - Small Mobile */
  @media (max-width: 400px) {
    .search-banner-body {
      margin: 15px 0;
      padding: 0 8px;
    }

    .search-banner {
      height: 20em;
      min-height: 250px;
    }

    .interior-background {
      min-height: 100%;
      height: 100%;
    }

    .white-banner-overlay {
      padding: 15px 12px;
    }

    .interior-image {
      min-height: 100%;
      height: 100%;
      width: 100%;
    }

    .banner-headline {
      margin-bottom: 10px;
    }

    .banner-description {
      margin-bottom: 15px;
      line-height: 1.4;
    }

    .search-now-btn {
      padding: 8px 16px;
      min-height: 36px;
      font-size: 12px;
    }
  }

  /* Extra Large Screens */
  @media (min-width: 1400px) {
    .search-banner {
      height: 35em;
      min-height: 500px;
    }

    .white-banner-overlay {
      padding: 50px;
    }
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
        ticking = false;
      };
      const onScroll = () => {
        if (!ticking) {
          window.requestAnimationFrame(updateParallax);
          ticking = true;
        }
      };
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            window.addEventListener('scroll', onScroll, {
              passive: true
            });
            updateParallax();
          } else {
            window.removeEventListener('scroll', onScroll);
          }
        });
      }, {
        root: null,
        rootMargin: '200px',
        threshold: 0
      });
      io.observe(searchBanner);
    }
  });
</script>