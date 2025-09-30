<?php
// Ensure DB connection is available (follow footer component pattern)
// Try to reuse existing connection; otherwise bootstrap like other components
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
  // First try test config
  if (!function_exists('getMysqliConnection')) {
    $testCfg = __DIR__ . '/../config/config.php';
    if (file_exists($testCfg)) { require_once $testCfg; }
  }
  if (!function_exists('getMysqliConnection')) {
    // Fallback to root config (used by admin/front)
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

      <button class="btn-arrow">
        Get in Touch <span>→</span>
      </button>

    </div>

    <div class="col-md-7">

      <div class="FaqQ">
        <?php if (!empty($faqs)): ?>
          <?php foreach ($faqs as $index => $faq): ?>
            <div class="FaqQ-item<?php echo $index === 0 ? ' ' : ''; ?>">
              <div class="FaqQ-title">
                <span><?php echo htmlspecialchars($faq['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                <img src="assets/images/icon/arrowdown.svg" alt="arrow" class="farrow down">
              </div>
              <div class="FaqQ-content">
                <span><?php echo htmlspecialchars($faq['answer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="FaqQ-item">
            <div class="FaqQ-title">
              <span>No FAQs available right now.</span>
              <img src="assets/images/icon/arrowdown.svg" alt="arrow" class="farrow down">
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
        if (idx === 0) {
          content.style.display = content.style.display || 'block';
        } else {
          content.style.display = 'none';
        }
        title.addEventListener('click', function(){
          var isOpen = content.style.display !== 'none';
          // Close all others to behave like accordion
          items.forEach(function(other){
            var c = other.querySelector('.FaqQ-content');
            var a = other.querySelector('.farrow');
            if (c) c.style.display = 'none';
            if (a) a.classList.add('down');
          });
          // Toggle current
          content.style.display = isOpen ? 'none' : 'block';
          if (arrow) {
            if (isOpen) { arrow.classList.add('down'); }
            else { arrow.classList.remove('down'); }
          }
        });
      });
    } catch(e) {
      // no-op
    }
  })();
</script>

