let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.dot');

// Modern carousel elements
const modernSlides = document.querySelectorAll('.modern-carousel-slide');
const modernDots = document.querySelectorAll('.modern-dot');
let modernCurrentSlide = 0;

// Small carousel elements
const smallSlides = document.querySelectorAll('.small-carousel-slide');
const smallDots = document.querySelectorAll('.small-dot');
let smallCurrentSlide = 0;

// Compact carousel elements - wait for DOM to be ready
let compactCards = [];
let compactDots = [];
let compactTrack = null;
let compactCurrentSlide = 0;

// Initialize compact carousel elements when DOM is ready
function initCompactCarousel() {
  compactCards = document.querySelectorAll('.compact-property-card');
  compactDots = document.querySelectorAll('.compact-dot');
  compactTrack = document.getElementById('compact-carousel-track');
}

function showOldSlide(index) {
  // Handle old carousel
  if (slides.length > 0) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active', 'next');
      if (i === index) {
        slide.classList.add('active');
      } else if (i === (index + 1) % slides.length) {
        slide.classList.add('next');
      }
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });
    currentSlide = index;
  }
}

function showModernSlide(index) {
  // Handle modern carousel
  if (modernSlides.length > 0) {
    modernSlides.forEach((slide, i) => {
      slide.classList.remove('active', 'next', 'prev');
      if (i === index) {
        slide.classList.add('active');
      } else if (i === (index + 1) % modernSlides.length) {
        slide.classList.add('next');
      } else if (i === (index - 1 + modernSlides.length) % modernSlides.length) {
        slide.classList.add('prev');
      }
    });
    modernDots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });
    modernCurrentSlide = index;
  }
}

// Global function for onclick handlers (maintains backward compatibility)
function showSlide(index) {
  // Try modern carousel first
  if (modernSlides.length > 0) {
    showModernSlide(index);
  } else if (slides.length > 0) {
    // Fall back to old carousel
    showOldSlide(index);
  }
}

function nextSlide() {
  if (slides.length > 0) {
    currentSlide = (currentSlide + 1) % slides.length;
    showOldSlide(currentSlide);
  }
  if (modernSlides.length > 0) {
    modernCurrentSlide = (modernCurrentSlide + 1) % modernSlides.length;
    showModernSlide(modernCurrentSlide);
  }
}

// Next slide click handler for old carousel
slides.forEach((slide, i) => {
  slide.onclick = () => {
    if (slide.classList.contains('next')) {
      currentSlide = (currentSlide + 1) % slides.length;
      showOldSlide(currentSlide);
    }
  };
});

// Next slide click handler for modern carousel
modernSlides.forEach((slide, i) => {
  slide.onclick = () => {
    if (slide.classList.contains('next') || slide.classList.contains('prev')) {
      const clickedIndex = Array.from(modernSlides).indexOf(slide);
      modernCurrentSlide = clickedIndex;
      showModernSlide(modernCurrentSlide);
    }
  };
});

function prevSlide() {
  if (slides.length > 0) {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showOldSlide(currentSlide);
  }
  if (modernSlides.length > 0) {
    modernCurrentSlide = (modernCurrentSlide - 1 + modernSlides.length) % modernSlides.length;
    showModernSlide(modernCurrentSlide);
  }
}

// Add click handlers to dots (old carousel)
dots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    currentSlide = index;
    showOldSlide(currentSlide);
  });
});

// Add click handlers to modern dots
modernDots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    modernCurrentSlide = index;
    showModernSlide(modernCurrentSlide);
  });
});

function showSmallSlide(index) {
  // Handle small carousel
  if (smallSlides.length > 0) {
    smallSlides.forEach((slide, i) => {
      slide.classList.remove('active', 'next', 'prev');
      if (i === index) {
        slide.classList.add('active');
      } else if (i === (index + 1) % smallSlides.length) {
        slide.classList.add('next');
      } else if (i === (index - 1 + smallSlides.length) % smallSlides.length) {
        slide.classList.add('prev');
      }
    });
    smallDots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });
    smallCurrentSlide = index;
  }
}

