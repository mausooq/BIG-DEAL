let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
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
}

// Next slide click handler
slides.forEach((slide, i) => {
  slide.onclick = () => {
    if (slide.classList.contains('next')) {
      currentSlide = (currentSlide + 1) % slides.length;
      showSlide(currentSlide);
    }
  };
});

function prevSlide() {
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(currentSlide);
}

function autoScrollCarousel(interval = 4000) {
  setInterval(() => {
    nextSlide();
  }, interval);
}

// Add click handlers to dots
dots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    currentSlide = index;
    showSlide(currentSlide);
  });
});

showSlide(currentSlide);
setInterval(() => {
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
}, 4000);

// Initialize the carousel
showSlide(currentSlide);

// Start auto-scrolling the carousel
autoScrollCarousel();

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
  
  viewMore.addEventListener('click', function(e) {
    e.preventDefault();

    if (description.classList.contains('expanded')) {
      description.classList.remove('expanded');
      viewMore.innerHTML = 'View More <span><img src="img/icon/parrowdown.svg" alt="arrow down" class="pdarrow"></span>';
    } else {
      description.classList.add('expanded');
      viewMore.innerHTML = 'View Less <span><img src="img/icon/parrowdown.svg" alt="arrow down" class="pdarrow"></span>';
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