<style>
/* Contact Section Styles */
.contact-section {
  padding: 80px 0;
  background: #f8f9fa;
}

.blac2 {
  font-size: 3rem;
  font-weight: 700;
  color: #333;
  line-height: 1.2;
  margin-bottom: 20px;
}

.blac2-sub {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 40px;
  line-height: 1.6;
}

.btn-stack {
  margin-left: 1%;
  margin-bottom: 60px;
}

.btn-black {
  display: flex;
  align-items: center;
  background: #333;
  border-radius: 15px;
  padding: 0;
  overflow: hidden;
  transition: all 0.3s ease;
  text-decoration: none;
  color: inherit;
}

.btn-black:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  text-decoration: none;
  color: inherit;
}

.btn-red {
  background: #e14c4c;
  padding: 20px 30px;
  border-radius: 15px 0 0 15px;
  transition: all 0.3s ease;
}

.btn-red:hover {
  filter: brightness(1.1);
}

.contact-btn-title {
  color: white;
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
  line-height: 1.2;
}

.arrow-btn {
  padding: 20px 25px;
  background: #333;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0 15px 15px 0;
  transition: all 0.3s ease;
}

.arrow-btn img {
  width: 24px;
  height: 24px;
  filter: brightness(0) invert(1);
  transition: transform 0.3s ease;
}

.btn-black:hover .arrow-btn img {
  transform: translateX(3px);
}

.section-divider {
  border: none;
  height: 1px;
  background: #ddd;
  margin: 0 20px;
  flex: 1;
}

.house-img {
  width: 48%;
  height: auto;
  margin-right: 15px;
  border-radius: 15px;
  object-fit: cover;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .contact-section {
    padding: 60px 0;
  }
  
  .blac2 {
    font-size: 2.5rem;
  }
  
  .blac2-sub {
    font-size: 1.1rem;
  }
  
  .btn-stack {
    margin-left: 0;
  }
  
  .house-img {
    width: 45%;
  }
}

@media (max-width: 768px) {
  .contact-section {
    padding: 50px 0;
  }
  
  .blac2 {
    font-size: 2rem;
  }
  
  .blac2-sub {
    font-size: 1rem;
  }
  
  .btn-black {
    flex-direction: column;
  }
  
  .btn-red {
    border-radius: 15px 15px 0 0;
    width: 100%;
    text-align: center;
  }
  
  .arrow-btn {
    border-radius: 0 0 15px 15px;
    width: 100%;
  }
  
  .contact-btn-title {
    font-size: 1.1rem;
  }
  
  .house-img {
    width: 100%;
    margin-right: 0;
    margin-top: 20px;
  }
  
  .section-divider {
    margin: 0 10px;
  }
}

@media (max-width: 480px) {
  .contact-section {
    padding: 40px 0;
  }
  
  .blac2 {
    font-size: 1.8rem;
  }
  
  .blac2-sub {
    font-size: 0.95rem;
  }
  
  .btn-red {
    padding: 15px 20px;
  }
  
  .arrow-btn {
    padding: 15px 20px;
  }
  
  .contact-btn-title {
    font-size: 1rem;
  }
  
  .arrow-btn img {
    width: 20px;
    height: 20px;
  }
  
  .house-img {
    margin-top: 15px;
  }
}
</style>