// Next slide click handler for small carousel
smallSlides.forEach((slide, i) => {
  slide.onclick = () => {
    if (slide.classList.contains('next') || slide.classList.contains('prev')) {
      const clickedIndex = Array.from(smallSlides).indexOf(slide);
      smallCurrentSlide = clickedIndex;
      showSmallSlide(smallCurrentSlide);
    }
  };
});

// Add click handlers to small dots
smallDots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    smallCurrentSlide = index;
    showSmallSlide(smallCurrentSlide);
  });
});

let isScrollingProgrammatically = false;

function showCompactSlide(index) {
  if (compactCards.length > 0 && compactTrack) {
    isScrollingProgrammatically = true;
    
    // Calculate scroll position - use only original cards (first half)
    const originalCardsCount = Math.floor(compactCards.length / 2);
    let scrollPosition = 0;
    
    // Ensure index is within original cards range
    const normalizedIndex = index % originalCardsCount;
    
    for (let i = 0; i < normalizedIndex && i < originalCardsCount; i++) {
      scrollPosition += compactCards[i].offsetWidth + 20; // 20px is gap
    }
    
    compactTrack.scrollTo({
      left: scrollPosition,
      behavior: 'smooth'
    });
    
    // Update dots (only show dots for original set)
    const dotIndex = normalizedIndex;
    compactDots.forEach((dot, i) => {
      dot.classList.toggle('active', i === dotIndex);
    });
    compactCurrentSlide = normalizedIndex;
    
    // Reset flag after scroll completes
    setTimeout(() => {
      isScrollingProgrammatically = false;
    }, 600);
  }
}

// Add click handlers to compact dots
compactDots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    compactCurrentSlide = index;
    showCompactSlide(compactCurrentSlide);
  });
});

// Initialize compact carousel scroll detection
if (compactTrack && compactCards.length > 0) {
  let scrollTimeout;
  const originalCardsCount = Math.floor(compactCards.length / 2);
  
  // Calculate original set width for scroll detection
  let originalSetWidth = 0;
  if (originalCardsCount > 0) {
    for (let i = 0; i < originalCardsCount; i++) {
      originalSetWidth += compactCards[i].offsetWidth + 20; // 20px gap
    }
  }
  
  compactTrack.addEventListener('scroll', () => {
    if (isScrollingProgrammatically) return; // Prevent updates during programmatic scrolling
    
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
      if (compactCards.length === 0 || originalCardsCount === 0) return;
      
      let scrollLeft = compactTrack.scrollLeft;
      // Normalize scroll position if beyond original set
      if (scrollLeft >= originalSetWidth) {
        scrollLeft = scrollLeft % originalSetWidth;
      }
      
      scrollLeft += (compactTrack.offsetWidth / 2);
      let currentIndex = 0;
      let minDistance = Infinity;
      
      // Only check original cards (first half) for dot navigation
      for (let i = 0; i < originalCardsCount; i++) {
        const card = compactCards[i];
        const cardLeft = card.offsetLeft;
        const cardCenter = cardLeft + (card.offsetWidth / 2);
        
        const distance = Math.abs(scrollLeft - cardCenter);
        
        if (distance < minDistance) {
          minDistance = distance;
          currentIndex = i;
        }
      }
      
      if (currentIndex !== compactCurrentSlide && currentIndex >= 0 && currentIndex < originalCardsCount) {
        compactCurrentSlide = currentIndex;
        compactDots.forEach((dot, i) => {
          dot.classList.toggle('active', i === compactCurrentSlide);
        });
      }
    }, 150);
  });
}

// Make showSlide available globally for onclick handlers
window.showSlide = showSlide;
window.showModernSlide = showModernSlide;
window.showSmallSlide = showSmallSlide;
window.showCompactSlide = showCompactSlide; // Already updated above

