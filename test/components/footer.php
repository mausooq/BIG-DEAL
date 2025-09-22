<style>
/* Footer Styles */
footer {
  background: #333;
  color: white;
  padding: 60px 0 20px;
}

.section-divider {
  border: none;
  height: 1px;
  background: #555;
  margin: 0 0 40px 0;
}

.footer {
  padding: 0 20px;
}

.footer img {
  max-width: 150px;
  height: auto;
  margin-bottom: 20px;
}

.footer p {
  color: #ccc;
  line-height: 1.6;
  margin-bottom: 30px;
  font-size: 0.95rem;
}

.social-links {
  display: flex;
  gap: 15px;
  padding-left: 0.4em;
}

.social-icon {
  width: 25px;
  height: 25px;
  transition: transform 0.3s ease;
}

.social-icon:hover {
  transform: scale(1.1);
}

.footer-column {
  padding: 0 20px;
}

.footer-column h3 {
  color: white;
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 25px;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 15px;
}

.footer-links a {
  color: #ccc;
  text-decoration: none;
  font-size: 0.95rem;
  transition: color 0.3s ease;
  position: relative;
}

.footer-links a::before {
  content: '';
  position: absolute;
  left: -15px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 1px;
  background: #e14c4c;
  transition: width 0.3s ease;
}

.footer-links a:hover {
  color: #e14c4c;
}

.footer-links a:hover::before {
  width: 10px;
}

#search-wrapper {
  position: relative;
  display: flex;
  margin-top: 20px;
}

#search {
  flex: 1;
  padding: 12px 15px;
  border: 1px solid #555;
  border-radius: 8px 0 0 8px;
  background: #444;
  color: white;
  font-size: 0.9rem;
}

#search::placeholder {
  color: #999;
}

#search:focus {
  outline: none;
  border-color: #e14c4c;
}

#search-button {
  background: #e14c4c;
  border: none;
  padding: 12px 15px;
  border-radius: 0 8px 8px 0;
  cursor: pointer;
  transition: background 0.3s ease;
}

#search-button:hover {
  background: #c73e3e;
}

#search-button img {
  width: 20px;
  height: 20px;
  filter: brightness(0) invert(1);
}

/* Copyright section */
footer .col-md-12 {
  border-top: 1px solid #555;
  padding-top: 20px;
  margin-top: 20px;
}

footer .col-md-12 p {
  margin: 0;
  color: #999;
  font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  footer {
    padding: 50px 0 20px;
  }
  
  .footer-column {
    padding: 0 15px;
    margin-bottom: 30px;
  }
}

@media (max-width: 768px) {
  footer {
    padding: 40px 0 20px;
  }
  
  .footer {
    padding: 0 15px;
    margin-bottom: 30px;
  }
  
  .footer-column {
    padding: 0 15px;
    margin-bottom: 25px;
  }
  
  .footer-column h3 {
    font-size: 1.1rem;
    margin-bottom: 20px;
  }
  
  .footer-links li {
    margin-bottom: 12px;
  }
  
  .footer-links a {
    font-size: 0.9rem;
  }
  
  .social-links {
    gap: 12px;
  }
  
  .social-icon {
    width: 22px;
    height: 22px;
  }
}

@media (max-width: 480px) {
  footer {
    padding: 30px 0 15px;
  }
  
  .footer {
    padding: 0 10px;
    margin-bottom: 25px;
  }
  
  .footer-column {
    padding: 0 10px;
    margin-bottom: 20px;
  }
  
  .footer-column h3 {
    font-size: 1rem;
    margin-bottom: 15px;
  }
  
  .footer-links li {
    margin-bottom: 10px;
  }
  
  .footer-links a {
    font-size: 0.85rem;
  }
  
  .footer p {
    font-size: 0.9rem;
  }
  
  .social-links {
    gap: 10px;
  }
  
  .social-icon {
    width: 20px;
    height: 20px;
  }
  
  #search {
    padding: 10px 12px;
    font-size: 0.85rem;
  }
  
  #search-button {
    padding: 10px 12px;
  }
  
  #search-button img {
    width: 18px;
    height: 18px;
  }
  
  footer .col-md-12 p {
    font-size: 0.8rem;
  }
}
</style>

