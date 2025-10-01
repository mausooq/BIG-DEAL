<style>
    /* hero banner removed */

/* Latest Blog Posts typography/colors to match site UI */
.blog-list-page .blog-list-container .blog-main h1{font-family:'DM Sans',sans-serif;font-weight:700;font-size:2em;line-height:1.1;color:#222;margin:0 0 10px}
.blog-list-page .blog-list-container .blog-main p{font-family:'DM Sans',sans-serif;color:#666}
.blog-list-page .blog-title{font-family:'DM Sans',sans-serif;font-weight:700;font-size:1.375em;color:#1a1a1a}
.blog-list-page .blog-excerpt{font-family:'DM Sans',sans-serif;font-weight:400;font-size:0.875em;line-height:1.7;color:#666}
.blog-list-page .blog-meta{font-family:'DM Sans',sans-serif;color:#888}
.blog-list-page .read-more{font-family:'DM Sans',sans-serif;color:#111;text-decoration:underline}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f8f9fa;
    color: #333;
    line-height: 1.6;
}

.blog-list-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 2fr 380px;
    gap: 40px;
}

/* Blog Posts */
.blog-main {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.blog-card {
    background: transparent !important;
    border-radius: 0 !important;
    overflow: visible !important;
    transition: transform 0.3s !important;
    display: flex !important;
    flex-direction: row !important;
    flex: none !important;
    gap: 0 !important;
    padding: 10px 0 10px 0 !important;
    border-bottom: 1px solid #e0e0e0 !important;
    align-items: flex-start !important;
}

@media (max-width: 1024px) {
    .blog-image-container { width: 100% !important; height: 320px !important; }
    .blog-title { font-size: 28px; }
}
@media (max-width: 768px) {
    .blog-image-container { height: 240px !important; }
    .blog-title { font-size: 24px; }
}

.blog-card:last-of-type {
    border-bottom: none;
}

.blog-card:hover {
    transform: translateX(5px);
}

/* Divider between blog cards */
.blog-divider { border: 0; border-top: 1px solid #e6e6e6; margin: 0.75em 0; }

.blog-image-container {
    position: relative !important;
    width: 420px !important;
    min-width: 420px !important;
    height: 280px !important;
    overflow: hidden !important;
    border-radius: 12px !important;
    flex-shrink: 0 !important;
    max-width: 100% !important;
}

.blog-image-container img {
    display: block;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}

.blog-category {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #cc1a1a; /* theme red */
    color: #fff;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.blog-content {
    padding: 8px 24px 8px 24px !important;
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
}

.blog-meta {
    display: flex;
    gap: 15px;
    font-size: 1em;
    color: #555;
    margin-bottom: 6px;
}

.blog-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.blog-title {
    font-size: 1.5em !important;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 6px;
    line-height: 1.3;
    word-break: break-word;
    overflow-wrap: anywhere;
}

.blog-title:hover {
    color: #cc1a1a;
    cursor: pointer;
}

.blog-excerpt {
    font-size: 1em !important;
    color: #666;
    line-height: 1.7;
    margin-bottom: 8px;
    text-align: justify;
}

.read-more {
    display: inline-block;
    color: #111;
    font-size: 1em;
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.3s;
    margin: 0;
}

.read-more:hover {
    color: #cc1a1a;
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
    position: sticky;
    top: 5em;
    align-self: start;
    height: max-content;
}

.sidebar-section {
    background: #ffffff;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    border: 1px solid #eee;
}
.sidebar-section.no-box{ background: transparent; box-shadow: none; border: none; padding: 0; }

.sidebar-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #111;
}

/* Search */
.search-container {
    position: relative;
}

/* Match homepage select/input shell */
.custom-select-wrapper { position: relative; display: block; }

/* Use existing homepage #search and #search-button styles from project */
#search-wrapper { display:flex; align-items:stretch; border-radius:50px; background-color:#fff; overflow:hidden; max-width:400px; margin: 0 !important; }
#search { border:none; width:350px; font-size:0.9375em; padding:10px 20px; color:#111; background-color:#ffffff; }
#search:focus { outline:none; }
#search-button { border:none; cursor:pointer; color:#fff; background-color:#111; padding:0 12px; }

.search-input {
    width: 100%;
    padding: 12px 44px 12px 16px;
    border: 2px solid #ececec;
    border-radius: 12px;
    font-size: 0.875em;
    outline: none;
    transition: all 0.2s ease;
    background: #fafafa;
}
.search-input:focus {
    border-color: #cc1a1a;
    box-shadow: 0 0 0 3px rgba(204,26,26,0.12);
    background: #fff;
}
.search-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #111;
    color: #fff;
    border: none;
    cursor: pointer;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease, transform 0.08s ease;
}
.search-btn:hover{ background:#cc1a1a; }
.search-btn:active{ transform: scale(0.98); }
/* Categories */
.categories-list {
    list-style: none;
}

/* Ensure category chips stack vertically under the title */
.categories-list.chips { display: block; padding: 0; margin: 0.5em 0 0 0; }
.categories-list.chips li { display: block; padding: 0; margin: 0; }
.categories-list.chips .cat-chip { display: flex; align-items: center; justify-content: space-between; width: 100%; text-decoration: none; padding: 0.6em 0.8em; border-radius: 8px; background: #fff; border: 1px solid #eee; color: #111; }
.categories-list.chips .cat-chip:hover { border-color: #cc1a1a; color: #cc1a1a; background: #fff6f6; }
.categories-list.chips .cat-chip .label { font-size: 0.95em; }
.categories-list.chips .cat-chip .count { font-size: 0.9em; color: #999; }

.categories-list li {
    display: flex;
    justify-content: space-between;
    padding: 10px 12px;
    border-bottom: 1px solid #f4f4f4;
    font-size: 0.875em;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 8px;
}

.categories-list li:hover { color:#cc1a1a; background:#fff6f6; }

.categories-list li:last-child {
    border-bottom: none;
}

.category-count {
    color: #999;
    font-size: 0.8125em;
}

/* Recent Posts */
.recent-post { display:flex; gap:12px; margin-bottom:16px; cursor:pointer; padding:0; border:none; border-radius:0; background:transparent; }
.recent-post img { width:80px; height:80px; border-radius:8px; overflow:hidden; flex-shrink:0; object-fit:cover; }
.recent-post:hover{ border-color:#cc1a1a; background:#fff; }

.recent-post-content {
    flex: 1;
}

.recent-post-author {
    font-size: 0.6875em;
    color: #999;
    margin-bottom: 5px;
}

.recent-post-title {
    font-size: 1em;
    font-weight: 600;
    color: #333;
    line-height: 1.4;
}

.recent-post:hover .recent-post-title {
    color: #cc1a1a;
}

/* Newsletter */
.newsletter-form {
    display: flex;
    gap: 10px;
}

.newsletter-input { flex:1; padding:12px 15px; border:2px solid #ececec; border-radius:12px; font-size:0.875em; outline:none; background:#fafafa; transition:all .2s ease; }
.newsletter-input:focus{ border-color:#cc1a1a; box-shadow:0 0 0 3px rgba(204,26,26,0.12); background:#fff; }
.newsletter-btn { background:#111; color:#fff; border:none; padding:12px 18px; border-radius:12px; cursor:pointer; font-size:16px; transition:background .2s ease, transform .08s ease; }
.newsletter-btn:hover { background:#cc1a1a; }
.newsletter-btn:active { transform: scale(0.98); }

/* Tags */
.tags-cloud { display:flex; flex-wrap:wrap; gap:10px; padding:0; background:transparent; border:none; }

.tag { padding:8px 12px; background:#fff3f3; border:1px solid #f3c8c8; border-radius:999px; font-size:0.75em; color:#e14c4c; cursor:pointer; transition:all .2s ease; text-decoration:none; }
.tag:hover { background:#cc1a1a; border-color:#cc1a1a; color:#fff; }

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 40px;
}

.pagination {
    display: flex;
    gap: 10px;
}

.pagination a,
.pagination span {
    width: 40px;
    height: 40px;
    border: 1px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875em;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #333;
}

.pagination a:hover {
    background: #f5f5f5;
}

.pagination .current {
    background: #333;
    color: white;
    border-color: #333;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 20px 0;
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    margin: 40px 0;
}

.no-results h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 10px;
}

.no-results p {
    color: #666;
}

@media (max-width: 1024px) {
    .blog-list-container { grid-template-columns: 1fr; gap: 2em; padding: 1.5em; }
    .sidebar-section { padding: 1.5em; }
    .blog-title, .blog-title.featured { font-size: 1.75em; }
    .blog-image-container, .blog-image-container.featured { width: 100% !important; min-width: 0 !important; height: 20em !important; }
    .blog-list-page .blog-list-container .blog-main h1 { font-size: 1.6em; }
    .blog-list-page .hero-content1 { font-size: 2.2em; margin-right: 2em; }
    .blog-list-page .hero-content2 { font-size: 3.2em; margin-left: 2em; }
}

@media (max-width: 768px) {
    .blog-list-container { gap: 1.5em; padding: 1.25em; }
    .blog-card { flex-direction: column !important; padding: 0.6em 0 0.6em 0 !important; }
    .blog-image-container { width: 100% !important; min-width: 0 !important; height: auto !important; aspect-ratio: 16 / 9; }
    .blog-title { font-size: 1.5em; }
    .blog-excerpt { font-size: 0.95em; }
    .blog-meta { font-size: 0.9em; }
    #search-wrapper { max-width: 100%; }
    .tags-cloud .tag { font-size: 0.85em; padding: 0.6em 1em; }
    .pagination a, .pagination span { width: 2.2em; height: 2.2em; font-size: 0.9em; }
    .blog-list-page .blog-list-container .blog-main h1 { font-size: 1.4em; }
    .blog-list-page .hero-banner::before { background-size: cover; background-position: center; z-index: -7; }
    .sidebar { position: static; top: auto; }
}

/* For small devices (phones, portrait) */
@media (max-width: 480px) {
    .blog-list-container { padding: 1em; gap: 1.25em; }
    .blog-card { padding: 0.5em 0 0.5em 0 !important; }
    .blog-image-container { width: 100% !important; min-width: 0 !important; height: auto !important; aspect-ratio: 16 / 9; }
    .blog-content { padding: 0.75em 0 0 0 !important; }
    .blog-title { font-size: 1.3em; }
    .blog-excerpt { font-size: 0.9em; line-height: 1.6; }
    .blog-meta { font-size: 0.85em; }
    .categories-list li { padding: 0.7em 0.9em; font-size: 0.9em; }
    .recent-post { gap: 0.7em; }
    .recent-post img { width: 3.5em; height: 3.5em; }
    .sidebar-section { padding: 1em; }
    .sidebar-title { font-size: 1em; margin-bottom: 0.8em; }
    .tags-cloud .tag { font-size: 0.8em; padding: 0.5em 0.8em; }
    .pagination a, .pagination span { width: 2em; height: 2em; font-size: 0.85em; }
    .blog-list-page .blog-list-container .blog-main h1 { font-size: 1.2em; }
    .blog-list-page .hero-content1 { font-size: 1.2em; margin-right: 0.5em; }
    .blog-list-page .hero-content2 { font-size: 1.8em; margin-left: 0.5em; }
    .blog-list-page .hero-banner::before { background-size: cover; background-position: center; z-index: -2; }
}


</style>
<?php
require_once __DIR__ . '/../config/config.php';

$mysqli = getMysqliConnection();

// Handle search and pagination
$search = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');
$selectedTag = trim($_GET['tag'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // Show 6 blogs per page
$offset = ($page - 1) * $limit;

// Build dynamic WHERE clause for search, category and tag
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = '(title LIKE ? OR content LIKE ?)';
    $types .= 'ss';
    $like = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($selectedCategory !== '') {
    $conditions[] = 'category = ?';
    $types .= 's';
    $params[] = $selectedCategory;
}

if ($selectedTag !== '') {
    // expects comma-separated tags; match with LIKE
    $conditions[] = 'tags LIKE ?';
    $types .= 's';
    $params[] = '%'.$mysqli->real_escape_string($selectedTag).'%';
}

$whereClause = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

// Get blogs with pagination
$sql = "SELECT id, title, content, image_url, created_at, category, tags FROM blogs" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$blogs = $stmt->get_result();

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM blogs" . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $conditions) {
    // Bind only the search/category params (not limit/offset)
    $countTypes = '';
    $countParams = [];
    if ($search !== '') {
        $countTypes .= 'ss';
        $like2 = '%' . $mysqli->real_escape_string($search) . '%';
        $countParams[] = $like2;
        $countParams[] = $like2;
    }
    if ($selectedCategory !== '') {
        $countTypes .= 's';
        $countParams[] = $selectedCategory;
    }
    if ($selectedTag !== '') {
        $countTypes .= 's';
        $countParams[] = '%'.$mysqli->real_escape_string($selectedTag).'%';
    }
    if ($countTypes !== '') {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
}
$countStmt->execute();
$totalCount = $countStmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalCount / $limit);

// Get categories for sidebar from blogs.category
$categories = [];
try {
    $catSql = "SELECT category AS name, COUNT(*) AS count FROM blogs WHERE category IS NOT NULL AND TRIM(category) <> '' GROUP BY category ORDER BY count DESC, name ASC";
    $catResult = $mysqli->query($catSql);
    if ($catResult) {
        while ($row = $catResult->fetch_assoc()) {
            if ((int)($row['count'] ?? 0) > 0) {
                $categories[] = $row;
            }
        }
        $catResult->free();
    }
} catch (Throwable $e) {
    // Silent fail; leave $categories empty
}

// Get recent posts for sidebar
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

// Build tags cloud from blogs.tags (comma-separated)
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
} catch (Throwable $e) { /* ignore */ }

// Image path resolution function
function resolveBlogImage($raw) {
    $raw = trim((string)($raw ?? ''));
    if ($raw === '') { return '../assets/images/prop/bhouse3.png'; }
    
    // Absolute URLs
    if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) { 
        return $raw; 
    }
    
    // From /test/blog/ to project root use '../../'
    $toRoot = '../../';
    
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

// Get excerpt function
function getExcerpt($content, $length = 150) {
    $content = strip_tags($content);
    if (mb_strlen($content) <= $length) {
        return $content;
    }
    return mb_substr($content, 0, $length) . '...';
}
?>

<div class="blog-list-page">


    <div class="container-fluid blog-list-container">
        <!-- Main Content -->
        <div class="blog-main">
            <?php if ($search): ?>
                <p><?php echo $totalCount; ?> result(s) found</p>
            <?php endif; ?>
            
            <?php if ($blogs->num_rows > 0): ?>
                <?php 
                $categoryNames = ['REAL ESTATE', 'PROPERTY', 'MARKET', 'HOUSING', 'RENTAL'];
                $categoryIndex = 0;
                $isFirst = true;
                while ($blog = $blogs->fetch_assoc()): 
                    if (!$isFirst) { echo '<hr class="blog-divider">'; }
                    $categoryName = isset($blog['category']) && trim($blog['category']) !== ''
                        ? strtoupper($blog['category'])
                        : $categoryNames[$categoryIndex % count($categoryNames)];
                    $categoryIndex++;
                ?>
                    <article class="blog-card" onclick="window.location.href='blog-details.php?id=<?php echo (int)$blog['id']; ?>'">
                        <div class="blog-image-container">
                            <img src="<?php echo htmlspecialchars(resolveBlogImage($blog['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="blog-category"><?php echo $categoryName; ?></span>
                        </div>
                        <div class="blog-content">
                            <div class="blog-meta">
                                <span>By Admin</span>
                                <span><?php echo date('M j, Y', strtotime($blog['created_at'])); ?></span>
                            </div>
                            <h2 class="blog-title"><?php echo htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="blog-excerpt"><?php echo htmlspecialchars(getExcerpt($blog['content'], 220), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </article>
                <?php $isFirst = false; endwhile; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?><?php echo $selectedTag ? '&tag=' . urlencode($selectedTag) : ''; ?>">&lt;</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?><?php echo $selectedTag ? '&tag=' . urlencode($selectedTag) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?><?php echo $selectedTag ? '&tag=' . urlencode($selectedTag) : ''; ?>">&gt;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>No blogs found</h3>
                    <p>Try adjusting your search terms or check back later for new content.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Search -->
            <div class="sidebar-section no-box">
                <form method="GET" action="">
                    <div id="search-wrapper">
                        <input id="search" name="search" type="text" placeholder="Search blogs" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
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
                            <a class="cat-chip" href="?category=<?php echo urlencode($category['name']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
            
            <!-- Newsletter removed as requested -->
            
            <!-- Tags -->
            <div class="sidebar-section no-box">
                <h3 class="sidebar-title">Tags</h3>
                <div class="tags-cloud">
                    <?php foreach ($tagsCloud as $t): ?>
                        <a href="?tag=<?php echo urlencode($t['name']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>" class="tag" title="<?php echo (int)$t['count']; ?> posts">
                            <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js" defer></script>
                </div>