// Initialize carousels
if (slides.length > 0) {
  showOldSlide(currentSlide);
}
if (modernSlides.length > 0) {
  showModernSlide(modernCurrentSlide);
}
if (smallSlides.length > 0) {
  showSmallSlide(smallCurrentSlide);
}

// Auto-scroll carousels (single interval for all)
if (slides.length > 0 || modernSlides.length > 0 || smallSlides.length > 0 || compactCards.length > 0) {
  setInterval(() => {
    nextSlide();
    // Also auto-scroll small carousel
    if (smallSlides.length > 0) {
      smallCurrentSlide = (smallCurrentSlide + 1) % smallSlides.length;
      showSmallSlide(smallCurrentSlide);
    }
  }, 4000);
}

// Initialize compact carousel on DOM ready
document.addEventListener('DOMContentLoaded', function() {
  initCompactCarousel();
  
  // Continuous auto-scroll for compact carousel (LEFT TO RIGHT movement)
  if (compactTrack && compactCards.length > 0) {
    let position = 0;
    let isPaused = false;
    let animationId = null;
    
    // Calculate width of one set (divide by 3 since we render 3 sets)
    const cardCount = Math.floor(compactCards.length / 3);
    let oneSetWidth = 0;
    if (cardCount > 0) {
      for (let i = 0; i < cardCount; i++) {
        oneSetWidth += compactCards[i].offsetWidth + 20; // 20px gap
      }
    }
    
    function animate() {
      if (isPaused) {
        animationId = requestAnimationFrame(animate);
        return;
      }
      
      // Move LEFT to RIGHT: decrease position (transform: translateX goes negative)
      position -= 0.6; // Slow speed
      
      // Reset when we've moved one full set
      if (Math.abs(position) >= oneSetWidth) {
        position = 0;
      }
      
      if (compactTrack) {
        compactTrack.style.transform = `translateX(${position}px)`;
      }
      
      animationId = requestAnimationFrame(animate);
    }
    
    // Pause on hover
    const section = compactTrack.closest('.compact-carousel-section');
    if (section) {
      section.addEventListener('mouseenter', () => {
        isPaused = true;
      });
      
      section.addEventListener('mouseleave', () => {
        isPaused = false;
        if (!animationId) {
          animate();
        }
      });
    }
    
    // Start animation
    animate();
  }
});

// Testimonial Carousel
let currentTestimonial = 0;
const testimonialSlides = document.querySelectorAll('.testimonial-slide');
const testimonialPrev = document.getElementById('testimonial-prev');
const testimonialNext = document.getElementById('testimonial-next');

function showTestimonial(index) {
  testimonialSlides.forEach((slide, i) => {
    slide.classList.toggle('active', i === index);
  });
}

function nextTestimonial() {
  currentTestimonial = (currentTestimonial + 1) % testimonialSlides.length;
  showTestimonial(currentTestimonial);
}

function prevTestimonial() {
  currentTestimonial = (currentTestimonial - 1 + testimonialSlides.length) % testimonialSlides.length;
  showTestimonial(currentTestimonial);
}

if (testimonialPrev && testimonialNext) {
  testimonialPrev.addEventListener('click', prevTestimonial);
  testimonialNext.addEventListener('click', nextTestimonial);
}

// FAQ Toggle Functionality
const faqItems = document.querySelectorAll('.FaqQ-item');

faqItems.forEach(item => {
  const title = item.querySelector('.FaqQ-title');
  const arrow = title.querySelector('.farrow');

  title.addEventListener('click', () => {
    // Close all other items
    faqItems.forEach(i => {
      if (i !== item) {
        i.classList.remove('active');
        const arr = i.querySelector('.farrow');
        arr.classList.remove('up');
        arr.classList.add('down');
      }
    });

    // Toggle current item
    if (item.classList.contains('active')) {
      item.classList.remove('active');
      arrow.classList.remove('up');
      arrow.classList.add('down');
    } else {
      item.classList.add('active');
      arrow.classList.remove('down');
      arrow.classList.add('up');
    }
  });
});

// search box 
var search=document.getElementById('search');

