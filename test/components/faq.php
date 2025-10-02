<?php
// Ensure DB connection is available (follow footer component pattern)
// Try to reuse existing connection; otherwise bootstrap like other components
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
  // Use root config
  if (!function_exists('getMysqliConnection')) {
    $rootCfg = __DIR__ . '/../../config/config.php';
    if (file_exists($rootCfg)) { require_once $rootCfg; }
  }
  if (function_exists('getMysqliConnection')) {
    try { $mysqli = getMysqliConnection(); } catch (Throwable $e) { $mysqli = null; }
  }
}
// If a stale/broken connection was injected, attempt to reopen
if (isset($mysqli) && ($mysqli instanceof mysqli)) {
  try {
    if (!$mysqli->ping()) {
      if (function_exists('getMysqliConnection')) {
        $mysqli = getMysqliConnection();
      }
    }
  } catch (Throwable $e) {
    if (function_exists('getMysqliConnection')) {
      try { $mysqli = getMysqliConnection(); } catch (Throwable $e2) { $mysqli = null; }
    }
  }
}

// Load FAQs from database
$faqs = [];
try {
  $sql = "SELECT id, question, answer FROM faqs ORDER BY COALESCE(order_id, 1000000), id";
  if (isset($mysqli) && $mysqli instanceof mysqli && ($result = @$mysqli->query($sql))) {
    while ($row = $result->fetch_assoc()) { $faqs[] = $row; }
    $result->free();
  }
} catch (Throwable $e) {
  // Optionally log: error_log('FAQ load error: ' . $e->getMessage());
}
?>

<!-- Faq (component) -->
<style>
  /* Arrow rotation: default up, expanded down */
  .faq .farrow { transition: transform .2s ease; display:inline-block; }
  .faq .farrow.up { transform: rotate(180deg); }
  .faq .farrow.down { transform: rotate(0deg); }
</style>
<?php if (!isset($asset_path)) { $asset_path = 'assets/'; } $site_base_path = preg_replace('~assets/?$~', '', $asset_path); ?>
<section class="container-fluid faq">
  <div class="row">

    <div class="col-md-5">
      <button class="btn-faq">FAQs</button>
      <h3>
        Your <span style="color: red;">questions</span> <br> answered
      </h3>

      <p>
        Here are the most common questions<br> clients ask.
      </p>

      <a href="<?php echo $site_base_path; ?>contact/" class="btn-arrow">
        Get in Touch <span>→</span>
      </a>

    </div>

    <div class="col-md-7">

      <div class="FaqQ">
        <?php if (!empty($faqs)): ?>
          <?php foreach ($faqs as $index => $faq): ?>
            <div class="FaqQ-item<?php echo $index === 0 ? ' ' : ''; ?>">
              <?php if (!isset($asset_path)) { $asset_path = 'assets/'; } ?>
              <div class="FaqQ-title">
                <span><?php echo htmlspecialchars($faq['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                <img src="<?php echo $asset_path; ?>images/icon/arrowdown.svg" alt="arrow" class="farrow up">
              </div>
              <div class="FaqQ-content">
                <span><?php echo htmlspecialchars($faq['answer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="FaqQ-item">
            <?php if (!isset($asset_path)) { $asset_path = 'assets/'; } ?>
            <div class="FaqQ-title">
              <span>No FAQs available right now.</span>
              <img src="<?php echo $asset_path; ?>images/icon/arrowdown.svg" alt="arrow" class="farrow up">
            </div>
            <div class="FaqQ-content">
              <span>Please check back later.</span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
  (function(){
    try {
      var container = document.querySelector('.faq .FaqQ');
      if (!container) return;
      var items = container.querySelectorAll('.FaqQ-item');
      items.forEach(function(item, idx){
        var title = item.querySelector('.FaqQ-title');
        var content = item.querySelector('.FaqQ-content');
        var arrow = item.querySelector('.farrow');
        if (!title || !content) return;
        // Initialize: show first, hide others (match previous behavior)
        // Default state: arrow up (collapsed), content hidden
        content.style.display = 'none';
        if (arrow) { arrow.classList.add('up'); arrow.classList.remove('down'); }
        title.addEventListener('click', function(){
          var isOpen = content.style.display !== 'none';
          // Close all others to behave like accordion
          items.forEach(function(other){
            var c = other.querySelector('.FaqQ-content');
            var a = other.querySelector('.farrow');
            if (c) c.style.display = 'none';
            if (a) { a.classList.add('up'); a.classList.remove('down'); }
          });
          // Toggle current
          content.style.display = isOpen ? 'none' : 'block';
          if (arrow) {
            if (isOpen) { arrow.classList.add('up'); arrow.classList.remove('down'); }
            else { arrow.classList.add('down'); arrow.classList.remove('up'); }
          }
        });
      });
    } catch(e) {
      // no-op
    }
  })();
</script>

