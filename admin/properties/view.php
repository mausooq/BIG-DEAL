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

// Fetch property data
$mysqli = db();
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
$images_stmt = $mysqli->prepare("SELECT id, image_url FROM property_images WHERE property_id = ? ORDER BY id");
$images_stmt->bind_param('i', $property_id);
$images_stmt->execute();
$property_images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$images_stmt->close();

// Format property data
$formatted_price = $property['price'] ? '₹' . number_format((float)$property['price']) : 'Not specified';
$formatted_area = $property['area'] ? number_format((float)$property['area']) . ' sqft' : 'Not specified';
$formatted_balcony = $property['balcony'] ? $property['balcony'] . ' balcony' . ($property['balcony'] > 1 ? 's' : '') : 'No balconies';
$formatted_created = date('M d, Y', strtotime($property['created_at']));
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
        :root{ --bg:#F1EFEC; --card:#ffffff; --muted:#6b7280; --line:#e9eef5; --brand-dark:#2f2f2f; --primary:#e11d2a; --primary-600:#b91c1c; --radius:16px; }
        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* Sidebar styles copied from dashboard */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .brand{ font-weight:700; font-size:1.25rem; }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item i{ width:18px; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .btn{ border-radius:12px; font-weight:500; }
        .btn-primary{ background:#3b82f6; border-color:#3b82f6; }
        .btn-primary:hover{ background:#2563eb; border-color:#2563eb; }
        .section-header{ border-bottom:2px solid var(--line); padding-bottom:1rem; margin-bottom:2rem; }
        .section-title{ color:var(--brand-dark); font-weight:600; font-size:1.1rem; }
        .property-header{ background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; border-radius:var(--radius); padding:2rem; margin-bottom:2rem; }
        .property-title{ font-size:2rem; font-weight:700; margin-bottom:0.5rem; }
        .property-subtitle{ font-size:1.1rem; opacity:0.9; margin-bottom:1rem; }
        .property-price{ font-size:1.5rem; font-weight:600; margin-bottom:0.5rem; }
        .property-location{ font-size:1rem; opacity:0.9; }
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
        .info-card{ border:1px solid var(--line); border-radius:12px; padding:1.5rem; margin-bottom:1rem; background:#fff; }
        .info-label{ color:var(--muted); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .info-value{ font-weight:600; color:#111827; }
        /* Hero image + thumbnails (match drawer style) */
        .hero{ background:#fff; border-radius:12px; border:1px solid var(--line); padding:12px; margin-bottom:16px; }
        .hero-main{ width:100%; height:360px; object-fit:contain; border-radius:8px; background:#f8f9fa; border:1px solid #e9ecef; }
        .thumbs{ display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
        .thumb{ width:90px; height:90px; object-fit:cover; border-radius:6px; cursor:pointer; border:2px solid transparent; transition:all 0.2s ease; flex-shrink:0; background:#f3f4f6; }
        .thumb:hover{ border-color:#3b82f6; transform:scale(1.05); }
        .thumb.active{ border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59, 130, 246, 0.2); }
        .status-badge{ padding:0.5rem 1rem; border-radius:20px; font-weight:600; font-size:0.875rem; }
        .status-available{ background:#dcfce7; color:#166534; }
        .status-sold{ background:#fecaca; color:#991b1b; }
        .status-rented{ background:#fef3c7; color:#92400e; }
        .description-text{ line-height:1.6; color:#374151; }
        .action-buttons{ display:flex; gap:1rem; flex-wrap:wrap; }
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
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
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
            <!-- Top row: Hero (left) and Quick Info (right) -->
            <div class="row g-4 mb-3">
                <div class="col-lg-8">
                    <?php if (!empty($property_images)): ?>
                    <div class="hero">
                        <?php $firstImage = $property_images[0]['image_url'] ?? null; ?>
                        <img src="../../uploads/properties/<?php echo htmlspecialchars($firstImage); ?>" alt="Property Image" id="heroMainImage" class="hero-main" loading="lazy">
                        <div class="thumbs">
                            <?php foreach ($property_images as $index => $image): ?>
                                <img 
                                    src="../../uploads/properties/<?php echo htmlspecialchars($image['image_url']); ?>" 
                                    alt="Thumb <?php echo $index+1; ?>" 
                                    class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                                    data-image-url="<?php echo htmlspecialchars($image['image_url']); ?>"
                                    loading="lazy"
                                >
                            <?php endforeach; ?>
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
                                <button class="btn btn-outline-info" onclick="window.print()">
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
        // Hero thumbnails swap main image
        document.addEventListener('DOMContentLoaded', function() {
            const hero = document.getElementById('heroMainImage');
            if (!hero) return;
            document.querySelectorAll('.thumb').forEach(t => {
                t.addEventListener('click', function(){
                    const url = this.getAttribute('data-image-url');
                    if (!url) return;
                    hero.src = `../../uploads/properties/${url}`;
                    document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>