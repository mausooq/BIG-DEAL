<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
	header('Location: ../login/');
	exit();
}

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Stats
$totalProperties = fetchScalar("SELECT COUNT(*) FROM properties");
$totalCategories = fetchScalar("SELECT COUNT(*) FROM categories");
$totalBlogs = fetchScalar("SELECT COUNT(*) FROM blogs");
$totalEnquiries = fetchScalar("SELECT COUNT(*) FROM enquiries");

// Recent lists
$mysqli = db();
$filters = [
	'price_min' => $_GET['price_min'] ?? null,
	'price_max' => $_GET['price_max'] ?? null,
	'location' => $_GET['location'] ?? null,
	'landmark' => $_GET['landmark'] ?? null,
	'title' => $_GET['title'] ?? null,
	'description' => $_GET['description'] ?? null,
	'furniture_status' => $_GET['furniture_status'] ?? null,
	'ownership_type' => $_GET['ownership_type'] ?? null,
	'facing' => $_GET['facing'] ?? null,
	'parking' => $_GET['parking'] ?? null,
	'area_min' => $_GET['area_min'] ?? null,
	'area_max' => $_GET['area_max'] ?? null,
	'configuration' => $_GET['configuration'] ?? null,
	'balcony_min' => $_GET['balcony_min'] ?? null,
	'category_id' => $_GET['category_id'] ?? null,
	'listing_type' => $_GET['listing_type'] ?? null,
	'status' => $_GET['status'] ?? null
];

// Lightweight chip params override
if (isset($_GET['cat']) && $_GET['cat'] !== '') { $filters['category_id'] = (int)$_GET['cat']; }
if (isset($_GET['lt']) && $_GET['lt'] !== '') { $filters['listing_type'] = $_GET['lt']; }

// Build dynamic WHERE clause and bindings
$where = [];
$types = '';
$params = [];