<footer class="container-fluid">
  <style>
  /* Footer (scoped) */
  .section-divider { border: none; border-bottom: 2px solid #222; margin-right: 10px; width: 100%; }
  .footer p { font-weight: 500; font-size: 16px; line-height: 150%; vertical-align: middle; padding-left: 1.2em; }
  .footer .social-links { padding-left: 0.4em; }
  .footer-column { position: relative; padding-top: 30px; font-family: "inter", sans-serif; }
  .footer-column h3 { font-family: var(--font-heading); font-size: 20px; font-weight: 600; margin-bottom: 30px; position: relative; display: inline-block; }
  .footer-links { list-style: none; color: #6a6666; padding-left: 0; }
  .footer-links li { margin-bottom: 30px; }
  .footer-links a { color: #6a6666; text-decoration: none; font-size: 16px; font-weight: 500; display: inline-block; position: relative; }
  .footer-links a::before { content: "→"; position: absolute; left: -5px; opacity: 0; transition: var(--transition); color: var(--primary); }
  .footer-links a:hover { color: var(--lighter); padding-left: 1.5rem; }
  .footer-links a:hover::before { left: 0; opacity: 1; }
  .social-icon { filter: grayscale(100%); padding: 10px; }
  #search-wrapper { display: flex; align-items: stretch; border-radius: 50px; background-color: #fff; overflow: hidden; max-width: 400px; margin: 10px 10px; }
  #search { border: none; width: 350px; font-size: 15px; padding: 10px 20px; color: #999; background-color: #ffe3e3; }
  #search:focus { outline: none; }
  #search-button { border: none; cursor: pointer; color: #fff; background-color: #bf1d1d; padding: 0px 10px; }

  /* Responsive adjustments used in footer columns/links */
  @media (max-width: 768px) {
    .footer-column { padding: 1.25rem 0.625rem; }
    .footer-links li { margin-bottom: 0.9375rem; }
    .footer-links a { font-size: 0.875rem; }
  }
  @media (max-width: 480px) {
    .footer-column { padding-top: 15px; }
    .footer-column h3 { font-size: 1rem; margin-bottom: 0.9375rem; }
    .footer-links li { margin-bottom: 0.9375rem; }
    .footer-links a { font-size: 13px; }
  }
  </style>
  <hr class="section-divider">
  <div class="row ">

    <div class="col-md-4 footer">
    <img src="<?php echo $asset_path; ?>images/logo.png" alt="footer logo">
  <p>Big Deal Real Estate is your trusted partner in buying, selling, and investing in properties. 
    We focus on transparency, professionalism, and making your real estate journey simple and hassle-free.</p>

    <div class="social-links">
      <a href="https://facebook.com"><img src="https://img.icons8.com/material-rounded/25/facebook-new.png" class="social-icon"></a>
      <a href="https://instagram.com"><img src="https://img.icons8.com/material-rounded/25/instagram-new.png" class="social-icon"></a>
      <a href="https://linkedin.com"><img src="https://img.icons8.com/material-rounded/25/linkedin--v1.png" class="social-icon"></a>
      <a href="https://youtube.com"><img src="https://img.icons8.com/material-rounded/25/youtube-play.png" class="social-icon"></a>
    </div>
  </div>

   <div class=" col-md-2 footer-column">
                        <h3>Navigation</h3>
                        <ul class="footer-links">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Home</a></li>
                            
                        </ul>
      </div>

      <div class=" col-md-3 footer-column">
                        <h3>Contact</h3>
                        <ul class="footer-links">
                            <li>+918197458962</li>
                            <li>info@bigdeal.com</li>
                            <li>Kankanady Gate building, Mangalore</li>  
                        </ul>
      </div>

      <div class=" col-md-3 footer-column">
          <h3>Get the latest information</h3>
           <div id="search-wrapper">
            <input type="email" id="search" placeholder="Email address">
            <button id="search-button"><img src="<?php echo $asset_path; ?>images/icon/sendw.png" alt="send"></button>
          </div>
      </div>

    <hr class="section-divider">
      <div class="col-md-12 d-flex justify-content-between align-items-center">
      <p >&copy;<script>document.write(new Date().getFullYear());</script> Big Deal Real Estate </p>
 <p >   Developed by </p>
          </div>

</footer>
