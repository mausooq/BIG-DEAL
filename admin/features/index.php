<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Handle feature operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_feature') {
		$property_id = (int)($_POST['property_id'] ?? 0);
		if ($property_id) {
			$stmt = $mysqli->prepare('INSERT INTO features (property_id) VALUES (?)');
			if ($stmt) {
				$stmt->bind_param('i', $property_id);
				if ($stmt->execute()) {
					$_SESSION['success_message'] = 'Feature added to property successfully!';
				} else {
					$_SESSION['error_message'] = 'Failed to add feature: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Please select a property.';
		}
	} elseif ($action === 'delete_feature') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			$stmt = $mysqli->prepare('DELETE FROM features WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Feature removed from property successfully!';
					} else {
						$_SESSION['error_message'] = 'Feature not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete feature: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		}
	}

	header('Location: index.php');
	exit();
}

// Stats
$totalFeatures = fetchScalar('SELECT COUNT(*) FROM features');
$totalBlogs = fetchScalar('SELECT COUNT(*) FROM blogs');
$totalProperties = fetchScalar('SELECT COUNT(*) FROM properties');
$totalEnquiries = fetchScalar('SELECT COUNT(*) FROM enquiries');

// Get property_id from URL if coming from edit page
$property_id_from_url = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

// Get features with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE p.title LIKE ? OR p.location LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = 'SELECT f.id, f.property_id, p.title as property_title, p.location, p.price, f.id as feature_id FROM features f LEFT JOIN properties p ON f.property_id = p.id' . $whereClause . ' ORDER BY f.id DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$features = $stmt ? $stmt->get_result() : $mysqli->query('SELECT f.id, f.property_id, p.title as property_title, p.location, p.price, f.id as feature_id FROM features f LEFT JOIN properties p ON f.property_id = p.id ORDER BY f.id DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM features f LEFT JOIN properties p ON f.property_id = p.id' . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $search) {
	$countSearchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$countSearchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$countStmt->bind_param('ss', $countSearchParam1, $countSearchParam2);
}
$countStmt && $countStmt->execute();
$totalCountRow = $countStmt ? $countStmt->get_result()->fetch_row() : [0];
$totalCount = (int)($totalCountRow[0] ?? 0);
$totalPages = (int)ceil($totalCount / $limit);

