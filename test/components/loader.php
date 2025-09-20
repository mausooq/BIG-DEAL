<!-- Modern GIF Loader Component -->
<link rel="stylesheet" href="assets/css/loader.css">

<div id="loader" class="loader-overlay">
  <img src="assets/building.gif" alt="Building animation" class="loader-gif">
  
  <div class="loader-content">
    <div class="loader-text ">Big<span>Deal</span>.property</div>
    <div class="loader-progress">
      <div class="progress-line"></div>
    </div>
  </div>
  
  <div class="loader-overlay-gradient"></div>
</div>

<script>
// Modern GIF Loader functionality
document.addEventListener('DOMContentLoaded', function() {
  const loader = document.getElementById('loader');
  const gif = document.querySelector('.loader-gif');
  const progressLine = document.querySelector('.progress-line');
  
  let isGifLoaded = false;
  let progressInterval;
  
  // Ensure GIF loads properly
  if (gif) {
    gif.addEventListener('load', function() {
      console.log('GIF loaded successfully');
      isGifLoaded = true;
    });
    
    gif.addEventListener('error', function(e) {
      console.error('GIF loading error:', e);
    });
  }
  
  // Animate progress bar
  function animateProgress() {
    let progress = 0;
    progressInterval = setInterval(function() {
      progress += Math.random() * 15;
      if (progress > 100) progress = 100;
      
      if (progressLine) {
        progressLine.style.width = progress + '%';
      }
      
      if (progress >= 100) {
        clearInterval(progressInterval);
      }
    }, 200);
  }
  
  // Start progress animation
  setTimeout(animateProgress, 500);
  
  // Hide loader when page is fully loaded
  window.addEventListener('load', function() {
    setTimeout(function() {
      if (loader) {
        // Clear progress animation
        if (progressInterval) {
          clearInterval(progressInterval);
        }
        
        // Complete progress bar
        if (progressLine) {
          progressLine.style.width = '100%';
        }
        
        // Hide loader with smooth transition
        setTimeout(function() {
          loader.classList.add('hidden');
          
          // Remove loader from DOM after animation
          setTimeout(function() {
            if (loader.parentNode) {
              loader.parentNode.removeChild(loader);
            }
          }, 1000);
        }, 500);
      }
    }, 2000); // Minimum 2 seconds display time for GIF
  });
  
  // Fallback: hide loader after 8 seconds regardless
  setTimeout(function() {
    if (loader && !loader.classList.contains('hidden')) {
      if (progressInterval) {
        clearInterval(progressInterval);
      }
      
      if (progressLine) {
        progressLine.style.width = '100%';
      }
      
      loader.classList.add('hidden');
      setTimeout(function() {
        if (loader.parentNode) {
          loader.parentNode.removeChild(loader);
        }
      }, 1000);
    }
  }, 8000);
});
</script>