<!-- Connect With Our Experts Section -->
<style>
/* Lets Connect (scoped) */
.blac2 { padding: 10px; background-color: #222; border-radius: 0px 0px 25px 0px; color: #fff; font-weight: 500; font-style: Medium; font-size: 3.125rem; line-height: 150%; letter-spacing: 0%; }
.blac2-sub { padding: 10px; margin-left: 2%; font-weight: 300; font-style: Light; font-size: 20px; line-height: 150%; letter-spacing: 9%; }
.btn-stack { position: relative; display: inline-block; height: 48px; }
.btn-black { position: absolute; top: 0px; left: 25px; width: 170px; height: 40px; border-radius: 25px; background: #000; z-index: 0; border: none; display: block; }
.btn-black .rarrow { z-index: 0; text-align: right; }
.btn-red { position: absolute; top: 0; left: 0; width: 170px; height: 40px; border-radius: 25px; background: #ef1515; color: #fff; font-weight: 600; font-size: 1rem; border: none; z-index: 1; cursor: pointer; transition: filter 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; justify-content: center; align-items: center; }
.btn-red:hover { filter: brightness(1.1); }
.section-divider { border: none; border-bottom: 2px solid #222; margin-right: 10px; width: 100%; }
.house-img { max-height: 100%; width: auto; margin-right: 0; margin-top: -245px; z-index: -1; }
.contact-section { margin-top: 35px; }
.contact-section .contact-btn-title { font-size: 1em; display: flex; justify-content: center; align-items: center; padding: 5em 0; margin: -20%; text-align: center; width: 100%; }
.btn-stack { margin-left: 1%; }
.btn-black { padding-left: 11.5em; display: flex; justify-content: end; }
.btn-red { padding-left: -10em; width: 140px; }
.btn-black .arrow-btn { display: flex; justify-content: center; }
.btn-black .arrow-btn img { width: 1.4em; margin-right: 90%; border: none; }

/* Responsive */
@media (max-width: 1024px) {
  .blog-section { padding: 2em; }
  .contact-section .house-img { width: 48%; margin-right: 15px; }
  .contact-section .section-divider { margin-bottom: 82px; }
  .contact-section .blac2-sub { font-size: 1.125rem; }
  .contact-section .blac2 { font-size: 2em !important; }
  .btn-stack { margin-left: 1%; }
  .btn-black { display: flex; justify-content: end; }
  .btn-red { width: 100px; padding: 0 4em; }
  .btn-red .contact-btn-title { font-size: 0.8em !important; padding: 4em 0; margin: -20%; }
  .btn-black .arrow-btn { display: flex; justify-content: center; align-items: center; }
  .btn-black .arrow-btn img { width: 1.3em; margin-right: 85%; }
  .section-divider { margin: 0 18px; }
  .contact-section h2 { font-size: 1.8rem; }
  .contact-section p { font-size: 0.95rem; }
}
@media (max-width: 768px) {
  .btn-stack { margin-left: 0; }
  .btn-black { display: flex; justify-content: start; width: 100%; }
  .btn-red { width: 130px; padding: 0 3em; }
  .btn-red .contact-btn-title { font-size: 0.75em !important; padding: 3em 0; margin: -18%; white-space: nowrap; }
  .btn-black .arrow-btn img { width: 1.1em; }
  .blac2{ width: 65%; }
  .house-img { width: 34% !important; margin-left: 28rem; }
  .section-divider{ width: 60% !important; margin: 5rem 0; margin-right: 15rem !important; }
  .contact-section h2 { font-size: 1.4rem; }
  .contact-section p { font-size: 0.85rem; margin-bottom: 20px; }
}
@media (max-width: 480px) {
  .btn-black, .btn-red { width: 50%; height: 50%; font-size: 1rem; justify-content: center; }
  .btn-stack { flex-direction: column; gap: 10px; }
  .blac2 { font-size: 1.75rem; padding: 15px; margin: 0; width: 20em; }
  .blac2-sub { font-size: 0.75rem; padding: 0.625rem 0.9375rem; }
  .btn-black { display: flex; justify-content: start; width: 100px; position: relative; margin: -8px; padding: 0 5em; }
  .btn-red { width: 120px; padding: 0 2em; margin: 0; }
  .btn-red .contact-btn-title { font-size: 0.7em !important; padding: 2.5em 0; margin: -15%; white-space: nowrap; }
  .btn-black .arrow-btn { position: absolute; right: 10%; top: 50%; transform: translateY(-50%); }
  .btn-black .arrow-btn img { width: 1em; margin-right: 0; }
  .house-img { width: 250px; max-width: 250px; align-self: flex-end; margin-top: -4em; z-index: 2; position: relative; }
  .section-divider { margin: -1em 10px 0 10px; z-index: 1; position: relative; top: 8em; }
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