if ($filters['price_min'] !== null && $filters['price_min'] !== '') { $where[] = 'p.price >= ?'; $types .= 'd'; $params[] = (float)$filters['price_min']; }
if ($filters['price_max'] !== null && $filters['price_max'] !== '') { $where[] = 'p.price <= ?'; $types .= 'd'; $params[] = (float)$filters['price_max']; }
if ($filters['location']) { $where[] = 'p.location LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['location']) . '%'; }
if ($filters['landmark']) { $where[] = 'p.landmark LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['landmark']) . '%'; }
if ($filters['title']) { $where[] = 'p.title LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['title']) . '%'; }
if ($filters['description']) { $where[] = 'p.description LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['description']) . '%'; }
if ($filters['furniture_status']) { $where[] = 'p.furniture_status = ?'; $types .= 's'; $params[] = $filters['furniture_status']; }
if ($filters['ownership_type']) { $where[] = 'p.ownership_type = ?'; $types .= 's'; $params[] = $filters['ownership_type']; }
if ($filters['facing']) { $where[] = 'p.facing = ?'; $types .= 's'; $params[] = $filters['facing']; }
if ($filters['parking']) { $where[] = 'p.parking = ?'; $types .= 's'; $params[] = $filters['parking']; }
if ($filters['area_min'] !== null && $filters['area_min'] !== '') { $where[] = 'p.area >= ?'; $types .= 'd'; $params[] = (float)$filters['area_min']; }
if ($filters['area_max'] !== null && $filters['area_max'] !== '') { $where[] = 'p.area <= ?'; $types .= 'd'; $params[] = (float)$filters['area_max']; }
if ($filters['configuration']) { $where[] = 'p.configuration LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['configuration']) . '%'; }
if ($filters['balcony_min'] !== null && $filters['balcony_min'] !== '') { $where[] = 'p.balcony >= ?'; $types .= 'i'; $params[] = (int)$filters['balcony_min']; }
if ($filters['category_id']) { $where[] = 'p.category_id = ?'; $types .= 'i'; $params[] = (int)$filters['category_id']; }
if ($filters['listing_type']) { $where[] = 'p.listing_type = ?'; $types .= 's'; $params[] = $filters['listing_type']; }
if ($filters['status']) { $where[] = 'p.status = ?'; $types .= 's'; $params[] = $filters['status']; }

$sql = "SELECT p.id, p.title, p.description, p.price, p.location, p.landmark, p.area, p.configuration, p.furniture_status, p.ownership_type, p.facing, p.parking, p.balcony, p.listing_type, p.status, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, c.name AS category_name
	FROM properties p LEFT JOIN categories c ON c.id = p.category_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.created_at DESC LIMIT 25';

$stmtRecent = $mysqli->prepare($sql);
if ($stmtRecent && $types !== '') { $stmtRecent->bind_param($types, ...$params); }
$stmtRecent && $stmtRecent->execute();
$recentProperties = $stmtRecent ? $stmtRecent->get_result() : $mysqli->query("SELECT p.id, p.title, p.description, p.price, p.location, p.landmark, p.area, p.configuration, p.furniture_status, p.ownership_type, p.facing, p.parking, p.balcony, p.listing_type, p.status, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, NULL AS category_name FROM properties p ORDER BY p.created_at DESC LIMIT 8");
$recentBlogs = $mysqli->query("SELECT id, title, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM blogs ORDER BY created_at DESC LIMIT 5");
$recentTestimonials = $mysqli->query("SELECT name, rating, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM testimonials ORDER BY created_at DESC LIMIT 5");
$recentActivity = $mysqli->query("SELECT a.id, COALESCE(u.username,'System') as actor, a.action, DATE_FORMAT(a.created_at,'%b %d, %Y %h:%i %p') as created_at FROM activity_logs a LEFT JOIN admin_users u ON u.id = a.admin_id ORDER BY a.created_at DESC LIMIT 10");
// For toolbar chips
$pillCategories = $mysqli->query("SELECT id, name FROM categories ORDER BY name LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<style>
		/* Base */
		:root{
			--bg:#F1EFEC;/* page background */
			--card:#ffffff;/* surfaces */
			--muted:#6b7280;/* secondary text */
			--line:#e9eef5;/* borders */
			--brand-dark:#2f2f2f;/* logo dark */
			--primary:#e11d2a;/* logo red accent */
			--primary-600:#b91c1c;/* darker red hover */
			--radius:16px;
		}
		body{ background:var(--bg); color:#111827; }
		.content{ margin-left:260px; }
		/* Sidebar */
		/* Rounded sidebar with visible radius */
		.sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.content{ margin-left:284px; } /* account for sidebar margin */
		.brand{ font-weight:700; font-size:1.25rem; }
		.list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
		.list-group-item i{ width:18px; }
		.list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
		.list-group-item:hover{ background:#f8fafc; }
		/* Topbar */
		.navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.text-primary{ color:var(--primary)!important; }
		.input-group .form-control{ border-color:var(--line); }
		.input-group-text{ border-color:var(--line); }
		/* Cards */
		.card{ border:0; border-radius:var(--radius); background:var(--card); }
		.card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Modern toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		.toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
		.toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
		.toolbar .chip:hover{ border-color:#d1d5db; }
		.toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
		.toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
		/* Table */
		.table{ --bs-table-bg:transparent; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
		.table tbody tr{ border-top:1px solid var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Badges */
		.badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Filters */
		.form-label{ font-weight:600; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Drawer */
		.drawer{ position:fixed; top:0; right:-420px; width:420px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .25s ease; z-index:1040; }
		.drawer.open{ right:0; }
		.drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
		.drawer-body{ padding:16px; overflow:auto; height:calc(100vh - 64px); }
		.drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.2); opacity:0; pointer-events:none; transition:opacity .2s ease; z-index:1035; }
		.drawer-backdrop.open{ opacity:1; pointer-events:auto; }
		/* Modern list rows for blogs/testimonials */
		.item-row{ padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff; display:flex; align-items:center; justify-content:space-between; gap:12px; }
		.item-row:hover{ box-shadow:0 6px 18px rgba(0,0,0,.06); }
		.item-title{ font-weight:600; }
		.item-meta{ color:#6b7280; font-size:.9rem; }
		/* Mobile responsiveness */
		@media (max-width: 991.98px){ /* md breakpoint */
			.sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
			.sidebar.open{ left:12px; }
			.content{ margin-left:0; }
			.table{ font-size:.9rem; }
		}
		@media (max-width: 575.98px){ /* xs */
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.toolbar .row-bottom{ gap:6px; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('dashboard'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

		<div class="container-fluid p-4">
			<div class="row g-3 mb-3">
				<div class="col-12"><div class="h5 mb-0">Quick Access</div></div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Properties</div>
								<div class="h4 mb-0"><?php echo $totalProperties; ?></div>
							</div>
							<div class="text-primary"><i class="fa-solid fa-building fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Categories</div>
								<div class="h4 mb-0"><?php echo $totalCategories; ?></div>
							</div>
							<div class="text-warning"><i class="fa-solid fa-layer-group fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Blogs</div>
								<div class="h4 mb-0"><?php echo $totalBlogs; ?></div>
							</div>
							<div class="text-danger"><i class="fa-solid fa-rss fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Enquiries</div>
								<div class="h4 mb-0"><?php echo $totalEnquiries; ?></div>
							</div>
							<div class="text-success"><i class="fa-regular fa-envelope fa-lg"></i></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Search toolbar moved up here -->
			<div class="toolbar mb-4">
				<div class="row-top">
					<form class="d-flex flex-grow-1" method="get">
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
							<input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($filters['title'] ?? ''); ?>" placeholder="Search properties by name">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
				</div>
				<div class="row-bottom">
					<?php foreach(['Buy','Rent','PG/Co-living'] as $lt): ?>
						<?php $isActive = ($filters['listing_type'] ?? '') === $lt; ?>
						<a class="chip <?php echo $isActive ? 'active' : ''; ?>" href="?lt=<?php echo urlencode($lt); ?>"><?php echo $lt; ?></a>
					<?php endforeach; ?>
					<span class="divider"></span>
					<?php while($pc = $pillCategories->fetch_assoc()): ?>
						<?php $isC = (string)($filters['category_id'] ?? '') === (string)$pc['id']; ?>
						<a class="chip <?php echo $isC ? 'active' : ''; ?>" href="?cat=<?php echo (int)$pc['id']; ?>"><?php echo htmlspecialchars($pc['name']); ?></a>
					<?php endwhile; ?>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-xl-8">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">Properties</div>
								<a href="../properties/add.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-circle-plus me-1"></i>Add Property</a>
							</div>
							<table class="table align-middle" id="propertiesTable">
								<thead>
									<tr>
										<th>Name</th>
										<th>Category</th>
										<th>Listing</th>
										<th>Price</th>
										<th>Location</th>
										<th>Modified</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php while($row = $recentProperties->fetch_assoc()): ?>
									<tr data-prop='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
										<td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
										<td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></span></td>
										<td><span class="badge badge-soft"><?php echo htmlspecialchars($row['listing_type']); ?></span></td>
										<td>₹<?php echo number_format((float)$row['price']); ?></td>
										<td class="text-muted"><?php echo htmlspecialchars($row['location']); ?></td>
										<td class="text-muted"><?php echo $row['created_at']; ?></td>
										<td class="text-end"><button class="btn btn-sm btn-outline-secondary btn-view">View</button></td>
									</tr>
									<?php endwhile; ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="row g-4">
						<div class="col-md-6">
							<div class="card h-100">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between mb-3">
										<div class="h6 mb-0">Recent Blogs</div>
										<a href="#" class="small">View all</a>
									</div>
									<div>
										<?php while($b = $recentBlogs->fetch_assoc()): ?>
										<div class="item-row">
											<span class="item-title"><?php echo htmlspecialchars($b['title']); ?></span>
											<span class="item-meta"><?php echo $b['created_at']; ?></span>
										</div>
										<?php endwhile; ?>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card h-100">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between mb-3">
										<div class="h6 mb-0">Testimonials</div>
										<a href="#" class="small">View all</a>
									</div>
									<div>
										<?php while($t = $recentTestimonials->fetch_assoc()): ?>
										<div class="item-row">
											<span class="item-title"><?php echo htmlspecialchars($t['name']); ?></span>
											<span class="text-warning"><?php echo str_repeat('★', (int)$t['rating']); ?><span class="text-muted"><?php echo str_repeat('☆', 5-(int)$t['rating']); ?></span></span>
										</div>
										<?php endwhile; ?>
									</div>
								</div>
							</div>
						</div>
				</div>

				<div class="col-xl-4">
					<div class="card h-100 sticky-side">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<div class="h6 mb-0">Activity</div>
								<span class="badge bg-light text-dark border">Logs</span>
							</div>
							<div class="list-activity">
								<?php while($a = $recentActivity->fetch_assoc()): ?>
								<div class="d-flex align-items-start gap-2 mb-3">
									<div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="fa-solid fa-user"></i></div>
									<div>
										<div class="small"><strong><?php echo htmlspecialchars($a['actor']); ?></strong> - <?php echo htmlspecialchars($a['action']); ?></div>
										<div class="text-muted small"><?php echo $a['created_at']; ?></div>
									</div>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Drawer for property details and backdrop -->
				<div class="drawer" id="propDrawer">
					<div class="drawer-header">
						<h6 class="mb-0" id="drawerTitle">Property</h6>
						<button class="btn btn-sm btn-outline-secondary" onclick="closeDrawer()">Close</button>
					</div>
					<div class="drawer-body" id="drawerBody"></div>
				</div>
				<div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
			</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		function openDrawer(data){
			const drawer = document.getElementById('propDrawer');
			const backdrop = document.getElementById('drawerBackdrop');
			document.getElementById('drawerTitle').textContent = data.title || 'Property';
			const body = document.getElementById('drawerBody');
			body.innerHTML = `
				<div class="mb-3"><span class="text-muted small">Category</span><div><span class="badge bg-light text-dark border">${data.category_name || '—'}</span></div></div>
				<div class="mb-3"><span class="text-muted small">Listing</span><div><span class="badge badge-soft">${data.listing_type || ''}</span></div></div>
				<div class="row g-2">
					<div class="col-6"><div class="text-muted small">Price</div><div class="fw-semibold">₹${Number(data.price||0).toLocaleString()}</div></div>
					<div class="col-6"><div class="text-muted small">Area</div><div class="fw-semibold">${data.area || '—'} sqft</div></div>
				</div>
				<div class="mt-2"><i class="fa-solid fa-location-dot me-1"></i>${data.location || ''} <span class="text-muted">${data.landmark? '('+data.landmark+')' : ''}</span></div>
				<hr/>
				<div class="row g-2">
					<div class="col-6"><div class="text-muted small">Config</div><div>${data.configuration || '—'}</div></div>
					<div class="col-6"><div class="text-muted small">Furniture</div><div>${data.furniture_status || '—'}</div></div>
					<div class="col-6"><div class="text-muted small">Ownership</div><div>${data.ownership_type || '—'}</div></div>
					<div class="col-6"><div class="text-muted small">Facing</div><div>${data.facing || '—'}</div></div>
					<div class="col-6"><div class="text-muted small">Parking</div><div>${data.parking || '—'}</div></div>
					<div class="col-6"><div class="text-muted small">Balcony</div><div>${data.balcony || 0}</div></div>
				</div>
				<hr/>
				<div><div class="text-muted small">Description</div><div>${(data.description || '').slice(0,800)}</div></div>
			`;
			drawer.classList.add('open');
			backdrop.classList.add('open');
		}
		function closeDrawer(){
			document.getElementById('propDrawer').classList.remove('open');
			document.getElementById('drawerBackdrop').classList.remove('open');
		}
		// attach events
		document.addEventListener('DOMContentLoaded', function(){
			document.querySelectorAll('#propertiesTable .btn-view').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-prop'));
					openDrawer(data);
				});
			});
		});
	</script>
</body>
</html>
