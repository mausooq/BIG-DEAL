<?php
// Set asset path for proper asset loading
// Use absolute path from document root to ensure it works from any directory
$asset_path = '/assets/';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Page Not Found - Big Deal Ventures</title>
  <link rel="icon" href="<?php echo $asset_path; ?>images/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $asset_path; ?>css/style.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..1000;1,14..32,100..1000&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  
  <style>
    .page-not-found-container {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background-color:rgb(255, 255, 255);
      padding: 1.25em;
      text-align: center;
    }
    
    .error-gif {
      width: 30em;
      height: 30em;
      margin: 0 auto 1.875em;
      border-radius: 50%;
      overflow: hidden;
      /* box-shadow: 0 10px 30px rgba(0,0,0,0.1); */
    }
    
    .error-gif img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    
    .page-not-found-title {
      font-family: 'DM Sans', sans-serif;
      font-size: 2.5rem;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 0.2em;
      line-height: 1.2;
    }
    
    .page-not-found-subtitle {
      font-family: 'DM Sans', sans-serif;
      font-size: 1.1rem;
      color: #7f8c8d;
      margin-bottom: 2.5em;
      line-height: 1.6;
    }
    
    .retry-button {
      background: linear-gradient(135deg, #e14c4c, #ff6b6b);
      color: white;
      border: none;
      padding: 0.9375em 2.5em;
      border-radius: 3.125em;
      font-family: 'DM Sans', sans-serif;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      box-shadow: 0 0.3125em 0.9375em rgba(225, 76, 76, 0.3);
    }
    
    .retry-button:hover {
      transform: translateY(-0.125em);
      box-shadow: 0 0.5em 1.5625em rgba(225, 76, 76, 0.4);
      color: white;
      text-decoration: none;
    }
    
    .retry-button:active {
      transform: translateY(0);
    }
    
    .home-button {
      background: transparent;
      color: #e14c4c;
      border: 0.125em solid #e14c4c;
      padding: 0.75em 1.875em;
      border-radius: 3.125em;
      font-family: 'DM Sans', sans-serif;
      font-weight: 500;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      margin-left: 0.9375em;
    }
    
    .home-button:hover {
      background: #e14c4c;
      color: white;
      text-decoration: none;
    }
    

    
    @media (max-width: 768px) {
      .page-not-found-title {
        font-size: 2rem;
      }
      
      .error-gif {
        width: 20em;
        height: 20em;
        border: 0.1em solid #e14c4c;
      }
      
      .retry-button, .home-button {
        display: block;
        margin: 0.625em auto !important;
        width: 12.5em;
        margin-left: auto !important;
        margin-right: auto !important;
      }
    }
    
    .pulse-animation {
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
  </style>
</head>

<body>
  <div class="page-not-found-container">
    <div class="error-gif pulse-animation">
      <img src="<?php echo $asset_path; ?>error.gif" alt="Page Not Found">
    </div>
    
    <h1 class="page-not-found-title">
      <span style="color: #e14c4c;">404</span> Page Not Found
    </h1>
    
    <p class="page-not-found-subtitle">
      The page you're looking for doesn't exist or has been moved.
    </p>
    
    
    <div style="margin-top: 0.625em; text-align: center;">
      <a href="#" class="retry-button" onclick="goBack()">
        <i class="fas fa-arrow-left"></i> Go Back
      </a>
      <a href="/index.php" class="home-button">
        <i class="fas fa-home"></i> Go Home
      </a>
    </div>
  </div>

  <script>
    function goBack() {
      // Show loading state
      const retryBtn = document.querySelector('.retry-button');
      const originalText = retryBtn.innerHTML;
      retryBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Going back...';
      retryBtn.style.pointerEvents = 'none';
      
      // Go back to previous page
      setTimeout(() => {
        if (window.history.length > 1) {
          window.history.back();
        } else {
          window.location.href = '/index.php';
        }
      }, 1000);
    }
    
    // Add some interactive feedback
    document.addEventListener('DOMContentLoaded', function() {
      const container = document.querySelector('.page-not-found-container');
      
      // Add subtle hover effect
      container.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'transform 0.3s ease';
      });
      
      container.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });
  </script>
</body>

</html>
