<!-- Modern Video Loader Component -->
<link rel="stylesheet" href="assets/css/loader.css">

<div id="loader" class="loader-overlay">
  <video autoplay muted loop playsinline class="loader-video">
    <source src="assets/building.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>
  
  <div class="loader-content">
    <div class="loader-text">Building Your Dream Home...</div>
    <div class="loader-progress">
      <div class="progress-line"></div>
    </div>
  </div>
  
  <div class="loader-overlay-gradient"></div>
</div>

<script>
// Modern Video Loader functionality
document.addEventListener('DOMContentLoaded', function() {
  const loader = document.getElementById('loader');
  const video = document.querySelector('.loader-video');
  const progressLine = document.querySelector('.progress-line');
  
  let isVideoLoaded = false;
  let progressInterval;
  
  // Ensure video plays smoothly
  if (video) {
    video.addEventListener('loadstart', function() {
      console.log('Video loading started');
    });
    
    video.addEventListener('canplay', function() {
      console.log('Video can start playing');
      isVideoLoaded = true;
    });
    
    video.addEventListener('error', function(e) {
      console.error('Video loading error:', e);
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
        // Pause video before hiding
        if (video) {
          video.pause();
        }
        
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
    }, 2000); // Minimum 2 seconds display time for video
  });
  
  // Fallback: hide loader after 8 seconds regardless
  setTimeout(function() {
    if (loader && !loader.classList.contains('hidden')) {
      if (video) {
        video.pause();
      }
      
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
