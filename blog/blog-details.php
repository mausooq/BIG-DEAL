<?php
require_once __DIR__ . '/../config/config.php';

$mysqli = getMysqliConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($blogId <= 0) { header('Location: index.php'); exit; }

$blog = null;
$subtitles = [];

// Load blog
$stmt = $mysqli->prepare("SELECT id, title, content, image_url, created_at, category FROM blogs WHERE id = ?");
$stmt->bind_param('i', $blogId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) { $blog = $res->fetch_assoc(); }
if ($res) { $res->free(); }
$stmt->close();
if (!$blog) { header('Location: index.php'); exit; }

// Load blog subtitles/sections
$stmt2 = $mysqli->prepare("SELECT subtitle, content, image_url FROM blog_subtitles WHERE blog_id = ? ORDER BY COALESCE(order_no, id)");
$stmt2->bind_param('i', $blogId);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($res2 && ($row = $res2->fetch_assoc())) { $subtitles[] = $row; }
if ($res2) { $res2->free(); }
$stmt2->close();

// Resolve cover image path relative to /blog/
function resolveBlogCover($raw) {
  $raw = trim((string)($raw ?? ''));
  if ($raw === '') { return ''; }
  // Absolute URLs
  if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) { return $raw; }
  // From /blog/ to project root use '../'
  $toRoot = '../';
  // If DB stored path starting with 'uploads/' (project-root relative)
  if (stripos($raw, 'uploads/') === 0) {
    return $toRoot . $raw; // '../uploads/...'
  }
  // If DB stored root-relative '/uploads/...'
  if (strpos($raw, '/uploads/') === 0) {
    return $toRoot . ltrim($raw, '/');
  }
  // If DB stored other root-relative path like '/images/...'
  if ($raw[0] === '/') {
    return $toRoot . ltrim($raw, '/');
  }
  // Treat as filename; point to project uploads/blogs
  $name = basename($raw);
  return $toRoot . 'uploads/blogs/' . $name;
}

// Resolve section image path (same rules, no fallback)
function resolveSectionImage($raw) {
  $raw = trim((string)($raw ?? ''));
  if ($raw === '') { return ''; }
  if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) { return $raw; }
  $toRoot = '../';
  if (stripos($raw, 'uploads/') === 0) { return $toRoot . $raw; }
  if (strpos($raw, '/uploads/') === 0) { return $toRoot . ltrim($raw, '/'); }
  if ($raw[0] === '/') { return $toRoot . ltrim($raw, '/'); }
  $name = basename($raw);
  return $toRoot . 'uploads/blogs/' . $name;
}

// Load recent blogs (exclude current), limit 3
$recentBlogs = [];
try {
  $stmt3 = $mysqli->prepare("SELECT id, title, image_url, created_at FROM blogs WHERE id <> ? ORDER BY created_at DESC, id DESC LIMIT 3");
  $stmt3->bind_param('i', $blogId);
  $stmt3->execute();
  $res3 = $stmt3->get_result();
  while ($res3 && ($row = $res3->fetch_assoc())) { $recentBlogs[] = $row; }
  if ($res3) { $res3->free(); }
  $stmt3->close();
} catch (Throwable $e) { /* ignore in UI */ }

// Build tags cloud from blogs.tags (comma-separated across all blogs)
$tagsCloud = [];
try {
  $tagRes = $mysqli->query("SELECT tags FROM blogs WHERE tags IS NOT NULL AND TRIM(tags) <> ''");
  if ($tagRes) {
    while ($row = $tagRes->fetch_assoc()) {
      $list = explode(',', (string)$row['tags']);
      foreach ($list as $raw) {
        $tag = trim($raw);
        if ($tag === '') continue;
        $key = mb_strtolower($tag);
        if (!isset($tagsCloud[$key])) { $tagsCloud[$key] = ['name' => $tag, 'count' => 0]; }
        $tagsCloud[$key]['count']++;
      }
    }
    $tagRes->free();
  }
  uasort($tagsCloud, function($a, $b){
    if ($a['count'] === $b['count']) { return strcasecmp($a['name'], $b['name']); }
    return $b['count'] <=> $a['count'];
  });
} catch (Throwable $e) { /* silent */ }

// Get categories for sidebar (dynamic from blogs.category)
$categories = [];
try {
  $catSql = "SELECT category AS name, COUNT(*) AS count FROM blogs WHERE category IS NOT NULL AND TRIM(category) <> '' GROUP BY category ORDER BY count DESC, name ASC";
  $catResult = $mysqli->query($catSql);
  if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
      if ((int)($row['count'] ?? 0) > 0) { $categories[] = $row; }
    }
    $catResult->free();
  }
} catch (Throwable $e) { /* silent */ }

// Get recent posts for sidebar (5 posts)
$recentPosts = [];
try {
    $recentResult = $mysqli->query("SELECT id, title, image_url, created_at FROM blogs ORDER BY created_at DESC LIMIT 5");
    if ($recentResult) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentPosts[] = $row;
        }
        $recentResult->free();
    }
} catch (Throwable $e) {
    // Silent fail
}

// Resolve recent blog image path (same rules)
function resolveRecentImage($raw) {
  return resolveSectionImage($raw);
}

