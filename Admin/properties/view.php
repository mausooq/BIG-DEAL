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
        .image-gallery{ display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1rem; }
        .gallery-item{ position:relative; border-radius:12px; overflow:hidden; aspect-ratio:4/3; }
        .gallery-item img{ width:100%; height:100%; object-fit:cover; transition:transform 0.3s ease; }
        .gallery-item:hover img{ transform:scale(1.05); }
        .gallery-overlay{ position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s ease; }
        .gallery-item:hover .gallery-overlay{ opacity:1; }
        .gallery-icon{ color:white; font-size:1.5rem; }
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
            .image-gallery{ grid-template-columns:1fr; }
            .property-header{ padding:1rem; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
            <!-- Property Header -->
            <div class="property-header">
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
            </div>

            <div class="row g-4">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Property Images -->
                    <?php if (!empty($property_images)): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <h5 class="section-title"><i class="fa-solid fa-images me-2"></i>Property Images</h5>
                            </div>
                            <div class="image-gallery">
                                <?php foreach ($property_images as $image): ?>
                                <div class="gallery-item">
                                    <img src="../../uploads/properties/<?php echo htmlspecialchars($image['image_url']); ?>" alt="Property Image" loading="lazy">
                                    <div class="gallery-overlay">
                                        <i class="fa-solid fa-expand gallery-icon"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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

                    <!-- Property Location Map -->
                    <?php if (!empty($property['map_embed_link'])): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <h5 class="section-title"><i class="fa-solid fa-map-location-dot me-2"></i>Location Map</h5>
                            </div>
                            <div class="map-container" style="position: relative; width: 100%; height: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
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
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    Interactive map showing the property location. You can zoom, pan, and explore the surrounding area.
                                </small>
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
                    <!-- Quick Info -->
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
        // Image gallery functionality
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            galleryItems.forEach(item => {
                item.addEventListener('click', function() {
                    const img = this.querySelector('img');
                    if (img) {
                        // Create modal for image viewing
                        const modal = document.createElement('div');
                        modal.className = 'modal fade';
                        modal.innerHTML = `
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Property Image</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="${img.src}" class="img-fluid" alt="Property Image">
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                        
                        modal.addEventListener('hidden.bs.modal', function() {
                            document.body.removeChild(modal);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