search.addEventListener('focus',(event)=>{

  document.getElementById('search-wrapper').style.border="1px solid #1dbf73";

});

search.addEventListener('focusout',(event)=>{

  document.getElementById('search-wrapper').style.border="1px solid rgba(0, 0, 0, 0.276)";

});





document.addEventListener('DOMContentLoaded', function() {
  const viewMore = document.querySelector('.view-more');
  const description = document.querySelector('.property-desc .pdesc');
  // If page provides its own handler (e.g., product details), skip global wiring to avoid double-toggle
  if (typeof window.toggleDescription === 'function') return;
  if (!viewMore || !description) return;

  viewMore.addEventListener('click', function(e) {
    e.preventDefault();

    var label = viewMore.querySelector('.label');
    var icon = viewMore.querySelector('.pdarrow, .pdarrow-desc, .pdarrow-table');

    if (description.classList.contains('expanded')) {
      description.classList.remove('expanded');
      if (label) label.textContent = 'View More';
      if (icon) { icon.style.transform = 'rotate(0deg)'; icon.style.transition = 'transform 150ms ease'; }
    } else {
      description.classList.add('expanded');
      if (label) label.textContent = 'View Less';
      if (icon) { icon.style.transform = 'rotate(180deg)'; icon.style.transition = 'transform 150ms ease'; }
    }
  });
});


function setupDoubleRange(minRangeId, maxRangeId, sliderRangeId, minLabelId, maxLabelId) {
    const minRange = document.getElementById(minRangeId);
    const maxRange = document.getElementById(maxRangeId);
    const sliderRange = document.getElementById(sliderRangeId);
    const minLabel = document.getElementById(minLabelId);
    const maxLabel = document.getElementById(maxLabelId);
    const min = parseInt(minRange.min);
    const max = parseInt(maxRange.max);

    function updateRange() {
      let minVal = parseInt(minRange.value);
      let maxVal = parseInt(maxRange.value);

      if (minVal > maxVal - 100) {
        minVal = maxVal - 100;
        minRange.value = minVal;
      }
      if (maxVal < minVal + 100) {
        maxVal = minVal + 100;
        maxRange.value = maxVal;
      }

      const rangeWidth = max - min;
      const leftPercent = ((minVal - min) / rangeWidth) * 100;
      const rightPercent = ((maxVal - min) / rangeWidth) * 100;

      sliderRange.style.left = leftPercent + '%';
      sliderRange.style.width = (rightPercent - leftPercent) + '%';

      minLabel.textContent = minVal;
      maxLabel.textContent = maxVal;
    }

    minRange.addEventListener('input', updateRange);
    maxRange.addEventListener('input', updateRange);

    updateRange();
  }

  setupDoubleRange('minRange1', 'maxRange1', 'sliderRange1', 'minLabel1', 'maxLabel1');
  setupDoubleRange('minRange2', 'maxRange2', 'sliderRange2', 'minLabel2', 'maxLabel2');

  // Mobile Filters toggle
  const filterToggleBtn = document.querySelector('.filter-toggle-btn');
  const filterSidebar = document.getElementById('filterSidebar');
  const filterBackdrop = document.querySelector('.filter-backdrop');
  const filterCloseBtn = document.querySelector('.filter-close-btn');

  function openFilters() {
    if (!filterSidebar) return;
    filterSidebar.classList.add('open');
    if (filterBackdrop) filterBackdrop.hidden = false;
    if (filterToggleBtn) filterToggleBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeFilters() {
    if (!filterSidebar) return;
    filterSidebar.classList.remove('open');
    if (filterBackdrop) filterBackdrop.hidden = true;
    if (filterToggleBtn) filterToggleBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  if (filterToggleBtn) {
    filterToggleBtn.addEventListener('click', openFilters);
  }
  if (filterCloseBtn) {
    filterCloseBtn.addEventListener('click', closeFilters);
  }
  if (filterBackdrop) {
    filterBackdrop.addEventListener('click', closeFilters);
  }