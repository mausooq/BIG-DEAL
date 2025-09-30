<?php
require_once __DIR__ . '/../config/config.php';

$mysqli = getMysqliConnection();

// Handle search and pagination
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // Show 6 blogs per page
$offset = ($page - 1) * $limit;

// Build search query
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = ' WHERE title LIKE ? OR content LIKE ?';
    $types = 'ss';
    $searchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Get blogs with pagination
$sql = "SELECT id, title, content, image_url, created_at FROM blogs" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
if ($countStmt && $search) {
    $countSearchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $countStmt->bind_param('ss', $countSearchParam, $countSearchParam);
}
$countStmt->execute();
$totalCount = $countStmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalCount / $limit);

// Get categories for sidebar
$categories = [];
try {
    $catResult = $mysqli->query("SELECT 'Market Trends' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%market%' OR title LIKE '%trend%' 
                                 UNION ALL
                                 SELECT 'Property Investment' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%investment%' OR title LIKE '%invest%'
                                 UNION ALL
                                 SELECT 'Home Buying Tips' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%buying%' OR title LIKE '%tip%'
                                 UNION ALL
                                 SELECT 'Rental & Leasing' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%rental%' OR title LIKE '%lease%'
                                 UNION ALL
                                 SELECT 'Commercial Real Estate' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%commercial%'
                                 UNION ALL
                                 SELECT 'Smart Living & Design' as name, COUNT(*) as count FROM blogs WHERE title LIKE '%design%' OR title LIKE '%living%'");
    if ($catResult) {
        while ($row = $catResult->fetch_assoc()) {
            if ($row['count'] > 0) {
                $categories[] = $row;
            }
        }
        $catResult->free();
    }
} catch (Throwable $e) {
    // Fallback categories if query fails
    $categories = [
        ['name' => 'Market Trends', 'count' => 22],
        ['name' => 'Property Investment', 'count' => 15],
        ['name' => 'Home Buying Tips', 'count' => 16],
        ['name' => 'Rental & Leasing', 'count' => 8],
        ['name' => 'Commercial Real Estate', 'count' => 19],
        ['name' => 'Smart Living & Design', 'count' => 21]
    ];
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
    <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .blog-list-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 320px;
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

        .blog-card:last-of-type {
            border-bottom: none;
        }

        .blog-card:hover {
            transform: translateX(5px);
        }

        .blog-image-container {
            position: relative !important;
            width: 200px !important;
            min-width: 200px !important;
            height: 160px !important;
            overflow: hidden !important;
            border-radius: 8px !important;
            flex-shrink: 0 !important;
        }

        .blog-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .blog-category {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #d4e157;
            color: #333;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .blog-content {
            padding: 8px 15px 8px 15px !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
        }

        .blog-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #888;
            margin-bottom: 6px;
        }

        .blog-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .blog-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .blog-title:hover {
            color: #d4e157;
            cursor: pointer;
        }

        .blog-excerpt {
            font-size: 14px;
            color: #666;
            line-height: 1.7;
            margin-bottom: 8px;
        }

        .read-more {
            display: inline-block;
            color: #333;
            font-size: 13px;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
            transition: color 0.3s;
            margin: 0;
        }

        .read-more:hover {
            color: #d4e157;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .sidebar-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        /* Search */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border 0.3s;
        }

        .search-input:focus {
            border-color: #d4e157;
        }

        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
        }

        /* Categories */
        .categories-list {
            list-style: none;
        }

        .categories-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .categories-list li:hover {
            color: #d4e157;
        }

        .categories-list li:last-child {
            border-bottom: none;
        }

        .category-count {
            color: #999;
            font-size: 13px;
        }

        /* Recent Posts */
        .recent-post {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .recent-post:last-child {
            margin-bottom: 0;
        }

        .recent-post img {
            width: 80px;
            height: 80px;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            object-fit: cover;
        }

        .recent-post-content {
            flex: 1;
        }

        .recent-post-author {
            font-size: 11px;
            color: #999;
            margin-bottom: 5px;
        }

        .recent-post-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            line-height: 1.4;
        }

        .recent-post:hover .recent-post-title {
            color: #d4e157;
        }

        /* Newsletter */
        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .newsletter-btn {
            background: #333;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }

        .newsletter-btn:hover {
            background: #d4e157;
            color: #333;
        }

        /* Tags */
        .tags-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            padding: 8px 16px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .tag:hover {
            background: #d4e157;
            border-color: #d4e157;
            color: #333;
        }

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
            font-size: 14px;
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
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
            .blog-list-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .blog-card {
                flex-direction: column;
            }
            
            .blog-image-container {
                width: 100%;
                height: 240px;
            }
        }
    </style>
</head>
<body class="blog-list-page">
    <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>

    <div class="blog-list-container">
        <!-- Main Content -->
        <div class="blog-main">
            <?php if ($search): ?>
                <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #333;">Search Results for "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"</h1>
                <p style="color: #666; margin-bottom: 30px;"><?php echo $totalCount; ?> result(s) found</p>
            <?php else: ?>
                <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #333;">Latest Blog Posts</h1>
            <?php endif; ?>
            
            <?php if ($blogs->num_rows > 0): ?>
                <?php 
                $categoryNames = ['REAL ESTATE', 'PROPERTY', 'MARKET', 'HOUSING', 'RENTAL'];
                $categoryIndex = 0;
                while ($blog = $blogs->fetch_assoc()): 
                    $categoryName = $categoryNames[$categoryIndex % count($categoryNames)];
                    $categoryIndex++;
                ?>
                    <article class="blog-card" onclick="window.location.href='blog-details.php?id=<?php echo (int)$blog['id']; ?>'" style="cursor: pointer;">
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
                            <p class="blog-excerpt"><?php echo htmlspecialchars(getExcerpt($blog['content']), ENT_QUOTES, 'UTF-8'); ?></p>
                            <a class="read-more">Read More</a>
                        </div>
                    </article>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&lt;</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&gt;</a>
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
            <div class="sidebar-section">
                <h3 class="sidebar-title">Search</h3>
                <form method="GET" action="">
                    <div class="search-container">
                        <input type="text" name="search" class="search-input" placeholder="Search..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>
                </form>
            </div>
            
            <!-- Categories -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">Categories</h3>
                <ul class="categories-list">
                    <?php foreach ($categories as $category): ?>
                        <li onclick="window.location.href='?search=<?php echo urlencode($category['name']); ?>'">
                            <span><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="category-count">(<?php echo (int)$category['count']; ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Recent Posts -->
            <div class="sidebar-section">
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
            
            <!-- Newsletter -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">Subscribe Newsletter</h3>
                <form class="newsletter-form" method="POST" action="">
                    <input type="email" name="email" class="newsletter-input" placeholder="Email address" required>
                    <button type="submit" class="newsletter-btn">→</button>
                </form>
            </div>
            
            <!-- Tags -->
            <div class="sidebar-section">
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

    <!-- Contact -->
    <?php include '../components/letsconnect.php'; ?>

    <!-- Footer -->
    <?php include '../components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js" defer></script>
</body>
</html>
