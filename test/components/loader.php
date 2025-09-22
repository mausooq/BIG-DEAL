<style>
/* Loader Styles */
.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 0.5s ease, visibility 0.5s ease;
}

.loader-overlay.hidden {
  opacity: 0;
  visibility: hidden;
}

.loader-gif {
  width: 200px;
  height: 200px;
  object-fit: contain;
  margin-bottom: 30px;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.loader-content {
  text-align: center;
  color: white;
}

.loader-text {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 30px;
  text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.loader-text span {
  color: #ffd700;
}

.loader-progress {
  width: 300px;
  height: 4px;
  background: rgba(255,255,255,0.2);
  border-radius: 2px;
  overflow: hidden;
  margin: 0 auto;
}

.progress-line {
  height: 100%;
  background: linear-gradient(90deg, #ffd700, #ffed4e);
  width: 0%;
  transition: width 0.3s ease;
  border-radius: 2px;
  box-shadow: 0 0 10px rgba(255,215,0,0.5);
}

.loader-overlay-gradient {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.1) 100%);
  pointer-events: none;
}

/* Animation for loading text */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.loader-text {
  animation: pulse 2s ease-in-out infinite;
}

/* Responsive Design */
@media (max-width: 768px) {
  .loader-gif {
    width: 150px;
    height: 150px;
    margin-bottom: 20px;
  }
  
  .loader-text {
    font-size: 2rem;
    margin-bottom: 20px;
  }
  
  .loader-progress {
    width: 250px;
  }
}

@media (max-width: 480px) {
  .loader-gif {
    width: 120px;
    height: 120px;
    margin-bottom: 15px;
  }
  
  .loader-text {
    font-size: 1.5rem;
    margin-bottom: 15px;
  }
  
  .loader-progress {
    width: 200px;
  }
}
</style>

<!-- Modern GIF Loader Component -->

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