// Recent section removed per request
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		/* Cards */
		
		.card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
		.table tbody tr{ border-top:1px solid var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
        /* Actions cell */
        .actions-cell{ display:flex; gap:8px; justify-content:flex-start; }
        .actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }

        /* Modern Animated Action Buttons (match Properties) */
        .modern-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0,0,0,.15), inset 0 1px 0 rgba(255,255,255,.2);
            display: inline-flex; align-items:center; justify-content:center; font-size:14px; margin:0 2px;
        }
        .modern-btn::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent, rgba(255,255,255,.3), transparent); transition:left .6s; }
        .modern-btn:hover::before { left:100%; }
        .modern-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 24px rgba(0,0,0,.2), inset 0 1px 0 rgba(255,255,255,.3); }
        .modern-btn:active { transform: translateY(-1px) scale(1.02); transition: all .1s ease; }
        .modern-btn .icon { transition: all .3s ease; }
        .modern-btn:hover .icon { transform: scale(1.2) rotate(-5deg); }
        .delete-btn { background: var(--primary); color:#fff; border:1px solid var(--primary-600); }
        .delete-btn:hover { background: var(--primary-600); border-color: var(--primary-600); color:#fff; }
        .ripple { position:absolute; border-radius:50%; background: rgba(255,255,255,.4); transform: scale(0); animation: ripple-animation .6s linear; pointer-events:none; }
        @keyframes ripple-animation { to { transform: scale(4); opacity: 0; } }
		/* Badges */
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Mobile responsiveness */

			.table{ font-size:.9rem; }
		}
		@media (max-width: 575.98px){
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.actions-cell{ justify-content:flex-start; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('features'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Featured properties'); ?>

		<div class="container-fluid p-4">
			<!-- Header -->
			<?php if ($property_id_from_url > 0): ?>
			<div class="d-flex align-items-center justify-content-between mb-4">
				<div>
					<h2 class="h4 mb-1">Manage Features</h2>
					<p class="text-muted mb-0">Add or remove featured properties</p>
				</div>
				<a href="../properties/edit.php?id=<?php echo $property_id_from_url; ?>" class="btn btn-outline-secondary">
					<i class="fa-solid fa-arrow-left me-2"></i>Back to Property Edit
				</a>
			</div>
			<?php endif; ?>

			<?php if (isset($_SESSION['success_message'])): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
			<?php if (isset($_SESSION['error_message'])): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>

			<!-- Quick Access removed -->

			<!-- Search toolbar -->
			<div class="toolbar mb-4">
				<div class="row-top">
					<form class="d-flex flex-grow-1" method="get">
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search features by property title or location">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<!-- <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFeatureModal">
						<i class="fa-solid fa-circle-plus me-1"></i>Add Feature
					</button> -->
				</div>
			</div>

			<div class="row g-4">
				<div class="col-12">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">Features</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="featuresTable">
									<thead>
										<tr>
											<th>Property</th>
											<th>Location</th>
											<th>Price</th>
											<th class="text-start">Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $features->fetch_assoc()): ?>
										<tr data-feature='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
											<td class="fw-semibold">
												<i class="fa-solid fa-building text-primary me-2"></i>
												<?php echo htmlspecialchars($row['property_title'] ?? 'N/A'); ?>
											</td>
											<td class="text-muted"><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
											<td class="text-success fw-semibold">
												<?php if($row['price']): ?>
													₹<?php echo number_format($row['price']); ?>
												<?php else: ?>
													<span class="text-muted">N/A</span>
												<?php endif; ?>
											</td>
											<td class="text-start actions-cell">
												<button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Remove Feature"><span class="icon"><i class="fa-solid fa-trash"></i></span></button>
											</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>

							<?php if ($totalPages > 1): ?>
							<nav aria-label="Features pagination">
								<ul class="pagination justify-content-center">
									<?php for ($i = 1; $i <= $totalPages; $i++): ?>
									<li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
										<a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
									</li>
									<?php endfor; ?>
								</ul>
							</nav>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Feature Modal -->
	<div class="modal fade" id="addFeatureModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add Property as Feature</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_feature">
						<div class="mb-3">
							<label class="form-label">Select Property</label>
							<select class="form-select" name="property_id" required>
								<option value="">Choose a property...</option>
								<?php
								// Get already featured property IDs
								$featuredProperties = $mysqli->query("SELECT property_id FROM features");
								$featuredIds = [];
								while($featured = $featuredProperties->fetch_assoc()) {
									$featuredIds[] = $featured['property_id'];
								}
								
								$properties = $mysqli->query("SELECT id, title, location, price FROM properties ORDER BY title ASC");
								while($prop = $properties->fetch_assoc()):
									$isFeatured = in_array($prop['id'], $featuredIds);
								?>
									<option value="<?php echo $prop['id']; ?>" <?php echo ($property_id_from_url == $prop['id']) ? 'selected' : ''; ?> <?php echo $isFeatured ? 'disabled' : ''; ?>>
										<?php if($isFeatured): ?>
											✅ <?php echo htmlspecialchars($prop['title']); ?> - <?php echo htmlspecialchars($prop['location']); ?> (₹<?php echo number_format($prop['price']); ?>) - Already Featured
										<?php else: ?>
											<?php echo htmlspecialchars($prop['title']); ?> - <?php echo htmlspecialchars($prop['location']); ?> (₹<?php echo number_format($prop['price']); ?>)
										<?php endif; ?>
									</option>
								<?php endwhile; ?>
							</select>
							<div class="form-text">Select a property to add as a featured property.</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Add as Feature</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Delete Confirmation Modal -->
	<div class="modal fade" id="deleteModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Delete</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to remove this property from features? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_feature">
						<input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn-animated-delete noselect">
                            <span class="text">Delete</span>
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                </svg>
                            </span>
                        </button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
        document.addEventListener('DOMContentLoaded', function(){
			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-feature'));
					document.getElementById('delete_id').value = data.id;
				});
			});

			// Auto-open modal if coming from edit page
			<?php if ($property_id_from_url > 0): ?>
			const addFeatureModal = new bootstrap.Modal(document.getElementById('addFeatureModal'));
			addFeatureModal.show();
			<?php endif; ?>

            // Auto-hide alerts after 5 seconds
			setTimeout(function(){
				document.querySelectorAll('.alert').forEach(a => {
					try { (new bootstrap.Alert(a)).close(); } catch(e) {}
				});
			}, 5000);
		});

        // Modern Button Ripple + click animation (match Properties)
        function createRipple(event) {
            const button = event.currentTarget;
            const circle = document.createElement('span');
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
            circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
            circle.classList.add('ripple');
            const ripple = button.querySelector('.ripple');
            if (ripple) ripple.remove();
            button.appendChild(circle);
        }

        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.modern-btn').forEach(btn => {
                btn.addEventListener('click', createRipple);
            });
        });

        document.addEventListener('click', function(event){
            const modernBtn = event.target.closest('.modern-btn');
            if (modernBtn) {
                modernBtn.style.animation = 'none';
                modernBtn.style.transform = 'scale(0.95)';
                setTimeout(() => { modernBtn.style.animation = ''; modernBtn.style.transform = ''; }, 150);
            }
        });
	</script>
</body>
</html>
