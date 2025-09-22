<style>

<!-- Connect With Our Experts Section -->
<style>
/* Lets Connect (scoped) */
.blac2 { padding: 10px; background-color: #222; border-radius: 0px 0px 25px 0px; color: #fff; font-weight: 500; font-style: Medium; font-size: 3.125rem; line-height: 150%; letter-spacing: 0%; }
.blac2-sub { padding: 10px; margin-left: 2%; font-weight: 300; font-style: Light; font-size: 20px; line-height: 150%; letter-spacing: 9%; }
.btn-stack { position: relative; display: inline-block; height: 48px; }
.btn-black { position: absolute; top: 0px; left: 25px; width: 170px; height: 40px; border-radius: 25px; background: #000; z-index: 0; border: none; display: block; }
.btn-black .rarrow { z-index: 0; text-align: right; }
.btn-red { position: absolute; top: 0; left: 0; width: 200px; height: 40px; border-radius: 25px; background: #ef1515; color: #fff; font-weight: 600; font-size: 1rem; border: none; z-index: 1; cursor: pointer; transition: filter 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; justify-content: center; align-items: center; }
.btn-red:hover { filter: brightness(1.1); }
.section-divider { border: none; border-bottom: 2px solid #222; margin-right: 10px; width: 100%; }
.house-img { max-height: 100%; width: auto; margin-right: 0; margin-top: -245px; z-index: 0; }
.contact-section { margin-top: 35px; }
.contact-section .contact-btn-title { font-size: 1em; display: inline-block; white-space: nowrap; justify-content: center; align-items: center; padding: 0 5em; text-align: center; width: auto;margin: 0; }
.btn-stack { margin-left: 1%; }
.btn-black { padding-left: 11.5em; display: flex; justify-content: end; }
.btn-red { padding-left: -10em; width: 140px; }
.btn-black .arrow-btn { display: flex; justify-content: center; }
.btn-black .arrow-btn img { width: 1.4em; margin-right: 0.6em; border: none; }

/* Responsive */
@media (max-width: 1024px) {
  .blog-section { padding: 2em; }
  .contact-section .house-img { width: 48%; margin-right: 15px; }
  .contact-section .section-divider { margin-bottom: 82px; }
  .contact-section .blac2-sub { font-size: 1.125rem; }
  .contact-section .blac2 { font-size: 2em !important; }
  .btn-stack { position: relative; display: inline-block; height: 48px; margin-left: 1%; }
  .contact-section .btn-black { position: absolute; top: 0; left: 25px; width: 170px; height: 40px; border-radius: 25px; background: #000; display: block; padding: 0; }
  .contact-section .btn-red { position: absolute; top: 0; left: 0; width: 135px; height: 40px; border-radius: 25px; display: flex; align-items: center; justify-content: center; padding: 0; }
  .contact-section .btn-red .contact-btn-title { white-space: nowrap; margin: 0; }
  .contact-section .btn-black .arrow-btn { padding: 0 0.8em; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 0 25px 25px 0; }
  .contact-section .btn-black .arrow-btn img { width: 1.1em; margin: 0; }
  .section-divider { margin: 0 18px; }
  .contact-section h2 { font-size: 1.8rem; }
  .contact-section p { font-size: 0.95rem; }
}
@media (max-width: 768px) {
  .btn-stack { position: relative; display: inline-block; height: 48px; margin-left: 0; }
  .contact-section .btn-black { position: absolute; top: 0; left: 25px; width: 160px; height: 40px; border-radius: 25px; background: #000; display: block; padding: 0; }
  .contact-section .btn-red { position: absolute; top: 0; left: 0; width: 125px !important; height: 40px; border-radius: 25px; display: flex; align-items: center; justify-content: center; padding: 0; }
  .contact-section .btn-red .contact-btn-title { font-size: 0.85em !important; white-space: nowrap; margin: 0; }
  .contact-section .btn-black .arrow-btn { padding: 0 0.7em; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 0 25px 25px 0; }
  .contact-section .btn-black .arrow-btn img { width: 1.05em; }
  .contact-section .blac2 { width: 100%; margin: 0 0 0.75rem 0; }
  .contact-section .d-flex.align-items-center.flex-column.flex-md-row { flex-direction: row; justify-content: space-between; align-items: center; }
  .contact-section .section-divider { width: 60% !important; margin-left: 0 !important; margin-right: 0; align-self: flex-start; }
  .contact-section .house-img { width: 60% !important; max-width: 520px; margin: 0 0 0 auto; display: block; position: static; margin-left: auto; margin-right: 0; }
  .contact-section h2 { font-size: 1.4rem; }
  .contact-section p { font-size: 0.85rem; margin-bottom: 20px; }
}
@media (max-width: 480px) {
  .btn-black, .btn-red { width: auto; height: 40px; font-size: 1rem; }
  .btn-stack { position: relative; display: inline-block; height: 48px; flex-direction: row; gap: 0; }
  .blac2 { font-size: 1.75rem; padding: 15px; margin: 0; width: 100%; }
  .blac2-sub { font-size: 0.75rem; padding: 0.625rem 0.9375rem; }
  .contact-section .btn-black { position: absolute; top: 0; left: 25px; width: 160px; height: 40px; border-radius: 25px; background: #000; display: block; margin: 0; padding: 0; }
  .contact-section .btn-red { position: absolute; top: 0; left: 0; width: 125px; height: 40px; border-radius: 25px; display: flex; align-items: center; justify-content: center; padding: 0; margin: 0; }
  .contact-section .btn-red .contact-btn-title { font-size: 0.75em !important; white-space: nowrap; padding: 0; margin: 0; }
  .contact-section .btn-black .arrow-btn { position: static; padding: 0 5.5em; height: 40px; display: flex; align-items: center; justify-content: center; }
  .contact-section .btn-black .arrow-btn img { width: 0.95em; margin-right: -8em; }
  .contact-section .d-flex.align-items-center.flex-column.flex-md-row { flex-direction: row; align-items: center; }
  .section-divider { flex: 0 0 60%; width: 60%; position: static; margin-left: 0 !important; margin-right: 0; align-self: auto; margin-top: 0.75rem; }
  .contact-section .house-img { flex: 0 0 auto; width: 100%; max-width: 320px; margin: 0 0 0 auto !important; position: static; display: block; align-self: auto; }
  .contact-section h2 { font-size: 1.2rem; }
  .contact-section p { font-size: 0.8rem; margin-bottom: 15px; }
}
</style>
<section class="contact-section">
  <h2 class="blac2 col-md-6">
    Connect With Our Experts
  </h2>
  <p class="blac2-sub">
    Our team is here to help you find your perfect home.
  </p>
   <div class="btn-stack d-flex justify-content-start">
    <a href="#" class="text-decoration-none">
    <div class="btn-black d-flex align-items-center">
    <div class="btn-red">
      <h1 class="contact-btn-title">
        Contact Us Now
      </h1>
    </div>
    <div class="arrow-btn">
      <img src="<?php echo $asset_path; ?>images/icon/rightarrow.svg" alt="">
      </div>
    </div>
  </a>
  </div>
      <div class="d-flex align-items-center flex-column flex-md-row">
        <hr class="section-divider">
        <img class="house-img" src="<?php echo $asset_path; ?>images/prop/bhouse.png" alt="">
      </div>
</section>
