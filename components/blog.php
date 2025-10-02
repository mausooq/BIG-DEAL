<style>
/* Blog Section (scoped) */
.blog-section {
  max-width: 1100px;
  margin: 60px auto;
  margin-bottom: 100px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 36px;
}
.section-header h2 {
  font-weight: 600;
  font-style: SemiBold;
  font-size: 48px;
  line-height: 100%;
  letter-spacing: 0%;
}
.section-header .view-all-btn {
  background: #111;
  color: #fff;
  border: none;
  font-family: DM Sans;
  font-weight: 600;
  font-style: SemiBold;
  font-size: Body/Size Medium;
  line-height: 100%;
  letter-spacing: 0%;
  text-align: center;
  height: 44px;
  border-radius: 15px;
  text-align: center;
  margin: 3.125rem;
  width: 120px;
}
.blog-feature {
  display: flex;
  background: #eeeeee;
  border-radius: 28px;
  overflow: hidden;
}
.blog-feature { min-height: 480px; }
/* Flexible lead image sizing across viewports */
.blog-image { width: clamp(320px, 40vw, 506px); height: auto; flex: 0 0 clamp(320px, 40vw, 506px); }
.blog-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 0; display: block; }
.blog-card {
  color: #373737;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #eeeeee;
  padding: 40px 50px;
  border-radius: 0 28px 28px 0;
}
.blog-read {
  font-weight: 300;
  font-style: Light;
  font-size: 20px;
  line-height: 100%;
  letter-spacing: 0%;
  text-align: center;
  display: inline-block;
  position: relative;
  background: #fff;
  color: #000000;
  border-radius: 14px;
  padding: 4px 16px;
  margin-bottom: 12px;
  width: 30%;
}
.blog-card h3 {
  font-weight: 600;
  font-style: SemiBold;
  font-size: 48px;
  line-height: 100%;
  letter-spacing: 0%;
  padding-bottom: 10px;
}
.blog-card p {
  font-weight: 200;
  font-style: ExtraLight;
  font-size: 24px;
  line-height: 156%;
  letter-spacing: 0%;
  margin-right: 20px;
}
.blog-author { display: flex; align-items: center; gap: 14px; margin-top: 30px; }
.author-img { width: 38px; height: 38px; border-radius: 50%; }
.author-name { font-weight: 400; font-style: Regular; font-size: 24px; line-height: 122%; letter-spacing: 0%; }
.author-role { font-weight: 200; font-style: ExtraLight; font-size: 20px; line-height: 122%; letter-spacing: 0%; }
.blog2, .blog3 { margin-top: 10px; padding-top: 10px; width: 100%; }
.blog-panel { border-radius: 24px; width: 50%; display: flex; flex-direction: column; margin: 0 20px; text-align: start; }
.blog-panel img { width: 327px; height: 329px; border-radius: 24px; object-fit: cover; display: block; margin: 0 auto; }
.caption { font-weight: 500; font-style: Medium; font-size: 24px; line-height: 100%; letter-spacing: 0%; margin: 10px 0; color: #000000; }
.date { font-weight: 300; font-style: Light; font-size: 16px; line-height: 100%; letter-spacing: 0%; color: #afabab; }

/* Responsive subset */
@media (max-width: 1024px) {
  .blog-section { padding: 2em; }
}
@media (min-width: 769px) and (max-width: 1024px) {
  .blog-image { width: 48vw; flex: 0 0 48vw; }
}
@media (max-width: 768px) {
  .blog-section { max-width: 100%; margin: 30px auto; padding: 0 20px; }
  .section-header { flex-direction: column; align-items: flex-start; gap: 20px; }
  .section-header h2 { font-size: clamp(1.1rem, 1.8vw + 0.9rem, 1.5rem); line-height: 1.3; }
  .view-all-btn { align-self: flex-end; padding: 10px 16px; font-size: 0.9rem; }
  .blog-feature { flex-direction: column; }
  .blog-image { width: 100%; flex: 0 0 auto; }
  .blog-image img { width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 28px 28px 0 0; }
  .blog-card { border-radius: 0 0 28px 28px; padding: 1.25rem; }
  .blog-card h3 { font-size: 1.1rem; margin-bottom: 12px; }
  .blog-card p { font-size: 0.9rem; line-height: 1.6; margin-bottom: 18px; }
  .blog-author { gap: 12px; flex-wrap: wrap; }
  .author-img { width: 32px; height: 32px; }
  .author-name { font-size: 0.9rem; }
  .author-role { font-size: 0.85rem; }
  .blog2, .blog3 { margin-top: 5px; padding-top: 0.3125rem; }
  .blog2 { display: flex; flex-wrap: wrap; gap: 12px; }
  .blog-panel { width: 100%; margin: 0px 5px; padding: 10px 5px; }
  .blog-panel img { width: 100%; height: auto; aspect-ratio: 16 / 10; object-fit: cover; }
  .caption { font-size: 1rem; margin-top: 12px; }
  .date { font-size: 0.85rem; margin-top: 2px; }
  .section-header {
        flex-direction: row;
        align-items: center;
        gap: 2px;
    }
  .section-header .view-all-btn {
        height: 3em !important;
        width: 8em !important;
        margin: 0px !important;
    }
}
@media (max-width: 480px) {
  .blog-section { padding: 0 10px; }
  .blog-card { padding: 15px; }
  .blog-card p { font-size: 0.9rem; }
  /* Keep title and button inline on small mobile */
  .section-header { flex-direction: row; align-items: center; justify-content: space-between; gap: 8px; }
  .section-header h2 { flex: 1 1 auto; margin: 0; }
  .section-header .view-all-btn { align-self: auto; flex: 0 0 auto; margin: 0; }
  .blog-read {width: 40% !important;font-size: 1rem !important;}
}
@media (min-width: 769px) and (max-width: 1024px) {
  .blog2 { display: flex; flex-wrap: wrap; gap: 16px; }
  .blog-panel { width: calc(50% - 16px); margin: 0; }
  .blog-panel img { width: 100%; height: auto; aspect-ratio: 16 / 10; object-fit: cover; }
}
@media (min-width: 1025px) {
  .blog2 { display: flex; flex-wrap: wrap; gap: 20px; }
  .blog-panel { width: calc(33.333% - 13.333px); margin: 0; }
  .blog-panel img { width: 100%; height: 100%; aspect-ratio: 16 / 11; object-fit: cover; }
}
</style>

<?php
  // Load latest blogs from DB (max 4) to fill the section without changing structure/styles
  if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    if (!function_exists('getMysqliConnection')) {
      $cfg = __DIR__ . '/../config/config.php';
      if (file_exists($cfg)) { require_once $cfg; }
    }
    if (function_exists('getMysqliConnection')) {
      try { $mysqli = getMysqliConnection(); } catch (Throwable $e) { $mysqli = null; }
    }
  }

  $blogs = [];
  if (isset($mysqli) && $mysqli instanceof mysqli) {
    try {
      if ($res = $mysqli->query("SELECT id, title, content, image_url, created_at FROM blogs ORDER BY created_at DESC, id DESC LIMIT 4")) {
        while ($row = $res->fetch_assoc()) { $blogs[] = $row; }
        $res->free();
        if (count($blogs) === 0) { error_log('[blog.php] No blogs returned from DB.'); }
      }
    } catch (Throwable $e) { error_log('[blog.php] Blog query error: ' . $e->getMessage()); }
  }

  // Build paths based on current page context
  if (!isset($asset_path)) { $asset_path = 'assets/'; }
  
  // Determine path depth based on asset_path
  if (strpos($asset_path, '../') === 0) {
      // We're in a subdirectory, use relative paths
      $site_base_path = '../';
      $uploads_prefix = '../';
  } else {
      // We're at root level, use direct paths
      $site_base_path = '';
      $uploads_prefix = '';
  }

  // Helper to resolve image path; prefer uploads/blogs when a filename only is stored
  function resolveBlogImageSrc($raw) {
    global $asset_path, $site_base_path, $uploads_prefix;
    $raw = trim((string)($raw ?? ''));
    if ($raw === '') { return ''; }
    
    // Absolute URLs
    if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) { 
        return $raw; 
    }
    
    // If DB stored path starting with 'uploads/' (project-root relative)
    if (stripos($raw, 'uploads/') === 0) {
        return $uploads_prefix . $raw;
    }
    
    // If DB stored root-relative '/uploads/...'
    if (strpos($raw, '/uploads/') === 0) {
        return $uploads_prefix . ltrim($raw, '/');
    }
    
    // If DB stored other root-relative path like '/images/...'
    if ($raw[0] === '/') {
        return $uploads_prefix . ltrim($raw, '/');
    }
    
    // Treat as filename; point to project uploads/blogs
    $name = basename($raw);
    return $uploads_prefix . 'uploads/blogs/' . $name;
  }

  $b0 = $blogs[0] ?? null;
  $b1 = $blogs[1] ?? null;
  $b2 = $blogs[2] ?? null;
