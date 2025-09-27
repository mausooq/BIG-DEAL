<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Get project ID
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    header('Location: index.php');
    exit;
}

$mysqli = db();

// Fetch project data
$stmt = $mysqli->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    header('Location: index.php');
    exit;
}

// Fetch project images
$stmt = $mysqli->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order ASC");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project_images = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Our Builds - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ef4444;
            --bg: #F4F7FA;
            --card: #FFFFFF;
            --line: #E0E0E0;
            --border-color: #E0E0E0;
            --text: #333;
        }
        body{ background:var(--bg); color:#111827; margin:0; }
        
        /* Header */
        .page-header {
            background: var(--card);
            border-bottom: 1px solid var(--line);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        /* Project Details */
        .project-detail{ margin-bottom:1rem; }
        .project-detail .label{ color:var(--text); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .project-detail .value{ font-weight:600; color:#111827; }
        .project-detail .value.badge{ font-weight:500; }
        .divider{ height:1px; background:var(--line); margin:1rem 0; }
        .two-col{ display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        
        /* Image Gallery */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: scale(1.02);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .gallery-item .order-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Main Image */
        .main-image {
            width: 100%;
            max-width: 600px;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 1rem;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        .btn-primary-custom:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .btn-secondary-custom {
            background: white;
            color: var(--text);
            border: 1px solid var(--line);
        }
        .btn-secondary-custom:hover {
            background: #f8f9fa;
            border-color: #d1d5db;
            color: var(--text);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .two-col {
                grid-template-columns: 1fr;
            }
            .image-gallery {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            .gallery-item img {
                height: 150px;
            }
            .main-image {
                height: 250px;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('our-builds'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Project Details'); ?>

        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="page-header">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h3 mb-0"><?php echo htmlspecialchars($project['name']); ?></h1>
                            <p class="text-muted mb-0">Project Details</p>
                        </div>
                        <a href="index.php" class="btn btn-secondary-custom">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back to Projects
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Main Image -->
                    <?php if (!empty($project_images)): ?>
                        <div class="mb-4">
                            <img src="../../uploads/projects/<?php echo htmlspecialchars($project_images[0]['image_filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($project['name']); ?>" 
                                 class="main-image" 
                                 id="mainImage">
                        </div>
                    <?php endif; ?>

                    <!-- Project Information -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fa-solid fa-info-circle me-2" style="color: var(--primary-color);"></i>
                                Project Information
                            </h5>
                            
                            <div class="project-detail">
                                <div class="label">Project Name</div>
                                <div class="value"><?php echo htmlspecialchars($project['name']); ?></div>
                            </div>
                            
                            <?php if ($project['description']): ?>
                                <div class="project-detail">
                                    <div class="label">Description</div>
                                    <div class="value"><?php echo nl2br(htmlspecialchars($project['description'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="two-col">
                                <div class="project-detail">
                                    <div class="label">Location</div>
                                    <div class="value">
                                        <i class="fa-solid fa-location-dot me-1" style="color: var(--primary-color);"></i>
                                        <?php echo htmlspecialchars($project['location'] ?: 'Not specified'); ?>
                                    </div>
                                </div>
                                <div class="project-detail">
                                    <div class="label">Display Order</div>
                                    <div class="value">
                                        <span class="badge" style="background-color: var(--primary-color);"><?php echo (int)$project['order_id']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="two-col">
                                <div class="project-detail">
                                    <div class="label">Created</div>
                                    <div class="value"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                                </div>
                                <div class="project-detail">
                                    <div class="label">Last Updated</div>
                                    <div class="value"><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn-custom btn-primary-custom">
                            <i class="fa-solid fa-edit"></i>Edit Project
                        </a>
                        <a href="index.php" class="btn-custom btn-secondary-custom">
                            <i class="fa-solid fa-list"></i>View All Projects
                        </a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Project Images -->
                    <?php if (!empty($project_images)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fa-solid fa-images me-2" style="color: var(--primary-color);"></i>
                                    Project Images (<?php echo count($project_images); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="image-gallery">
                                    <?php foreach ($project_images as $index => $img): ?>
                                        <div class="gallery-item" onclick="changeMainImage('<?php echo htmlspecialchars($img['image_filename']); ?>')">
                                            <img src="../../uploads/projects/<?php echo htmlspecialchars($img['image_filename']); ?>" 
                                                 alt="Project Image <?php echo $index + 1; ?>">
                                            <div class="order-badge"><?php echo $img['display_order']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-image fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No images uploaded for this project</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Project Stats -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-chart-bar me-2" style="color: var(--primary-color);"></i>
                                Project Stats
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="project-detail">
                                <div class="label">Project ID</div>
                                <div class="value">#<?php echo $project['id']; ?></div>
                            </div>
                            <div class="project-detail">
                                <div class="label">Total Images</div>
                                <div class="value"><?php echo count($project_images); ?></div>
                            </div>
                            <div class="project-detail">
                                <div class="label">Status</div>
                                <div class="value">
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(filename) {
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.src = `../../uploads/projects/${filename}`;
                
                // Add a smooth transition effect
                mainImage.style.opacity = '0.7';
                setTimeout(() => {
                    mainImage.style.opacity = '1';
                }, 150);
            }
        }

        // Add click effect to gallery items
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                document.querySelectorAll('.gallery-item').forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
