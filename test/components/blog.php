<style>
/* Blog Section (scoped) */
.blog-section {
  max-width: 1100px;
  margin: 60px auto;
  margin-bottom: 100px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 36px;
}
.section-header h2 {
  font-weight: 600;
  font-style: SemiBold;
  font-size: 48px;
  line-height: 100%;
  letter-spacing: 0%;
}
.section-header .view-all-btn {
  background: #111;
  color: #fff;
  border: none;
  font-family: DM Sans;
  font-weight: 600;
  font-style: SemiBold;
  font-size: Body/Size Medium;
  line-height: 100%;
  letter-spacing: 0%;
  text-align: center;
  height: 44px;
  border-radius: 15px;
  text-align: center;
  margin: 3.125rem;
  width: 120px;
}
.blog-feature {
  display: flex;
  background: #eeeeee;
  border-radius: 28px;
  overflow: hidden;
}
.blog-image img {
  height: fit-content;
  width: 100%;
  object-fit: cover;
  border-radius: 0;
  display: block;
}
.blog-card {
  color: #373737;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #eeeeee;
  padding: 40px 50px;
  border-radius: 0 28px 28px 0;
}
.blog-read {
  font-weight: 300;
  font-style: Light;
  font-size: 20px;
  line-height: 100%;
  letter-spacing: 0%;
  text-align: center;
  display: inline-block;
  position: relative;
  background: #fff;
  color: #000000;
  border-radius: 14px;
  padding: 4px 16px;
  margin-bottom: 12px;
  width: 30%;
}
.blog-card h3 {
  font-weight: 600;
  font-style: SemiBold;
  font-size: 48px;
  line-height: 100%;
  letter-spacing: 0%;
  padding-bottom: 10px;
}
.blog-card p {
  font-weight: 200;
  font-style: ExtraLight;
  font-size: 24px;
  line-height: 156%;
  letter-spacing: 0%;
  margin-right: 20px;
}
.blog-author { display: flex; align-items: center; gap: 14px; margin-top: 30px; }
.author-img { width: 38px; height: 38px; border-radius: 50%; }
.author-name { font-weight: 400; font-style: Regular; font-size: 24px; line-height: 122%; letter-spacing: 0%; }
.author-role { font-weight: 200; font-style: ExtraLight; font-size: 20px; line-height: 122%; letter-spacing: 0%; }
.blog2, .blog3 { margin-top: 10px; padding-top: 10px; width: 100%; }
.blog-panel { border-radius: 24px; width: 50%; display: flex; flex-direction: column; margin: 0 20px; text-align: start; }
.blog-panel img { width: 100%; border-radius: 24px; object-fit: cover; align-items: center; }
.caption { font-weight: 500; font-style: Medium; font-size: 24px; line-height: 100%; letter-spacing: 0%; margin: 10px 0; color: #000000; }
.date { font-weight: 300; font-style: Light; font-size: 16px; line-height: 100%; letter-spacing: 0%; color: #afabab; }

/* Responsive subset */
@media (max-width: 1024px) {
  .blog-section { padding: 2em; }
}
@media (max-width: 768px) {
  .blog-section { max-width: 100%; margin: 30px auto; padding: 0 20px; }
  .section-header { flex-direction: column; align-items: flex-start; gap: 20px; }
  .section-header h2 { font-size: 1.5rem; line-height: 1.3; }
  .view-all-btn { align-self: flex-end; padding: 10px 16px; font-size: 0.9rem; }
  .blog-feature { flex-direction: column; }
  .blog-image img { width: 100%; height: auto; border-radius: 28px 28px 0 0; }
  .blog-card { border-radius: 0 0 28px 28px; padding: 1.25rem; }
  .blog-card h3 { font-size: 1.1rem; margin-bottom: 12px; }
  .blog-card p { font-size: 0.9rem; line-height: 1.6; margin-bottom: 18px; }
  .blog-author { gap: 12px; }
  .author-img { width: 32px; height: 32px; }
  .author-name { font-size: 0.9rem; }
  .author-role { font-size: 0.85rem; }
  .blog2, .blog3 { margin-top: 5px; padding-top: 0.3125rem; }
  .blog-panel { width: 100%; margin: 0px 5px; padding: 10px 5px; }
  .caption { font-size: 1rem; margin-top: 12px; }
  .date { font-size: 0.85rem; margin-top: 2px; }
}
@media (max-width: 480px) {
  .blog-section { padding: 0 10px; }
  .blog-card { padding: 15px; }
  .blog-card h3 { font-size: 1rem; }
  .blog-card p { font-size: 0.85rem; }
}
</style>

<section class="blog-section ">
  <div class="section-header">
    <h2>Explore our latest blogs for<br>real estate insights</h2>
    <button class="view-all-btn">View all <span>→</span></button>
  </div>
  <div class="blog-feature">
    <div class="blog-image">
      <img src="assets/images/prop/bhouse3.png" alt="Blogimage1">
    </div>
    <div class="blog-card">
      <span class="blog-read">7 min read</span>
      <h3>High-end properties</h3>
      <p>
        Experience the pinnacle of luxury living with our exclusive collection of high-end properties. Featuring elegant villas, premium apartments, and penthouses in prime locations, these homes are designed with world-class amenities and modern architecture to deliver unmatched comfort, sophistication, and lifestyle value.
      </p>
      <div class="blog-author">
        <img src="assets/images/avatar/test1.png" class="author-img" alt="Admin">
        <div>
          <span class="author-name">Admin</span><br>
          <span class="author-role">Software Dev</span>
        </div>
      </div>
    </div>
  </div>
<div class="d-flex blog2">
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse4.png" alt="Living Room">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse5.png" alt="Modern TV Unit">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  <div class="blog-panel">
    <img src="assets/images/prop/bhouse6.png" alt="Contemporary Kitchen">
    <div class="caption">Market Trend</div>
    <div class="date">April 9, 2025</div>
  </div>
  </div>

</section>