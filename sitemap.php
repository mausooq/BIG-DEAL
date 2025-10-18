<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/seo_config.php';

header('Content-Type: application/xml; charset=utf-8');

$mysqli = getMysqliConnection();
$baseUrl = 'https://bigdeal.property';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/about/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/services/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/blog/', 'priority' => '0.7', 'changefreq' => 'weekly'],
    ['url' => '/contact/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/products/', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/products/?listing=Buy', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/products/?listing=Rent', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/products/?featured=1', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['url' => '/creative-works/', 'priority' => '0.6', 'changefreq' => 'monthly']
];

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . $baseUrl . htmlspecialchars($page['url'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $page['priority'] . "</priority>\n";
    echo "  </url>\n";
}

// Dynamic property pages
try {
    $propertyQuery = "SELECT id, title, created_at, updated_at FROM properties WHERE status = 'Available' ORDER BY created_at DESC";
    if ($result = $mysqli->query($propertyQuery)) {
        while ($property = $result->fetch_assoc()) {
            $lastmod = !empty($property['updated_at']) ? date('Y-m-d', strtotime($property['updated_at'])) : date('Y-m-d', strtotime($property['created_at']));
            
            echo "  <url>\n";
            echo "    <loc>" . $baseUrl . "/products/product-details.php?id=" . (int)$property['id'] . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
        $result->free();
    }
} catch (Throwable $e) {
    error_log('Sitemap property query error: ' . $e->getMessage());
}

// Blog posts (if you have a blog system)
try {
    $blogQuery = "SELECT id, title, created_at, updated_at FROM blogs WHERE status = 'published' ORDER BY created_at DESC";
    if ($result = $mysqli->query($blogQuery)) {
        while ($blog = $result->fetch_assoc()) {
            $lastmod = !empty($blog['updated_at']) ? date('Y-m-d', strtotime($blog['updated_at'])) : date('Y-m-d', strtotime($blog['created_at']));
            
            echo "  <url>\n";
            echo "    <loc>" . $baseUrl . "/blog/blog-details.php?id=" . (int)$blog['id'] . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }
        $result->free();
    }
} catch (Throwable $e) {
    error_log('Sitemap blog query error: ' . $e->getMessage());
}

echo '</urlset>';
?>