$b3 = $blogs[3] ?? null;
?>

<?php if ($b0 || $b1 || $b2 || $b3): ?>
<section class="blog-section ">
  <div class="section-header">
    <h2>Explore our latest blogs for<br>real estate insights</h2>
    <button class="view-all-btn">View all <span>→</span></button>
  </div>
  <?php if ($b0): ?>
  <div class="blog-feature" <?php echo 'onclick="goToBlog(' . (int)$b0['id'] . ')" style="cursor:pointer" role="link" tabindex="0"'; ?> >
    <div class="blog-image">
      <?php $img0 = resolveBlogImageSrc($b0['image_url'] ?? ''); if ($img0 !== '') { ?>
      <img src="<?php echo htmlspecialchars($img0, ENT_QUOTES, 'UTF-8'); ?>" alt="Blogimage1">
      <?php } ?>
    </div>
    <div class="blog-card">
      <span class="blog-read">7 min read</span>
      <h3><?php echo htmlspecialchars($b0['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
      <p>
        <?php
          $excerpt = trim(strip_tags($b0['content'] ?? ''));
          $lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
          $subFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
          if ($lenFn($excerpt) > 320) { $excerpt = $subFn($excerpt, 0, 317) . '...'; }
          echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');
        ?>
      </p>
      <div class="blog-author">
        <img src="<?php echo htmlspecialchars($asset_path . 'images/avatar/test1.png', ENT_QUOTES, 'UTF-8'); ?>" class="author-img" alt="Admin">
        <div>
          <span class="author-name">Admin</span><br>
          <span class="author-role">Content Writer</span>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
<div class="d-flex blog2">
  <?php if ($b1): ?>
  <div class="blog-panel" <?php echo 'onclick="goToBlog(' . (int)$b1['id'] . ')" style="cursor:pointer" role="link" tabindex="0"'; ?> >
    <?php $img1 = resolveBlogImageSrc($b1['image_url'] ?? ''); if ($img1 !== '') { ?>
    <img src="<?php echo htmlspecialchars($img1, ENT_QUOTES, 'UTF-8'); ?>" alt="Living Room">
    <?php } ?>
    <div class="caption"><?php echo htmlspecialchars($b1['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="date"><?php if (isset($b1['created_at'])) { echo htmlspecialchars(date('F j, Y', strtotime((string)$b1['created_at'])), ENT_QUOTES, 'UTF-8'); } ?></div>
  </div>
  <?php endif; ?>
  <?php if ($b2): ?>
  <div class="blog-panel" <?php echo 'onclick="goToBlog(' . (int)$b2['id'] . ')" style="cursor:pointer" role="link" tabindex="0"'; ?> >
    <?php $img2 = resolveBlogImageSrc($b2['image_url'] ?? ''); if ($img2 !== '') { ?>
    <img src="<?php echo htmlspecialchars($img2, ENT_QUOTES, 'UTF-8'); ?>" alt="Modern TV Unit">
    <?php } ?>
    <div class="caption"><?php echo htmlspecialchars($b2['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="date"><?php if (isset($b2['created_at'])) { echo htmlspecialchars(date('F j, Y', strtotime((string)$b2['created_at'])), ENT_QUOTES, 'UTF-8'); } ?></div>
  </div>
  <?php endif; ?>
  <?php if ($b3): ?>
  <div class="blog-panel" <?php echo 'onclick="goToBlog(' . (int)$b3['id'] . ')" style="cursor:pointer" role="link" tabindex="0"'; ?> >
    <?php $img3 = resolveBlogImageSrc($b3['image_url'] ?? ''); if ($img3 !== '') { ?>
    <img src="<?php echo htmlspecialchars($img3, ENT_QUOTES, 'UTF-8'); ?>" alt="Contemporary Kitchen">
    <?php } ?>
    <div class="caption"><?php echo htmlspecialchars($b3['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="date"><?php if (isset($b3['created_at'])) { echo htmlspecialchars(date('F j, Y', strtotime((string)$b3['created_at'])), ENT_QUOTES, 'UTF-8'); } ?></div>
  </div>
  <?php endif; ?>
  </div>

</section>
<?php endif; ?>
<script>
function goToBlog(id){
  if(!id) return;
  window.location.href = '<?php echo $site_base_path; ?>blog/blog-details.php?id=' + id;
}
</script>