<?php
/**
 * Search Input Component
 * Compact search bar with category buttons and city selector
 */

// Get selected city and cities list
$selectedCity = isset($_GET['city']) ? trim((string)$_GET['city']) : '';
if (!isset($allCityNames) || empty($allCityNames)) {
  $allCityNames = [];
  
  if (!isset($mysqli)) {
    if (file_exists(__DIR__ . '/../config/config.php')) {
      require_once __DIR__ . '/../config/config.php';
      $mysqli = getMysqliConnection();
    }
  }
  
  try {
    if (isset($mysqli) && $mysqli) {
      if ($resAllCities = $mysqli->query("SELECT name FROM cities ORDER BY name")) {
        while ($row = $resAllCities->fetch_assoc()) {
          $allCityNames[] = $row['name'];
        }
        $resAllCities->free();
      }
    }
  } catch (Throwable $e) {
    error_log('All cities load error: ' . $e->getMessage());
  }
}
?>
<div class="compact-search-section" style="margin: 10px 0px 0px 0px !important;">
  <div class="container">
    <div class="compact-search-wrapper">
      <div class="compact-nav-buttons">
        <button type="button" onclick="onStaticListingClick('Buy')" class="compact-nav-btn">Buy</button>
        <button type="button" onclick="onStaticListingClick('Rent')" class="compact-nav-btn">Rent</button>
        <button type="button" onclick="onStaticCategoryClick('Plot')" class="compact-nav-btn">Plot</button>
        <button type="button" onclick="onStaticCategoryClick('Commercial')" class="compact-nav-btn">Commercial</button>
        <button type="button" onclick="onStaticListingClick('PG/Co-living')" class="compact-nav-btn">PG/Co-living</button>
        <button type="button" onclick="onStaticCategoryClick('1BHK/Studio')" class="compact-nav-btn">1BHK/Studio</button>
      </div>

        <div class="custom-select-wrapper" style="width: 100% !important;">
          <select class="custom-select" name="city" id="city-select-compact" aria-label="Select city" onchange="onHeroCityChange(this.value)">
            <option value="" <?php echo $selectedCity === '' ? 'selected' : ''; ?>>All Cities</option>
            <?php foreach ($allCityNames as $cityName): ?>
              <option value="<?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($selectedCity, $cityName) === 0 ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
    

  </div>
</div>

