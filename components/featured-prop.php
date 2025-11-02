<?php
/**
 * Featured Properties Minimal Compact Carousel Component
 * Clean, minimal design with compact horizontal carousel
 */

// If properties not provided, fetch from database
if (!isset($featuredProperties) || empty($featuredProperties)) {
  $featuredProperties = [];
  
  if (!isset($mysqli)) {
    if (file_exists(__DIR__ . '/../config/config.php')) {
      require_once __DIR__ . '/../config/config.php';
      $mysqli = getMysqliConnection();
    }
  }
  
  try {
    if (isset($mysqli) && $mysqli) {
      $sqlFeatured = "
            SELECT 
        p.id,
        p.title,
        p.configuration,
        p.parking,
        p.area,
        p.price,
        COALESCE(c.name, p.landmark, '') AS location,
        (
          SELECT pi.image_url 
          FROM property_images pi 
          WHERE pi.property_id = p.id 
          ORDER BY pi.id ASC
          LIMIT 1
        ) AS cover_image_url
      FROM properties p
      LEFT JOIN properties_location pl ON pl.property_id = p.id
      LEFT JOIN cities c ON c.id = pl.city_id
      WHERE TRIM(LOWER(p.status)) = 'available'
      ORDER BY p.created_at DESC, p.id DESC
      LIMIT 10 
      ";
      
      if ($result = $mysqli->query($sqlFeatured)) {
        while ($row = $result->fetch_assoc()) {
          $featuredProperties[] = $row;
        }
        $result->free();
      }
    }
  } catch (Throwable $e) {
    error_log('Featured properties load error: ' . $e->getMessage());
    $featuredProperties = [];
  }
}

?>
<div class="compact-featured-section" style="margin:0px !important;">
  <div class="container">
    <!-- Compact Title -->
    <div class="compact-title-section">
      <h2 class="compact-title gugi" style="font-weight: 700;">Featured <span style="color: #cc1a1a;">Properties</span> </h2>
    </div>

    <!-- Compact Horizontal Carousel -->
    <?php if (!empty($featuredProperties)): ?>
    <div class="compact-carousel-section" id="compact-carousel-section">
      <div class="compact-carousel-track" id="compact-carousel-track">
        <?php 
        // Render cards twice for seamless infinite loop
        $renderCards = function($properties, $isDuplicate = false) use ($featuredProperties) {
          foreach ($properties as $index => $featured): 
            // Process image URL
            $img = trim((string)($featured['cover_image_url'] ?? ''));
            if ($img !== '') {
              if (!str_starts_with($img, 'http://') && !str_starts_with($img, 'https://') && !str_starts_with($img, '/')) {
                if (strpos($img, '/') === false) {
                  $img = 'uploads/properties/' . $img;
                } else {
                  $img = ltrim($img, './');
                  if (!str_starts_with($img, 'uploads/')) {
                    $img = 'uploads/properties/' . basename($img);
                  }
                }
              }
            }
            if (empty($img)) {
              $img = 'assets/images/prop/prop1.png';
            }
            
            $price = isset($featured['price']) && $featured['price'] ? number_format((float)$featured['price'], 0) : '';
            $location = $featured['location'] ?? '';
            $dataIndex = $isDuplicate ? ($index + count($featuredProperties)) : $index;
            ?>
            <div class="compact-property-card" data-index="<?php echo $dataIndex; ?>">
              <a href="products/product-details.php?id=<?php echo (int)$featured['id']; ?>" class="compact-card-link">
                <div class="compact-card-image">
                  <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" 
                       alt="<?php echo htmlspecialchars($featured['title'] ?? 'Property', ENT_QUOTES, 'UTF-8'); ?>" 
                       loading="<?php echo ($index === 0 && !$isDuplicate) ? 'eager' : 'lazy'; ?>">
                  <div class="compact-card-overlay">
                    <div class="compact-card-info">
                      <div class="compact-info-badges">
                        <span class="compact-badge">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                          </svg>
                          <?php echo htmlspecialchars($featured['configuration'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="compact-badge">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M9 9h6v6H9z"></path>
                          </svg>
                          <?php echo htmlspecialchars((string)($featured['area'] ?? '—')); ?> sqft
                        </span>
                        <?php if (($featured['parking'] ?? '') === 'Yes'): ?>
                        <span class="compact-badge">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2"></rect>
                            <path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path>
                          </svg>
                          1 Car
                        </span>
                        <?php endif; ?>
                      </div>
                      <?php if ($price): ?>
                      <div class="compact-card-price">₹<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></div>
                      <?php endif; ?>
                      <?php if ($location): ?>
                      <div class="compact-card-location"><?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach;
        };
        
        // Render original set
        $renderCards($featuredProperties, false);
        // Render duplicate set twice for seamless infinite loop
        $renderCards($featuredProperties, true);
        $renderCards($featuredProperties, true);
        ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
