<?php
require_once __DIR__ . '/../config/config.php';

$mysqli = getMysqliConnection();

// Handle search and pagination
$search = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // Show 6 blogs per page
$offset = ($page - 1) * $limit;

// Build dynamic WHERE clause for search and category
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

$whereClause = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

// Get blogs with pagination
$sql = "SELECT id, title, content, image_url, created_at, category FROM blogs" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog List - Big Deal Ventures</title>
    <link rel="icon" href="../assets/images/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/blog-list.css">
    <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
</head>
<body class="blog-list-page">


    <div class="container-fluid blog-list-container">
        <!-- Main Content -->
        <div class="blog-main">
            <?php if ($search): ?>
                <h1>Search Results for "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"</h1>
                <p><?php echo $totalCount; ?> result(s) found</p>
            <?php else: ?>
                <h1>Latest Blog Posts</h1>
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
                    $isFeatured = $isFirst; // first blog as featured
                ?>
                    <article class="blog-card<?php echo $isFeatured ? ' featured-blog-card' : ''; ?>" onclick="window.location.href='blog-details.php?id=<?php echo (int)$blog['id']; ?>'">
                        <div class="blog-image-container<?php echo $isFeatured ? ' featured' : ''; ?>">
                            <img src="<?php echo htmlspecialchars(resolveBlogImage($blog['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="blog-category"><?php echo $categoryName; ?></span>
                        </div>
                        <div class="blog-content<?php echo $isFeatured ? ' featured' : ''; ?>">
                            <div class="blog-meta">
                                <span>By Admin</span>
                                <span><?php echo date('M j, Y', strtotime($blog['created_at'])); ?></span>
                            </div>
                            <h2 class="blog-title<?php echo $isFeatured ? ' featured' : ''; ?>"><?php echo htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="blog-excerpt<?php echo $isFeatured ? ' featured' : ''; ?>"><?php echo htmlspecialchars(getExcerpt($blog['content'], 220), ENT_QUOTES, 'UTF-8'); ?></p>
                            <a class="read-more">Read More</a>
                        </div>
                    </article>
                <?php $isFirst = false; endwhile; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>">&lt;</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>">&gt;</a>
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
                    <a href="?search=housing" class="tag">housing</a>
                    <a href="?search=mortgage" class="tag">mortgage</a>
                    <a href="?search=loans" class="tag">loans</a>
                    <a href="?search=crypto" class="tag">crypto</a>
                    <a href="?search=investment" class="tag">investment</a>
                    <a href="?search=commercial" class="tag">commercial</a>
                    <a href="?search=property" class="tag">property</a>
                    <a href="?search=condo" class="tag">condo</a>
                    <a href="?search=rental" class="tag">rental</a>
                </div>
            </div>
        </aside>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js" defer></script>
</body>
</html>
