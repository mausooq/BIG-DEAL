<style>
/* Testimonials (scoped) */
.testimonials { padding: 20px 0; width: 100%; }
.test-head { font-size: 48px; font-weight: 700; font-style: Bold; margin-top: -80px; letter-spacing: 1%; }
.test-sub { font-weight: 400; font-style: Regular; font-size: 16px; letter-spacing: 1px; }
.quote { justify-items: center; margin-top: -200px; margin-right: 15px; }
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
  .testimonial-nav { bottom: 21px !important; right: 0px !important; height: 3em !important; }
  .test-head { font-size: 22px; margin-top: 10px; padding-top: 20px; }
  .test-sub { font-size: 0.625rem; }
  .testimonial-content { flex-direction: column; align-items: center; }
  .testimg { margin-right: 5px; margin-bottom: 0.9375rem; position: absolute; right: 10px; left: auto; }
  .quote { display: none; }
  .testimonial-carousel { max-width: 90%; margin-left: 0; padding: 10px; font-size: 0.625rem; text-align: justify; }
  .testimonial-author img { width: 50px; height: 50px; }
  .testimonial-author-info h5 { font-size: 0.875rem; }
  .testimonial-author-info p { font-size: 5px; }
  .stars { font-size: 5px; }
  .testimonial-nav { position: static; bottom: auto; right: auto; margin-top: 20px; justify-content: center; }
  .testimonial-slide { padding: 10px; }
  .testimonial-nav img { width: 25px; height: 25px; margin: 30px 5px; }
}
@media (max-width: 480px) {
  .about-page .test-head { margin-top: 20px; font-size: 1.75rem; }
  .test-sub { line-height: 2; }
  .about-page .test-sub { font-size: 0.75rem; }
  .testimonial-carousel { padding: 10px; }
  .testimonial-author img { width: 40px; height: 40px; }
  .testimonial-author-info h5 { font-size: 0.875rem; }
  .testimonial-author-info p { font-size: 11px; }
  .testimonial-nav img { width: 18px; height: 18px; }
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