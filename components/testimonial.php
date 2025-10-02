<?php
// Load DB config and fetch testimonials
require_once __DIR__ . '/../config/config.php';

$testimonials = [];

// Derive site paths so returned URLs work from any depth using the provided $asset_path from parent page
if (!isset($asset_path)) { $asset_path = 'assets/'; }
$site_base_path = preg_replace('~assets/?$~', '', $asset_path);
$dotdotCount = substr_count($asset_path, '../');
$uploads_prefix = str_repeat('../', $dotdotCount + 1);

// Helper to resolve testimonial image path; prefer uploads/testimonials when a filename only is stored
function resolveTestimonialImageSrc($raw, $type = 'profile') {
    global $asset_path, $site_base_path, $uploads_prefix;
    $raw = trim((string)($raw ?? ''));
    
    // Default fallback images based on type
    $defaultImages = [
        'profile' => $asset_path . 'images/avatar/test1.png',
        'home' => $asset_path . 'images/prop/prop5.png'
    ];
    
    if ($raw === '') { return $defaultImages[$type] ?? $defaultImages['profile']; }
    
    // Check if it's already an absolute URL
    $isAbs = (stripos($raw, 'http://') === 0) || (stripos($raw, 'https://') === 0) || (strpos($raw, '/') === 0);
    if ($isAbs) { return $raw; }
    
    $name = basename($raw);
    $testRoot = dirname(__DIR__);        // .../BIG-DEAL/test
    $projectRoot = dirname($testRoot);   // .../BIG-DEAL

    // Check under project uploads first
    $projCandidates = [
        $projectRoot . '/uploads/testimonials/' . $name,
        $projectRoot . '/uploads/' . $name,
    ];
    foreach ($projCandidates as $abs) {
        if (file_exists($abs)) {
            // Return URL to project uploads from current page depth
            if (strpos($abs, $projectRoot . '/uploads/testimonials/') === 0) {
                return $uploads_prefix . 'uploads/testimonials/' . $name;
            }
            if (strpos($abs, $projectRoot . '/uploads/') === 0) {
                return $uploads_prefix . 'uploads/' . $name;
            }
        }
    }

    // Fallback to test assets
    $testCandidates = [
        $testRoot . '/assets/images/avatar/' . $name,
        $testRoot . '/assets/images/prop/' . $name,
    ];
    foreach ($testCandidates as $abs) {
        if (file_exists($abs)) {
            if (strpos($abs, '/avatar/') !== false) {
                return $asset_path . 'images/avatar/' . $name;
            }
            if (strpos($abs, '/prop/') !== false) {
                return $asset_path . 'images/prop/' . $name;
            }
        }
    }

    return $defaultImages[$type] ?? $defaultImages['profile'];
}
$houseImage = resolveTestimonialImageSrc('', 'home');
try {
    $db = getMysqliConnection();
    $sql = "SELECT id, name, feedback, rating, profile_image, home_image FROM testimonials ORDER BY created_at DESC";
    if ($result = $db->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $testimonials[] = $row;
        }
        $result->free();
    }
    if (!empty($testimonials) && !empty($testimonials[0]['home_image'])) {
        $houseImage = resolveTestimonialImageSrc($testimonials[0]['home_image'], 'home');
    }
} catch (Throwable $e) {
    // Fail silently in UI; optionally log
    error_log('Testimonials fetch failed: ' . $e->getMessage());
}
?>
<style>
/* Testimonials (scoped) */
.testimonials { padding: 20px 0; width: 100%; }
.test-head { font-size: 48px; font-weight: 700; font-style: Bold; margin-top: -80px; letter-spacing: 1%; }
.test-sub { font-weight: 400; font-style: Regular; font-size: 16px; letter-spacing: 1px; }
/* Utility: clip-path shape */
.clip-notch {
  -webkit-clip-path: polygon(40% 0%, 100% 0, 100% 100%, 0 100%, 0 40%);
  clip-path: polygon(40% 0%, 100% 0, 100% 100%, 0 100%, 0 40%);
}
/* Desktop-only quote positioning to avoid conflicts on mobile/tablet */
@media (min-width: 1025px) {
  .quote { justify-items: center; margin-top: -200px; margin-right: 15px; }
}
.testimg { position: relative; display: flex; justify-content: flex-end; align-items: center; gap: 10px; z-index: 1; margin-right: 70px; }
.testimg #testimonial-house-img {
  width: clamp(18rem, 42vw, 36rem);
  aspect-ratio: 9 / 5;
  object-fit: cover;
  display: block;
}
.testimonial-content { position: relative; margin-top: 80px; }
.testimonial-carousel {
  max-width: 730px; padding: 10px; padding-bottom: 0; margin-top: -60px; margin-left: 50px;
  border-radius: 15px 15px 40px 15px; border: 2px solid #000; background: #fff; position: relative; z-index: -1;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.testimonial-text p { font-size: 15px; font-weight: 500; font-style: Medium; line-height: 150%; letter-spacing: 0%; color: #333; margin: 0 0 25px 0; }
.testimonial-author { display: flex; align-items: center; margin-bottom: 20px; }
.testimonial-author img { width: 60px; height: 60px; border-radius: 50%; margin-right: 15px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.testimonial-author-info h5 { margin: 0; font-weight: 700; font-style: Bold; font-size: 40px; line-height: 150%; letter-spacing: 1%; color: #222; }
.testimonial-author-info p { margin: 4px 0 0; color: #666; font-size: 16px; font-family: "Roboto", sans-serif; font-weight: 200; font-style: ExtraLight; line-height: 100%; }
.stars { color: #ffd700; font-size: 22px; letter-spacing: 4px; margin: 0 0 25px 0; display: inline-block; }
.testimonial-nav { position: absolute; bottom: 70px; right: 70px; justify-content: flex-end; width: 100%; height: 60px; margin: 0px 10px; cursor: pointer; transition: all 0.3s ease; display: flex; gap: 0.5rem; }
.testimonial-nav :hover { opacity: 50%; border-color: #999; }
.testimonial-slide { display: none; opacity: 0; transition: opacity 0.5s ease-in-out; }
.testimonial-slide.active { display: block; opacity: 1; }
.testimonial-nav img:hover { opacity: 0.7; }

/* Responsive */
@media (max-width: 1024px) {
  .test-head { font-size: 1.75em; margin-top: -1.25em; }
  .test-sub { font-size: 0.8125em; }
  .testimonial-content { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 1.5rem; margin-top: 2rem; }
  .testimonials .testimg .quote { display: none !important; }
  .testimg { position: relative; display: flex; justify-content: center; align-items: center; margin-right: 0; flex: 0 0 auto; overflow: visible; }
  .testimg img.img-fluid { position: static; right: auto; margin: 0; z-index: 1; }
  .testimg #testimonial-house-img { width: clamp(16rem, 80vw, 30rem); aspect-ratio: 9 / 5; }
  .testimonial-carousel { width: 100% !important; max-width: 90%; margin: 0 auto; padding: 0.75em; z-index: 0; }
  .testimonial-author img { width: 3em; height: 3em; }
  .testimonial-author-info h5 { font-size: 1.125em; }
  .testimonial-author-info p { font-size: 0.75em; }
  .testimonial-author { margin-bottom: 0.5em !important; }
  .testimonial-slide p { margin: 0.375em 0 0.5em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; overflow: hidden; }
  .stars { font-size: 0.875em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: static; margin-top: 1rem; display: flex; justify-content: center; }
  .testimonial-slide { padding: 0; }
  .testimonial-nav img { width: 1.5em; height: 1.5em; }
}
@media (max-width: 768px) {
  .test-head { font-size: 1.75em; margin-top: -1.25em; }
  .test-sub { font-size: 0.8125em; }
  .testimonial-content { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 1.25rem; margin-top: 1.5rem; }
  .testimonials .testimg .quote { display: none !important; }
  .testimg { position: relative; display: flex; justify-content: center; align-items: center; margin-right: 0; flex: 0 0 auto; overflow: visible; }
  .testimg img.img-fluid { position: static; right: auto; margin: 0; z-index: 1; }
  .testimg #testimonial-house-img { width: clamp(14rem, 90vw, 26rem); aspect-ratio: 9 / 5; }
  .testimonial-carousel { width: 100% !important; max-width: 94%; margin: 0 auto; padding: 0.75em; z-index: 0; }
  .testimonial-author img { width: 3em; height: 3em; }
  .testimonial-author-info h5 { font-size: 1.125em; }
  .testimonial-author-info p { font-size: 0.75em; }
  .testimonial-author { margin-bottom: 0.5em !important; }
  .testimonial-slide p { margin: 0.375em 0 0.5em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; overflow: hidden; }
  .stars { font-size: 0.875em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: static; margin-top: 0.75rem; display: flex; justify-content: center; }
  .testimonial-nav img { width: 1.5em; height: 1.5em; }
}
@media (max-width: 480px) {
  .test-head { margin-top: 0; font-size: 1.5em;}
  .test-sub { font-size: 0.75em; line-height: 1.5; }
  .testimonial-content { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 0.75rem; margin-top: 1rem; margin-left: 0; }
  .testimonials .testimg .quote { display: none !important; }
  .testimg { position: relative; display: flex; justify-content: center; align-items: center; margin-right: 0; flex: 0 0 auto; overflow: visible; }
  .testimg img.img-fluid { position: static; margin: 0; left: auto; z-index: 1; }
  .testimg #testimonial-house-img { width: 100%; max-width: 22rem; aspect-ratio: 9 / 5; }
  .testimonial-carousel { width: 100%; max-width: 96%; margin: 0 auto; padding: 0.625em; z-index: 0; }
  .testimonial-author img { width: 2.25em; height: 2.25em; }
  .testimonial-author-info h5 { font-size: 0.9375em; }
  .testimonial-author-info p { font-size: 0.6875em; }
  .testimonial-author { margin-bottom: 0.375em !important; }
  .testimonial-slide p { margin: 0.25em 0 0.375em !important; line-height: 1.4 !important; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
  .stars { font-size: 0.8125em; margin: 0 0 0.375em !important; }
  .testimonial-nav { position: static; margin-top: 0.5rem; display: flex; justify-content: center; }
  .testimonial-nav img { width: 1.25em; height: 1.25em; }
}
</style>
<section class="testimonials">
      <div class="container">
        <h2 class="test-head">Testimonials</h2>
        <p class="test-sub">Stories from people who found their perfect space</p>
        
        <div class="testimonial-content">
          <div class="testimg">
            <img src="<?php echo $asset_path; ?>images/icon/quote.svg" alt="quote" class="quote">
            <img src="<?php echo htmlspecialchars($houseImage, ENT_QUOTES, 'UTF-8'); ?>" alt="House" class="img-fluid clip-notch" id="testimonial-house-img">
          </div>

          <div class="testimonial-carousel">
            <?php if (!empty($testimonials)): ?>
              <?php foreach ($testimonials as $index => $t): ?>
                <?php
                  $name = htmlspecialchars($t['name'] ?: 'Anonymous', ENT_QUOTES, 'UTF-8');
                  $feedback = htmlspecialchars($t['feedback'] ?: '', ENT_QUOTES, 'UTF-8');
                  $rating = (int)($t['rating'] ?: 5);
                  if ($rating < 1) { $rating = 1; }
                  if ($rating > 5) { $rating = 5; }
                  $profile = resolveTestimonialImageSrc($t['profile_image'], 'profile');
                ?>
                <div class="testimonial-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-home-image="<?php echo htmlspecialchars(resolveTestimonialImageSrc($t['home_image'], 'home'), ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="testimonial-author">
                    <img src="<?php echo htmlspecialchars($profile, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $name; ?>">
                    <div class="testimonial-author-info">
                      <h5><?php echo $name; ?></h5>
                      <p>&nbsp;</p>
                    </div>
                  </div>

                  <p><?php echo $feedback; ?></p>

                  <div class="stars">
                    <?php for ($i=0; $i<$rating; $i++): ?><span>★</span><?php endfor; ?>
                    <?php for ($i=$rating; $i<5; $i++): ?><span style="opacity:.25;">★</span><?php endfor; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="testimonial-slide active">
                <div class="testimonial-author">
                  <img src="<?php echo resolveTestimonialImageSrc('', 'profile'); ?>" alt="Guest">
                  <div class="testimonial-author-info">
                    <h5>Guest</h5>
                    <p>&nbsp;</p>
                  </div>
                </div>
                <p>We’ll soon showcase real stories from our happy customers.</p>
                <div class="stars"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
              </div>
            <?php endif; ?>
          </div>

          <div class="testimonial-nav">
            <img src="<?php echo $asset_path; ?>images/icon/prev.svg" alt="previous" id="testimonial-prev">
            <img src="<?php echo $asset_path; ?>images/icon/next.svg" alt="next" id="testimonial-next">
          </div>
        </div>
    </section>
<script>
(function(){
  const slides = Array.from(document.querySelectorAll('.testimonial-carousel .testimonial-slide'));
  const prevBtn = document.getElementById('testimonial-prev');
  const nextBtn = document.getElementById('testimonial-next');
  const houseImg = document.getElementById('testimonial-house-img');
  if (!slides.length || !prevBtn || !nextBtn) return;

  let index = slides.findIndex(s => s.classList.contains('active'));
  if (index < 0) index = 0;

  const fallbackHouse = houseImg ? houseImg.getAttribute('src') : '';

  function setActive(newIndex){
    slides[index].classList.remove('active');
    index = (newIndex + slides.length) % slides.length;
    slides[index].classList.add('active');
    // Update house image if provided for this slide
    if (houseImg){
      const newSrc = slides[index].getAttribute('data-home-image');
      houseImg.src = newSrc && newSrc.trim() !== '' ? newSrc : fallbackHouse;
    }
  }

  prevBtn.addEventListener('click', function(){ setActive(index - 1); });
  nextBtn.addEventListener('click', function(){ setActive(index + 1); });
})();
</script>