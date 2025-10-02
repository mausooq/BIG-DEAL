<!-- Compact Loader Component -->
<link rel="stylesheet" href="assets/css/loader.css">

<div id="loader" class="loader-container">
  <div class="loader-content">
    <div class="loader-spinner">
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
    </div>
    <div class="loader-text">Big<span>Deal</span>.property</div>
    <div class="loader-subtitle">Loading...</div>
  </div>
</div>

<script>
// Compact Loader functionality
document.addEventListener('DOMContentLoaded', function() {
  const loader = document.getElementById('loader');
  
  // Show loader initially
  if (loader) {
    loader.style.display = 'flex';
  }
  
  // Hide loader when page is fully loaded
  window.addEventListener('load', function() {
    setTimeout(function() {
      if (loader) {
        // Hide loader with smooth transition
        loader.classList.add('hidden');
        
        // Remove loader from DOM after animation
        setTimeout(function() {
          if (loader.parentNode) {
            loader.parentNode.removeChild(loader);
          }
        }, 800);
      }
    }, 1500); // Show for 1.5 seconds
  });
  
  // Fallback: hide loader after 5 seconds regardless
  setTimeout(function() {
    if (loader && !loader.classList.contains('hidden')) {
      loader.classList.add('hidden');
      setTimeout(function() {
        if (loader.parentNode) {
          loader.parentNode.removeChild(loader);
        }
      }, 800);
    }
  }, 5000);
});
</script>