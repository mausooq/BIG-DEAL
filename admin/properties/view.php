<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($property_id <= 0) {
    header('Location: index.php');
    exit();
}

// Handle feature toggle
$mysqli = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_feature'])) {
    $existsStmt = $mysqli->prepare("SELECT id FROM features WHERE property_id = ? LIMIT 1");
    $existsStmt->bind_param('i', $property_id);
    $existsStmt->execute();
    $existsRes = $existsStmt->get_result();
    $existing = $existsRes ? $existsRes->fetch_assoc() : null;
    $existsStmt->close();

    if ($existing) {
        $del = $mysqli->prepare("DELETE FROM features WHERE id = ?");
        $del && $del->bind_param('i', $existing['id']);
        $del && $del->execute();
        $del && $del->close();
    } else {
        $ins = $mysqli->prepare("INSERT INTO features (property_id) VALUES (?)");
        $ins && $ins->bind_param('i', $property_id);
        $ins && $ins->execute();
        $ins && $ins->close();
    }
    header('Location: view.php?id=' . $property_id);
    exit();
}

// Fetch property data
$stmt = $mysqli->prepare("SELECT p.*, c.name AS category_name FROM properties p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    header('Location: index.php');
    exit();
}

// Fetch property images
$images_stmt = $mysqli->prepare("SELECT id, image_url, image_order FROM property_images WHERE property_id = ? ORDER BY image_order ASC, id ASC");
$images_stmt->bind_param('i', $property_id);
$images_stmt->execute();
$property_images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$images_stmt->close();

// Format property data
$formatted_price = $property['price'] ? '₹' . number_format((float)$property['price']) : 'Not specified';
$formatted_area = $property['area'] ? number_format((float)$property['area']) . ' sqft' : 'Not specified';
$formatted_balcony = $property['balcony'] ? $property['balcony'] . ' balcony' . ($property['balcony'] > 1 ? 's' : '') : 'No balconies';
$formatted_created = date('M d, Y', strtotime($property['created_at']));