// Image path resolution function for blog images
function resolveBlogImage($raw) {
    $raw = trim((string)($raw ?? ''));
    if ($raw === '') { return '../assets/images/prop/bhouse3.png'; }
    
    // Absolute URLs
    if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) { 
        return $raw; 
    }
    
    // From /blog/ to project root use '../'
    $toRoot = '../';
    
    // If DB stored path starting with 'uploads/' (project-root relative)
    if (stripos($raw, 'uploads/') === 0) {
        return $toRoot . $raw;
    }
    
    // If DB stored root-relative '/uploads/...'
    if (strpos($raw, '/uploads/') === 0) {
        return $toRoot . ltrim($raw, '/');
    }
    
    // If DB stored other root-relative path like '/images/...'
    if ($raw[0] === '/') {
        return $toRoot . ltrim($raw, '/');
    }
    
    // Treat as filename; point to project uploads/blogs
    $name = basename($raw);
    return $toRoot . 'uploads/blogs/' . $name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <!-- Favicon for Google Search Console - must use root /favicon.ico -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
  <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/blog-details.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
                
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body class="blogd-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

  <div class="container-fluid blog-details-container">
    <!-- Main Content -->
    <div class="blog-main">
      <section class="container">
        <a href="#" class="go-back" onclick="history.back();return false;">Go Back</a>
        <?php $catLabel = isset($blog['category']) && trim($blog['category']) !== '' ? htmlspecialchars($blog['category'], ENT_QUOTES, 'UTF-8') : 'Uncategorized'; ?>
        <a class="m-btn" href="index.php?category=<?php echo urlencode($blog['category'] ?? ''); ?>"><?php echo $catLabel; ?></a>
        <h1><?php echo htmlspecialchars($blog['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="meta">
        <span>7 min Read</span>
        <span><?php echo htmlspecialchars(date('F j, Y', strtotime($blog['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="image-container">
        <?php $cover = resolveBlogCover($blog['image_url'] ?? ''); if ($cover !== ''): ?>
        <img src="<?php echo htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>" alt="Blog cover" class="blog-cover" />
        <?php endif; ?>
        </div>
   </section>
    
      <div class="container blog-points">
        <div>
          <?php if (!empty($blog['content'])): ?>
            <p class="blog-content-text"><?php echo nl2br(htmlspecialchars($blog['content'], ENT_QUOTES, 'UTF-8')); ?></p>
          <?php endif; ?>
          <?php if (!empty($subtitles)): ?>
            <?php foreach ($subtitles as $sec): ?>
              <?php if (!empty($sec['subtitle'])): ?>
                <h2><?php echo htmlspecialchars($sec['subtitle'], ENT_QUOTES, 'UTF-8'); ?></h2>
              <?php endif; ?>
              <?php $secImg = resolveSectionImage($sec['image_url'] ?? ''); if ($secImg !== ''): ?>
                <div class="section-image-wrap">
                  <img src="<?php echo htmlspecialchars($secImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Section image" class="section-image" />
                </div>
              <?php endif; ?>
              <?php if (!empty($sec['content'])): ?>
                <p><?php echo nl2br(htmlspecialchars($sec['content'], ENT_QUOTES, 'UTF-8')); ?></p>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="testimonial-author">
                <img src="../assets/images/avatar/test1.png" alt="Profile">
                <div class="testimonial-author-info">
                  <h5>Admin</h5>
                  <p>Content Writer</p>
                </div>
              </div>
    </div>
    
    <!-- Sidebar -->
    <aside class="sidebar">
      <!-- Search -->
      <div class="sidebar-section no-box">
        <form method="GET" action="blog-list.php">
          <div id="search-wrapper">
            <input id="search" name="search" type="text" placeholder="Search blogs" value="">
            <button id="search-button" type="submit" aria-label="Search">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Categories -->
      <div class="sidebar-section no-box">
        <h3 class="sidebar-title">Categories</h3>
        <ul class="categories-list chips">
          <?php foreach ($categories as $category): ?>
            <li>
              <a class="cat-chip" href="blog-list.php?category=<?php echo urlencode($category['name']); ?>">
                <span class="label"><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="count"><?php echo (int)$category['count']; ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      
      <!-- Recent Posts -->
      <div class="sidebar-section no-box">
        <h3 class="sidebar-title">Recent Posts</h3>
        <?php foreach ($recentPosts as $recent): ?>
          <div class="recent-post" onclick="window.location.href='blog-details.php?id=<?php echo (int)$recent['id']; ?>'">
            <img src="<?php echo htmlspecialchars(resolveBlogImage($recent['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="Recent Post">
            <div class="recent-post-content">
              <div class="recent-post-author">Real Estate • <?php echo date('M j, Y', strtotime($recent['created_at'])); ?></div>
              <div class="recent-post-title"><?php echo htmlspecialchars($recent['title'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Tags -->
      <div class="sidebar-section no-box">
        <h3 class="sidebar-title">Tags</h3>
        <div class="tags-cloud">
          <?php foreach ($tagsCloud as $t): ?>
            <a href="blog-list.php?tag=<?php echo urlencode($t['name']); ?>" class="tag" title="<?php echo (int)$t['count']; ?> posts">
              <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
  </div>


    
    
</div>

 

<!-- contact  -->
<?php include '../components/letsconnect.php'; ?>


  <!-- footer -->
  <?php include '../components/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/scripts.js" defer></script>

</body>
</html>
