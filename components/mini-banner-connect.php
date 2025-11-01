<?php if (!isset($asset_path)) { $asset_path = 'assets/'; }?>
<style>
/* Mini Banner Connect Section */
.mini-banner-connect {
  display: flex;
  align-items: center;
  background: linear-gradient(to right, #f8f9fa 66.66%, #e0f2f1 33.34%);
  border-radius: 20px;
  padding: 40px 50px;
  margin: 60px auto;
  max-width: 1200px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  position: relative;
  overflow: hidden;
}

.mini-banner-content {
  flex: 0 0 66.66%;
  padding-right: 40px;
  z-index: 2;
}

.mini-banner-headline {
  font-weight: 700;
  font-size: 2.5rem;
  line-height: 1.2;
  color: #1a1a1a;
  margin-bottom: 15px;
  font-family: "DM Sans", sans-serif;
}

.mini-banner-subheadline {
  font-weight: 400;
  font-size: 1.1rem;
  color: #555;
  margin-bottom: 30px;
  line-height: 1.5;
  font-family: "DM Sans", sans-serif;
}

.mini-banner-cta {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.mini-banner-primary-btn {
  background:rgb(28, 28, 29);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 14px 28px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  width: fit-content;
  font-family: "DM Sans", sans-serif;
  box-shadow: 0 2px 8px rgba(44, 90, 160, 0.2);
}

.mini-banner-primary-btn:hover {
  background:rgb(30, 30, 31);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
}

.mini-banner-whatsapp {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #333;
  text-decoration: none;
  font-size: 1rem;
  font-weight: 500;
  width: fit-content;
  transition: color 0.3s ease;
  font-family: "DM Sans", sans-serif;
}

.mini-banner-whatsapp:hover {
  color: #25D366;
}

.mini-banner-whatsapp-icon {
  width: 24px;
  height: 24px;
  display: inline-block;
}

.mini-banner-image {
  flex: 0 0 33.34%;
  text-align: center;
  z-index: 2;
  position: relative;
}

.mini-banner-image img {
margin-left: 2rem;
  max-width: 100%;
  height: auto;
  object-fit: contain;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .mini-banner-connect {
    padding: 35px 40px;
    margin: 50px auto;
  }
  
  .mini-banner-headline {
    font-size: 2rem;
  }
  
  .mini-banner-subheadline {
    font-size: 1rem;
  }
  
  .mini-banner-primary-btn {
    font-size: 1rem;
    padding: 12px 24px;
  }
}

@media (max-width: 768px) {
  .mini-banner-connect {
    flex-direction: column;
    padding: 30px 25px;
    margin: 40px auto;
    background: #f8f9fa;
  }
  
  .mini-banner-content {
    flex: 1;
    padding-right: 0;
    margin-bottom: 25px;
    text-align: center;
  }
  
  .mini-banner-cta {
    align-items: center;
  }
  
  .mini-banner-headline {
    font-size: 1.75rem;
    text-align: center;
  }
  
  .mini-banner-subheadline {
    font-size: 0.95rem;
    text-align: center;
  }
  
  .mini-banner-image {
    flex: 1;
    width: 100%;
  }
  
  .mini-banner-image img {
    max-width: 280px;
  }
}

@media (max-width: 480px) {
  .mini-banner-connect {
    padding: 25px 20px;
    margin: 30px auto;
    border-radius: 15px;
  }
  
  .mini-banner-headline {
    font-size: 1.5rem;
    margin-bottom: 12px;
  }
  
  .mini-banner-subheadline {
    font-size: 0.9rem;
    margin-bottom: 20px;
  }
  
  .mini-banner-primary-btn {
    font-size: 0.95rem;
    padding: 12px 20px;
    width: 100%;
    max-width: 280px;
  }
  
  .mini-banner-whatsapp {
    font-size: 0.9rem;
  }
  
  .mini-banner-image img {
    max-width: 220px;
  }
}
</style>

<?php 
// Cleaned up PHP block to simply define the variable if it doesn't exist
if (!isset($asset_path)) { 
    $asset_path = 'assets/'; 
}
?>

<style>
/* ... (YOUR CSS HERE) ... */
</style>

<section class="mini-banner-connect">
  <div class="mini-banner-content">
    <h2 class="mini-banner-headline">Sell or rent faster at the right price!</h2>
    <p class="mini-banner-subheadline">Your perfect buyer is waiting, list your property now</p>
    
    <div class="mini-banner-cta">
      <button 
        class="mini-banner-primary-btn" 
        onclick="window.location.href='/contact'"
        type="button"
        aria-label="Post Property on our platform, it's free"
      >
        Post Property, It's FREE
      </button>
      
      <a href="https://wa.me/919901805505?text=Hi!%20I%20want%20to%20list%20my%20property." class="mini-banner-whatsapp" target="_blank" rel="noopener noreferrer">
        <span>Post via</span>
        <svg class="mini-banner-whatsapp-icon" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true" focusable="false">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
        </svg>
        <span>WhatsApp →</span>
      </a>
    </div>
  </div>
  
  <div class="mini-banner-image">
    <img src="<?php echo $asset_path; ?>images/prop/bhouse.png" alt="Connect with our experts" style="max-height: 350px;">
  </div>
</section>