<style>
/* Testimonials (scoped) */
.testimonials { padding: 20px 0; width: 100%; }
.test-head { font-size: 48px; font-weight: 700; font-style: Bold; margin-top: -80px; letter-spacing: 1%; }
.test-sub { font-weight: 400; font-style: Regular; font-size: 16px; letter-spacing: 1px; }
/* Desktop-only quote positioning to avoid conflicts on mobile/tablet */
@media (min-width: 1025px) {
  .quote { justify-items: center; margin-top: -200px; margin-right: 15px; }
}
.testimg { position: relative; display: flex; justify-content: flex-end; align-items: center; gap: 10px; z-index: 1; margin-right: 70px; }
.testimonial-content { position: relative; margin-top: 80px; }
.testimonial-carousel {
  max-width: 730px; padding: 10px; padding-bottom: 0; margin-top: -60px; margin-left: 50px;
  border-radius: 15px 15px 40px 15px; border: 2px solid #000; background: #fff; position: relative; z-index: -1;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.testimonial-text p { font-size: 15px; font-weight: 500; font-style: Medium; line-height: 150%; letter-spacing: 0%; color: #333; margin: 0 0 25px 0; }
.testimonial-author { display: flex; align-items: center; margin-bottom: 20px; }
.testimonial-author img { width: 60px; height: 60px; border-radius: 50%; margin-right: 15px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.testimonial-author-info h5 { margin: 0; font-weight: 700; font-style: Bold; font-size: 40px; line-height: 150%; letter-spacing: 1%; color: #222; }
.testimonial-author-info p { margin: 4px 0 0; color: #666; font-size: 16px; font-family: "Roboto", sans-serif; font-weight: 200; font-style: ExtraLight; line-height: 100%; }
.stars { color: #ffd700; font-size: 22px; letter-spacing: 4px; margin: 0 0 25px 0; display: inline-block; }
.testimonial-nav { position: absolute; bottom: 70px; right: 70px; justify-content: flex-end; width: 100%; height: 60px; margin: 0px 10px; cursor: pointer; transition: all 0.3s ease; display: flex; gap: 0.5rem; }
.testimonial-nav :hover { opacity: 50%; border-color: #999; }
.testimonial-slide { display: none; opacity: 0; transition: opacity 0.5s ease-in-out; }
.testimonial-slide.active { display: block; opacity: 1; }
.testimonial-nav img:hover { opacity: 0.7; }

/* Responsive */
@media (max-width: 1024px) {
  /* Mirror 768px styling */
  .test-head { font-size: 1.75em; margin-top: -1.25em; }
  .test-sub { font-size: 0.8125em; }
  .testimonial-content { display: flex; flex-direction: row-reverse; align-items: flex-start; justify-content: space-between; flex-wrap: nowrap; gap: 1em; margin-top: 10em; }
  /* Quote positioning like 768px */
  .testimonials .testimg .quote { display: block !important; position: absolute !important; left: -10em !important; right: auto !important; top: -2em !important; width: 5em !important; height: auto !important; margin: 0 !important; transform: none !important; z-index: 1; }
  .testimg { position: relative; display: flex; justify-content: flex-end; align-items: center; margin-right: 2.5em; flex: 0 0 28% !important; overflow: visible; }
  .testimg img.img-fluid { position: absolute; right: 2em; width: 15em; max-width: none; height: auto; margin-left: 0; margin-top: -8em; z-index: 3; }
  .testimonials .testimg .quote { display: block !important; position: absolute !important; left: -10em !important; right: auto !important; top: -9.625em !important; margin: 0 !important; z-index: 2 !important; transform: none !important; width: 5em !important; height: auto !important; }
  .testimonial-carousel { width: 72% !important; max-width: none; margin-left: 1.875em; margin-top: -2em; padding: 0.75em; flex: 0 0 62% !important; z-index: 0; }
  .testimonial-author img { width: 3em; height: 3em; }
  .testimonial-author-info h5 { font-size: 1.125em; }
  .testimonial-author-info p { font-size: 0.75em; }
  .testimonial-author { margin-bottom: 0.5em !important; }
  .testimonial-slide p { margin: 0.375em 0 0.5em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; overflow: hidden; }
  .stars { font-size: 0.875em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: absolute; bottom: 3.125em; right: 3.125em; top: 2.5em; }
  .testimonial-slide { padding: 0; }
  .testimonial-nav img { width: 1.5em; height: 1.5em; }
}
@media (max-width: 768px) {
  /* Keep desktop structure on small tablets */
  .test-head { font-size: 1.75em; margin-top: -1.25em; }
  .test-sub { font-size: 0.8125em; }
  .testimonial-content { display: flex; flex-direction: row-reverse; align-items: flex-start; justify-content: space-between; flex-wrap: nowrap; gap: 1em; margin-top: 10em; }
  /* Hide distracting quote on small tablets */
  .testimonials .testimg .quote { display: block !important; position: absolute !important; left: -10em !important; right: auto !important; top: -2em !important; width: 5em !important; height: auto !important; margin: 0 !important; transform: none !important; z-index: 1; }
  .testimg { position: relative; display: flex; justify-content: flex-end; align-items: center; margin-right: 2.5em; flex: 0 0 28% !important; overflow: visible; }
  .testimg img.img-fluid { position: absolute; right: -1em; width: 15em; max-width: none; height: auto; margin-left: 0; margin-top: -8em; z-index: 3; }
  .testimonials .testimg .quote { display: block !important; position: absolute !important; left: -10em !important; right: auto !important; top: -9.625em !important; margin: 0 !important; z-index: 2 !important; transform: none !important;width: 5em !important; height: auto !important; }
  .testimonial-carousel { width: 72% !important; max-width: none; margin-left: 1.875em; margin-top: -2em; padding: 0.75em; flex: 0 0 50% !important; z-index: 0; }
  .testimonial-author img { width: 3em; height: 3em; }
  .testimonial-author-info h5 { font-size: 1.125em; }
  .testimonial-author-info p { font-size: 0.75em; }
  .testimonial-author { margin-bottom: 0.5em !important; }
  .testimonial-slide p { margin: 0.375em 0 0.5em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; overflow: hidden; }
  .stars { font-size: 0.875em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: absolute; bottom: 3.125em; right: 3.125em; top: 2.5em; }
  .testimonial-nav img { width: 1.5em; height: 1.5em; }
}
@media (max-width: 480px) {
  /* Keep desktop structure on mobile (scaled) */
  .test-head { margin-top: 0; font-size: 1.5em;}
  .test-sub { font-size: 0.75em; line-height: 1.5; }
  .testimonial-content { display: flex; flex-direction: row-reverse; align-items: flex-start; justify-content: space-between; flex-wrap: nowrap; gap: 0.75em; margin-top: 3em;margin-left: 4em;}
  /* Hide distracting quote on mobile */
  .testimg { position: relative; display: flex; justify-content: flex-end; align-items: center; margin-right: 12px; flex: 1 1 38%; overflow: visible; }
  .testimg img.img-fluid { width: 12em !important; max-width: none !important; height: auto; margin-left: auto; position: relative; left: -2em; z-index: 10; margin-top: -2em; }
  /* Smaller quote pinned to start */
  .testimonials .testimg .quote { display: block !important; position: absolute !important; left: -10em !important; right: auto !important; top: -2.5em !important; width: 5em !important; height: auto !important; margin: 0 !important; transform: none !important; z-index: 1; }
  .testimonial-carousel { width: 62%; max-width: none; margin-left: 0.625em; margin-top: -1.25em; padding: 0.625em; flex: 1 1 50%; z-index: 0; }
  .testimonial-author img { width: 2.25em; height: 2.25em; }
  .testimonial-author-info h5 { font-size: 0.9375em; }
  .testimonial-author-info p { font-size: 0.6875em; }
  .testimonial-carousel { padding: 0.5em !important;margin-top: 6em; }
  .testimonial-author { margin-bottom: 0.375em !important; }
  .testimonial-slide p { margin: 0.25em 0 0.375em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
  .stars { font-size: 0.8125em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: absolute; bottom: 1.875em; right: 1.875em; top: 8em; }
  .testimonial-nav img { width: 1.25em; height: 1.25em; }
}
</style>
<section class="testimonials">
      <div class="container">
        <h2 class="test-head">Testimonials</h2>
        <p class="test-sub">Stories from people who found their perfect space</p>
        
        <div class="testimonial-content">
          <div class="testimg">
            <img src="<?php echo $asset_path; ?>images/icon/quote.svg" alt="quote" class="quote">
            <img src="<?php echo $asset_path; ?>images/prop/prop5.png" alt="House" class="img-fluid">
          </div>

          <div class="testimonial-carousel">
            <div class="testimonial-slide active">
              <div class="testimonial-author">
                <img src="<?php echo $asset_path; ?>images/avatar/test1.png" alt="Munazza">
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
                <img src="<?php echo $asset_path; ?>images/avatar/test2.png" alt="John Doe">
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
                <img src="<?php echo $asset_path; ?>images/avatar/test3.png" alt="Jane Smith">
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
            <img src="<?php echo $asset_path; ?>images/icon/prev.svg" alt="previous" id="testimonial-prev">
            <img src="<?php echo $asset_path; ?>images/icon/next.svg" alt="next" id="testimonial-next">
          </div>
        </div>
    </section>