// Check if featured
$featStmt = $mysqli->prepare("SELECT id FROM features WHERE property_id = ? LIMIT 1");
$featStmt->bind_param('i', $property_id);
$featStmt->execute();
$featureRow = $featStmt->get_result()->fetch_assoc();
$featStmt->close();
$is_featured = $featureRow ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Property - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        
        body{ background:var(--bg); color:#111827; }

        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .btn{ border-radius:12px; font-weight:500; }
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        .section-header{ border-bottom:2px solid var(--line); padding-bottom:1rem; margin-bottom:2rem; }
        .section-title{ color:var(--brand-dark); font-weight:600; font-size:1.1rem; }
        .property-header{ background:linear-gradient(135deg, var(--primary) 0%, var(--primary-600) 100%); color:white; border-radius:var(--radius); padding:2rem; margin-bottom:2rem; }
        .property-title{ font-size:2rem; font-weight:700; margin-bottom:0.5rem; }
        .property-subtitle{ font-size:1.1rem; opacity:0.9; margin-bottom:1rem; }
        .property-price{ font-size:1.5rem; font-weight:600; margin-bottom:0.5rem; }
        .property-location{ font-size:1rem; opacity:0.9; }
        .badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .info-card{ border:1px solid var(--line); border-radius:12px; padding:1.5rem; margin-bottom:1rem; background:#fff; }
        .info-label{ color:var(--muted); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .info-value{ font-weight:600; color:#111827; }
        /* Hero image + thumbnails (match drawer style) */
        .hero{ background:#fff; border-radius:12px; border:1px solid var(--line); padding:12px; margin-bottom:16px; }
        .hero-main{ width:100%; height:360px; object-fit:contain; border-radius:8px; background:#f8f9fa; border:1px solid #e9ecef; }
        .thumbs{ display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
        .thumb{ width:90px; height:90px; object-fit:cover; border-radius:6px; cursor:pointer; border:2px solid transparent; transition:all 0.2s ease; flex-shrink:0; background:#f3f4f6; }
        .thumb:hover{ border-color:var(--primary); transform:scale(1.05); }
        .thumb.active{ border-color:var(--primary); box-shadow:0 0 0 2px rgba(225, 29, 42, 0.2); }
        .status-badge{ padding:0.5rem 1rem; border-radius:20px; font-weight:600; font-size:0.875rem; }
        .status-available{ background:#dcfce7; color:#166534; }
        .status-sold{ background:#fecaca; color:#991b1b; }
        .status-rented{ background:#fef3c7; color:#92400e; }
        .description-text{ line-height:1.6; color:#374151; }
        .action-buttons{ display:flex; gap:1rem; flex-wrap:wrap; }
        /* Print styles */
        @media print{
            @page { size: A4; margin: 12mm; }
            html, body{ background:#fff !important; color:#000 !important; }
            /* Hide admin chrome and interactive controls */
            nav, aside, .sidebar, .topbar, .toolbar, .btn, button, .action-buttons, .d-grid .btn, .drawer, .drawer-backdrop{ display:none !important; }
            /* Layout adjustments */
            .content{ margin:0 !important; padding:0 !important; }
            .container-fluid{ padding:0 !important; }
            .card, .info-card{ box-shadow:none !important; border:1px solid #ddd !important; }
            .property-header{ background:#fff !important; color:#000 !important; border:0; }
            .property-title{ font-size:20pt; }
            .property-subtitle, .property-location{ color:#000 !important; opacity:1; }
            /* Images and gallery */
            .hero{ border:0 !important; padding:0 !important; }
            .hero-main{ height:auto !important; max-height:160mm !important; object-fit:contain !important; border:0 !important; background:#fff !important; }
            .thumbs{ display:none !important; }
            /* Map and iframes typically blocked on print */
            .map-container, iframe{ display:none !important; }
            /* Badges and status with borders for visibility */
            .status-badge{ border:1px solid #999 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            /* Prevent awkward page breaks */
            .card, .info-card, .section-header, .section-title{ break-inside: avoid; page-break-inside: avoid; }
            .card{ margin-bottom:10mm; }
            /* Links visible */
            a{ color:#000 !important; text-decoration:underline !important; }
        }
        /* Mobile responsiveness */

            .property-header{ padding:1.5rem; }
            .property-title{ font-size:1.5rem; }
            .action-buttons{ flex-direction:column; }
        }
        @media (max-width: 575.98px){
            .property-header{ padding:1rem; }
            .hero-main{ height:240px; }
            .thumb{ width:70px; height:70px; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Property'); ?>

        <div class="container-fluid p-4">
            <!-- Header row with title and actions -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Close</a>
                </div>
                <form method="post" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="toggle_feature" value="1">
                    <button type="submit" class="btn <?php echo $is_featured ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo $is_featured ? 'Unmark Featured' : 'Mark as Featured'; ?>">
                        <i class="fa-<?php echo $is_featured ? 'solid' : 'regular'; ?> fa-star me-2"></i><?php echo $is_featured ? 'Featured' : 'Add to Featured'; ?>
                    </button>
                </form>
            </div>

            <!-- Top row: Hero (left) and Quick Info (right) -->
            <div class="row g-4 mb-3">
                <div class="col-lg-8">
                    <?php if (!empty($property_images)): ?>
                    <div class="hero">
                        <?php 
                            $firstImage = $property_images[0]['image_url'] ?? null; 
                            $totalImages = count($property_images);
                            $visibleThumbs = 5; // show first 5; rest are behind a +N more tile
                        ?>
                        <img src="../../uploads/properties/<?php echo htmlspecialchars(basename($firstImage)); ?>" alt="Property Image" id="heroMainImage" class="hero-main" loading="lazy">
                        <div class="thumbs" id="thumbsContainer">
                            <?php for ($i = 0; $i < min($totalImages, $visibleThumbs); $i++): $image = $property_images[$i]; ?>
                                <img 
                                    src="../../uploads/properties/<?php echo htmlspecialchars(basename($image['image_url'])); ?>" 
                                    alt="Thumb <?php echo $i+1; ?>" 
                                    class="thumb <?php echo $i === 0 ? 'active' : ''; ?>" 
                                    data-image-url="<?php echo htmlspecialchars(basename($image['image_url'])); ?>"
                                    loading="lazy"
                                >
                            <?php endfor; ?>
                            <?php if ($totalImages > $visibleThumbs): $remaining = $totalImages - $visibleThumbs; ?>
                                <div class="thumb" id="moreThumbTile" style="display:flex;align-items:center;justify-content:center;background:#f3f4f6;border:1px dashed #d1d5db;color:#374151;font-weight:600;">
                                    +<?php echo (int)$remaining; ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <!-- Quick Info (moved next to hero) -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-info-circle me-2"></i>Quick Info</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="info-label">Price</div>
                                    <div class="info-value"><?php echo $formatted_price; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Area</div>
                                    <div class="info-value"><?php echo $formatted_area; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Category</div>
                                    <div class="info-value"><?php echo htmlspecialchars($property['category_name'] ?: 'Not categorized'); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="status-badge status-<?php echo strtolower($property['status']); ?>"><?php echo htmlspecialchars($property['status']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Compact Map Embed or Placeholder -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-map-location-dot me-2"></i>Location Map</h6>
                            <?php if (!empty($property['map_embed_link'])): ?>
                                <div class="map-container" style="position: relative; width: 100%; height: 220px; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                    <iframe 
                                        src="<?php echo htmlspecialchars($property['map_embed_link']); ?>" 
                                        width="100%" 
                                        height="100%" 
                                        style="border:0; border-radius: 12px;" 
                                        allowfullscreen="" 
                                        loading="lazy" 
                                        referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center" style="height: 160px; border:1px solid var(--line); border-radius:12px; background:#f8fafc;">
                                    <div class="text-center text-muted">
                                        <i class="fa-solid fa-map-location-dot" style="font-size: 1.5rem;"></i>
                                        <div class="mt-2">No map provided</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Property Header -->
            <!-- <div class="property-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="flex-grow-1">
                        <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
                        <div class="property-subtitle">
                            <i class="fa-solid fa-location-dot me-2"></i>
                            <?php echo htmlspecialchars($property['location']); ?>
                            <?php if ($property['landmark']): ?>
                                <span class="opacity-75">(<?php echo htmlspecialchars($property['landmark']); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="property-price"><?php echo $formatted_price; ?></div>
                        <div class="property-location">
                            <span class="badge badge-soft me-2"><?php echo htmlspecialchars($property['listing_type']); ?></span>
                            <span class="status-badge status-<?php echo strtolower($property['status']); ?>"><?php echo htmlspecialchars($property['status']); ?></span>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="edit.php?id=<?php echo $property_id; ?>" class="btn btn-light">
                            <i class="fa-solid fa-pen me-2"></i>Edit Property
                        </a>
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back to Properties
                        </a>
                    </div>
                </div>
            </div> -->

            <div class="row g-4">
                <!-- Main Content -->
                <div class="col-lg-8">

                    <!-- Property Description -->
                    <?php if ($property['description']): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <h5 class="section-title"><i class="fa-solid fa-align-left me-2"></i>Description</h5>
                            </div>
                            <div class="description-text">
                                <?php echo nl2br(htmlspecialchars($property['description'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Property Details -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <h5 class="section-title"><i class="fa-solid fa-home me-2"></i>Property Details</h5>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Configuration</div>
                                        <div class="info-value"><?php echo htmlspecialchars($property['configuration'] ?: 'Not specified'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Furniture Status</div>
                                        <div class="info-value"><?php echo htmlspecialchars($property['furniture_status'] ?: 'Not specified'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Ownership Type</div>
                                        <div class="info-value"><?php echo htmlspecialchars($property['ownership_type'] ?: 'Not specified'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Facing</div>
                                        <div class="info-value"><?php echo htmlspecialchars($property['facing'] ?: 'Not specified'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Parking</div>
                                        <div class="info-value"><?php echo htmlspecialchars($property['parking'] ?: 'Not specified'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-label">Balconies</div>
                                        <div class="info-value"><?php echo $formatted_balcony; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Property Stats -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-chart-bar me-2"></i>Property Stats</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="info-label">Created</div>
                                    <div class="info-value"><?php echo $formatted_created; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Images</div>
                                    <div class="info-value"><?php echo count($property_images); ?> photo<?php echo count($property_images) !== 1 ? 's' : ''; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Listing Type</div>
                                    <div class="info-value"><?php echo htmlspecialchars($property['listing_type']); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Property ID</div>
                                    <div class="info-value">#<?php echo $property_id; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-tools me-2"></i>Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?php echo $property_id; ?>" class="btn btn-primary">
                                    <i class="fa-solid fa-pen me-2"></i>Edit Property
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-list me-2"></i>View All Properties
                                </a>
                                <button class="btn btn-outline-primary" onclick="window.print()">
                                    <i class="fa-solid fa-print me-2"></i>Print Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hero thumbnails swap main image + "+N more" behavior
        document.addEventListener('DOMContentLoaded', function() {
            const hero = document.getElementById('heroMainImage');
            const thumbsContainer = document.getElementById('thumbsContainer');
            if (!hero || !thumbsContainer) return;

            // Prepare arrays of all image filenames from PHP side
            const allImages = <?php echo json_encode(array_map(function($im){ return basename($im['image_url']); }, $property_images)); ?>;
            const visibleLimit = 5;
            let visibleCount = Math.min(allImages.length, visibleLimit);

            function wireThumb(el){
                if (!el) return;
                el.addEventListener('click', function(){
                    const url = this.getAttribute('data-image-url');
                    if (!url) return;
                    hero.src = `../../uploads/properties/${url}`;
                    thumbsContainer.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
                    this.classList.add('active');
                });
            }

            thumbsContainer.querySelectorAll('.thumb[data-image-url]').forEach(wireThumb);

            const moreTile = document.getElementById('moreThumbTile');
            function updateMoreTile(){
                const remaining = allImages.length - visibleCount;
                if (moreTile) {
                    if (remaining > 0) {
                        moreTile.style.display = '';
                        moreTile.textContent = `+${remaining} more`;
                    } else {
                        moreTile.style.display = 'none';
                    }
                }
            }

            if (moreTile) {
                moreTile.addEventListener('click', function(){
                    const remaining = allImages.length - visibleCount;
                    if (remaining <= 0) return;
                    // Insert ALL remaining hidden images in place of the more tile
                    for (let idx = visibleCount; idx < allImages.length; idx++) {
                        const file = allImages[idx];
                        const img = document.createElement('img');
                        img.src = `../../uploads/properties/${file}`;
                        img.alt = `Thumb ${idx+1}`;
                        img.className = 'thumb';
                        img.setAttribute('data-image-url', file);
                        img.loading = 'lazy';
                        thumbsContainer.insertBefore(img, moreTile);
                        wireThumb(img);
                    }
                    visibleCount = allImages.length;
                    // Hide/remove the more tile permanently (no "Less" feature)
                    moreTile.remove();
                });
                updateMoreTile();
            }
        });
    </script>
</body>
</html>