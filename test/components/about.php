<!-- about section -->
<style>
/* About Section (scoped) */
.about-section {
  display: flex;
  align-items: center;
  background: #f9f0f0;
  padding: 60px 40px;
  gap: 40px;
  margin: 60px auto;
  border-radius: 30px;
  position: relative;
  width: 80%;
}
.about-image {
  position: relative;
  z-index: 2;
  margin-left: -200px;
}
.about-image img {
  border-radius: 20px;
  width: 500px;
  margin: 10px;
  height: auto;
  object-fit: cover;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.11);
  z-index: 1;
}
.about-content {
  background: transparent;
  padding: 30px;
  border-radius: 20px;
  box-sizing: border-box;
  flex: 1;
  padding-right: 10px;
  position: relative;
  z-index: 2;
}
.about-content h1 {
  font-size: 2.2rem;
  color: #222;
  margin-bottom: 14px;
  font-weight: 200;
  font-style: ExtraLight;
  font-size: 48px;
  line-height: 100%;
  letter-spacing: 0%;
}
.about-content .highlight {
  color: #d63737;
  font-weight: 400;
  font-style: Regular;
}
.about-content p {
  color: #444;
  margin-bottom: 26px;
  font-size: 1rem;
  line-height: 1.7;
  font-weight: 400;
}
.btn-arrow {
  display: inline-flex;
  align-items: center;
  background: #fff;
  color: #222;
  border: 2px solid #222;
  border-radius: 30px;
  font-size: 1rem;
  padding: 12px 24px;
  cursor: pointer;
  margin-bottom: 25px;
  gap: 8px;
  transition: background 0.2s;
}
.btn-arrow:hover {
  background: #222;
  color: #fff;
}
.about-stats {
  display: flex;
  gap: 38px;
  margin-top: 30px;
}
.stat-number {
  font-weight: 100;
  font-style: Thin;
  font-size: 48px;
  line-height: 150%;
  letter-spacing: 0%;
}
.stat-label {
  font-weight: 600;
  font-style: SemiBold;
  font-size: 32px;
  line-height: 150%;
  letter-spacing: 0%;
}
.arrow {
  width: 40px;
  height: auto;
  margin-top: -30px;
}
/* Responsive (subset that affects about section) */
@media (max-width: 1024px) {
  .about-image {
    margin-left: 0;
    order: -1;
    text-align: center;
  }
  .about-image img {
    width: 100%;
    max-width: 500px;
    height: auto;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.11);
  }
  .about-content {
    padding: 1.25rem;
    text-align: center;
  }
  .about-content h1 {
    font-size: 2rem;
    line-height: 1.2;
    margin-bottom: 20px;
  }
  .about-content h1 .arrow {
    width: 40px;
    height: auto;
    margin-top: -20px;
  }
  .about-content p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
    text-align: left;
  }
  .btn-arrow {
    font-size: 0.9rem;
    padding: 10px 20px;
    margin-bottom: 30px;
  }
  .about-stats {
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
  }
  .stat-item { text-align: center; min-width: 80px; }
  .stat-number { font-size: 2.5rem; line-height: 1; }
  .stat-label { font-size: 1.5rem; line-height: 1.2; }
}
@media (max-width: 768px) {
  .about-section { flex-direction: column; width: 90%; }
  .about-image { margin-left: 0; order: -1; }
  .about-image img { width: 100%; height: auto; }
  .about-content { padding: 1.25rem; }
}
@media (max-width: 480px) {
  .about-section { flex-direction: column; padding: 1.25rem 0.625rem; width: 100%; }
  .about-image { margin-left: 0; width: 100%; }
  .about-content { padding: 10px; }
  .about-content h1 { font-size: 1.5rem; }
  .about-content p { font-size: 0.9rem; }
  .btn-arrow { margin: 15px; padding: 6px; font-size: 13px; }
}
</style>
<section class="container ">
      <div class="about-section">
  <div class="about-image">
    <img src="<?php echo $asset_path; ?>images/prop/bhouse2.png" alt="aboutimg" />
  </div>
  <div class="about-content">
    <h1>
      Hello City We are <br> leader in <span class="highlight">properties.</span>
       <img src="<?php echo $asset_path; ?>images/ARROW.png" alt="arrow" class="arrow">
    </h1>
     <p>
      Your dream home isn’t just a vision — it’s a reality waiting for you. As leaders in real estate, we specialize in crafting experiences where luxury meets comfort, and investment meets trust. Explore our handpicked collection of properties designed for modern lifestyles and lasting value.
    </p>
    <button class="btn-arrow">
      View More <span>→</span>
    </button>
    <div class="about-stats">
       <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label">Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
              <div class="stat-item">
                <span class="stat-number" >100+</span><br>
                <span class="stat-label" >Project</span>
              </div>
    </div>
  </div>
  </div>
</section>