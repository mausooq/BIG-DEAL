<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

function getPropertyImages($propertyId) {
	$mysqli = db();
	$stmt = $mysqli->prepare("SELECT image_url FROM property_images WHERE property_id = ? ORDER BY id ASC");
	$stmt->bind_param("i", $propertyId);
	$stmt->execute();
	$result = $stmt->get_result();
	$images = [];
	while ($row = $result->fetch_assoc()) {
		$images[] = $row;
	}
	return $images;
}

// Stats
$totalProperties = fetchScalar("SELECT COUNT(*) FROM properties");
$totalCategories = fetchScalar("SELECT COUNT(*) FROM categories");
$totalBlogs = fetchScalar("SELECT COUNT(*) FROM blogs");
$totalEnquiries = fetchScalar("SELECT COUNT(*) FROM enquiries");

// Chart data for Monthly Sales Trend
$monthlyQuery = "SELECT 
    DATE_FORMAT(created_at, '%b %Y') as month,
    COUNT(*) as count
FROM properties 
WHERE status = 'sold'
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY MIN(created_at) ASC
LIMIT 12";
$monthlyResult = db()->query($monthlyQuery);
$monthlyLabels = [];
$monthlyCounts = [];
while ($row = $monthlyResult->fetch_assoc()) {
    $monthlyLabels[] = $row['month'];
    $monthlyCounts[] = (int)$row['count'];
}

// If no data, provide sample data
if (empty($monthlyLabels)) {
    $monthlyLabels = ['Jan 2023', 'Feb 2023', 'Mar 2023', 'Apr 2023', 'May 2023', 'Jun 2023'];
    $monthlyCounts = [4, 6, 8, 5, 10, 7];
}

// Chart data for Top Performing Cities
$cityQuery = "SELECT 
    location,
    COUNT(CASE WHEN status = 'Sold' THEN 1 END) as sold,
    COUNT(CASE WHEN status != 'Sold' THEN 1 END) as available
FROM properties
GROUP BY location
ORDER BY sold DESC
LIMIT 5";
$cityResult = db()->query($cityQuery);
$cityLabels = [];
$citySold = [];
$cityAvail = [];
while ($row = $cityResult->fetch_assoc()) {
    $cityLabels[] = $row['location'];
    $citySold[] = (int)$row['sold'];
    $cityAvail[] = (int)$row['available'];
}

// If no data, provide sample data
if (empty($cityLabels)) {
    $cityLabels = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Miami'];
    $citySold = [12, 9, 8, 7, 6];
    $cityAvail = [5, 8, 4, 6, 9];
}

// Recent properties
$monthlySql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') ym, COUNT(*) cnt
	FROM properties p WHERE p.status = 'Sold'
	GROUP BY ym
	ORDER BY ym ASC";
$monthly = $mysqli->query($monthlySql);
// Map query results
$monthlyMap = [];
while ($r = $monthly->fetch_assoc()) { $monthlyMap[$r['ym']] = (int)$r['cnt']; }

// Build month sequence for last 12 months
$monthlyLabels = [];
$monthlyCounts = [];
try {
    $endDate = new DateTime('first day of this month');
    $startDate = (clone $endDate)->modify('-11 months');
    $cursor = clone $startDate;
    while ($cursor <= $endDate) {
        $ym = $cursor->format('Y-m');
        $monthlyLabels[] = $ym;
        $monthlyCounts[] = $monthlyMap[$ym] ?? 0;
        $cursor->modify('+1 month');
    }
} catch (Exception $e) {
    // Fallback: just use whatever came back
    $monthlyLabels = array_keys($monthlyMap);
    $monthlyCounts = array_values($monthlyMap);
}

// Chart: city-wise available vs sold (top 10 cities by total)
$citySql = "SELECT COALESCE(ci.name, 'Unknown City') AS city,
    SUM(CASE WHEN p.status='Available' THEN 1 ELSE 0 END) AS avail_cnt,
    SUM(CASE WHEN p.status='Sold' THEN 1 ELSE 0 END) AS sold_cnt
    FROM properties p
    LEFT JOIN properties_location pl ON pl.property_id = p.id
    LEFT JOIN cities ci ON ci.id = pl.city_id
    GROUP BY city
    ORDER BY (SUM(CASE WHEN p.status='Available' THEN 1 ELSE 0 END)+SUM(CASE WHEN p.status='Sold' THEN 1 ELSE 0 END)) DESC
    LIMIT 10";
$cityRes = $mysqli->query($citySql);
$cityLabels = [];
$cityAvail = [];
$citySold = [];
while ($r = $cityRes->fetch_assoc()) { 
    $cityLabels[] = $r['city']; 
    $cityAvail[] = (int)$r['avail_cnt']; 
    $citySold[] = (int)$r['sold_cnt']; 
}

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

$sql = "SELECT p.id, p.title, p.description, p.price, p.location, p.landmark, p.area, p.configuration, p.furniture_status, p.ownership_type, p.facing, p.parking, p.balcony, p.listing_type, p.status, p.map_embed_link, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, c.name AS category_name, c.id AS category_id
	FROM properties p LEFT JOIN categories c ON c.id = p.category_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.created_at DESC LIMIT 25';
	
$stmtRecent = $mysqli->prepare($sql);
if ($stmtRecent && $types !== '') { $stmtRecent->bind_param($types, ...$params); }
$stmtRecent && $stmtRecent->execute();
$recentProperties = $stmtRecent ? $stmtRecent->get_result() : $mysqli->query("SELECT p.id, p.title, p.description, p.price, p.location, p.landmark, p.area, p.configuration, p.furniture_status, p.ownership_type, p.facing, p.parking, p.balcony, p.listing_type, p.status, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, NULL AS category_name, NULL AS category_id FROM properties p ORDER BY p.created_at DESC LIMIT 8");
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
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		/* Base */
		body{ background:var(--bg); color:#111827; }
		/* Topbar */
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
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
		/* Activity feed container */
		.list-activity {
			max-height: 420px;
			overflow-y: auto;
			padding-right: 4px; /* lil breathing room */
			margin-right: 0;    /* remove awkward gap */
		}
		/* Each activity item */
		.activity-item {
			font-size: 14px;
			padding: 6px 0;
			border-bottom: 1px solid #f1f1f1;
		}
		/* Smooth + minimal scrollbar */
		.list-activity::-webkit-scrollbar {
			width: 6px;
		}
		.list-activity::-webkit-scrollbar-thumb {
			background: #d1d5db;
			border-radius: 10px;
		}
		.list-activity::-webkit-scrollbar-track {
			background: transparent;
		}
		.sticky-side{ position:sticky; top:96px; }
		/* Filters */
		.form-label{ font-weight:600; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		.btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
		.btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
		/* Chart styles */
		.chart-wrap {
			height: 300px;
			position: relative;
		}
		.chart-card {
			background-color: white;
			border-radius: 0.75rem;
			box-shadow: 0 1px 2px rgba(0,0,0,0.05);
			padding: 1.25rem;
		}
		.chart-title {
			font-size: 1rem;
			font-weight: 600;
			color: #1e293b;
			margin-bottom: 1.25rem;
		}
		/* Modern Animated Action Buttons (parity with properties table) */
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
			box-shadow: 
				0 4px 16px rgba(0, 0, 0, 0.15),
				inset 0 1px 0 rgba(255, 255, 255, 0.2);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 14px;
			margin: 0 2px;
		}

		.modern-btn::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
			transition: left 0.6s;
		}

		.modern-btn:hover::before { left: 100%; }
		.modern-btn:hover {
			transform: translateY(-2px) scale(1.05);
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.3);
			filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.3));
		}
		.modern-btn:active { transform: translateY(-1px) scale(1.02); transition: all 0.1s ease; }
		.modern-btn .icon { transition: all 0.3s ease; }
		.modern-btn:hover .icon { transform: scale(1.2) rotate(5deg); }

		/* View Button - Neutral grey (parity) */
		.view-btn { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
		.view-btn:hover { background: #e5e7eb; border-color: #d1d5db; color: #374151; }

		/* Ripple effect */
		.ripple {
			position: absolute;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.4);
			transform: scale(0);
			animation: ripple-animation 0.6s linear;
			pointer-events: none;
		}
		@keyframes ripple-animation { to { transform: scale(4); opacity: 0; } }
		/* Drawer */
		.drawer{ position:fixed; top:0; right:-500px; width:500px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .3s cubic-bezier(0.4, 0.0, 0.2, 1); z-index:1040; }
		.drawer.open{ right:0; }
		.drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
		.drawer-body{ padding:16px; overflow:auto; height:calc(100vh - 64px); }
		.drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.2); opacity:0; pointer-events:none; transition:opacity .2s ease; z-index:1035; }
		.drawer-backdrop.open{ opacity:1; pointer-events:auto; }
		/* Image gallery styles */
		.drawer-image{ width:100%; height:250px; object-fit:contain; border-radius:8px; margin-bottom:1rem; background:#f8f9fa; border:1px solid #e9ecef; cursor:pointer; transition:transform 0.2s ease; }
		.drawer-image:hover{ transform:scale(1.02); }
		.drawer-image-gallery{ display:flex; gap:8px; margin-top:1rem; }
		.drawer-image-thumb{ width:90px; height:90px; object-fit:cover; border-radius:6px; cursor:pointer; border:2px solid transparent; transition:all 0.2s ease; flex-shrink:0; }
		.drawer-image-thumb:hover{ border-color:#3b82f6; transform:scale(1.05); }
		.drawer-image-thumb.active{ border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59, 130, 246, 0.2); }
		.more-images-btn{ 
			background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
			color: white; 
			display: flex; 
			align-items: center; 
			justify-content: center; 
			font-size: 0.8rem; 
			font-weight: 600;
		}
		.more-images-content{ text-align: center; }
		.more-count{ font-size: 0.7rem; }
		.drawer-map-container{ position: relative; width: 100%; height: 200px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 8px; }
		.property-detail{ margin-bottom: 1rem; }
		.property-detail .label{ font-size: 0.875rem; color: #6b7280; font-weight: 600; margin-bottom: 0.25rem; }
		.property-detail .value{ font-size: 0.95rem; color: #111827; }
		.two-col{ display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
		.divider{ height: 1px; background: #e5e7eb; margin: 1.5rem 0; }
		/* Mobile responsiveness */
		@media (max-width: 991.98px){ /* md breakpoint */
			.table{ font-size:.9rem; }
			.drawer{ width:100vw; right:-100vw; }
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
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Dashboard'); ?>

		<div class="container-fluid p-4">
			<div class="row mb-3">
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

			<div class="row g-4 mb-4">
				<div class="col-xl-6">	
					<div class="card chart-card">
						<div class="chart-title">Monthly Sales Trend</div>
						<div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
					</div>
				</div>
				<div class="col-xl-6">
					<div class="card chart-card">
						<div class="chart-title">Top Performing Cities</div>
						<div class="chart-wrap"><canvas id="chartCity"></canvas></div>
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
						<a class="chip js-filter <?php echo $isActive ? 'active' : ''; ?>" href="#" data-filter="lt" data-value="<?php echo htmlspecialchars($lt, ENT_QUOTES); ?>" role="button"><?php echo $lt; ?></a>
					<?php endforeach; ?>
					<span class="divider"></span>
					<?php while($pc = $pillCategories->fetch_assoc()): ?>
						<?php $isC = (string)($filters['category_id'] ?? '') === (string)$pc['id']; ?>
						<a class="chip js-filter <?php echo $isC ? 'active' : ''; ?>" href="#" data-filter="cat" data-value="<?php echo (int)$pc['id']; ?>" role="button"><?php echo htmlspecialchars($pc['name']); ?></a>
					<?php endwhile; ?>
				</div>
			</div>

			<div class="row">
				<div class="col-xl-8">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">Properties</div>
								<a href="../properties/add.php" class="btn-animated-add noselect btn-sm">
									<span class="text">Add Property</span>
									<span class="icon">
										<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
										<span class="buttonSpan">+</span>
									</span>
								</a>
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
										<td><?php if (!empty($row['category_id'])): ?><a href="index.php?cat=<?php echo (int)$row['category_id']; ?>" class="badge bg-light text-dark border text-decoration-none"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></a><?php else: ?><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></span><?php endif; ?></td>
										<td><span class="badge badge-soft"><?php echo htmlspecialchars($row['listing_type']); ?></span></td>
										<td>₹<?php echo number_format((float)$row['price']); ?></td>
										<td class="text-muted"><?php echo htmlspecialchars($row['location']); ?></td>
										<td class="text-muted"><?php echo $row['created_at']; ?></td>
										<td class="text-end"><button class="modern-btn view-btn btn-view" title="View"><span class="icon"><i class="fa-solid fa-eye"></i></span></button></td>
									</tr>
									<?php endwhile; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="col-xl-4">
					<div class="card h-95">
						<div class="card-body">
							<div class="h6 mb-2">Activity</div>
							<a href="../activity-logs/index.php" class="badge bg-light text-dark border text-decoration-none mb-2 d-inline-block" role="button">Logs</a>
							<div class="list-activity">
								<?php while($a = $recentActivity->fetch_assoc()): ?>
								<div class="d-flex align-items-start gap-2 mb-3 activity-item">
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
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	<script>
		// Helper functions
		function escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
		
		function formatNumber(num) {
			return Number(num || 0).toLocaleString();
		}
		
		function formatDate(dateString) {
			if (!dateString) return '—';
			const date = new Date(dateString);
			return date.toLocaleDateString('en-US', { 
				year: 'numeric', 
				month: 'short', 
				day: 'numeric' 
			});
		}
		
		function getStatusBadgeClass(status) {
			switch(status?.toLowerCase()) {
				case 'active': return 'bg-success';
				case 'inactive': return 'bg-secondary';
				case 'pending': return 'bg-warning';
				case 'sold': return 'bg-danger';
				default: return 'bg-light text-dark';
			}
		}
		
		// Initialize charts
		document.addEventListener('DOMContentLoaded', function() {
			// Monthly Sales Trend Chart
			const monthlyCtx = document.getElementById('chartMonthly').getContext('2d');
			const monthlyChart = new Chart(monthlyCtx, {
				type: 'line',
				data: {
					labels: <?php echo json_encode($monthlyLabels); ?>,
					datasets: [{
						label: 'Properties Sold',
						data: <?php echo json_encode($monthlyCounts); ?>,
						backgroundColor: 'rgba(239, 68, 68, 0.1)',
						borderColor: 'rgba(239, 68, 68, 1)',
						borderWidth: 2,
						tension: 0.4,
						fill: true,
						pointRadius: 0
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0,
								font: {
									size: 10
								}
							},
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						x: {
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							},
							ticks: {
								font: {
									size: 10
								}
							}
						}
					},
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(255, 255, 255, 0.9)',
							titleColor: '#1e293b',
							bodyColor: '#1e293b',
							borderColor: 'rgba(0, 0, 0, 0.1)',
							borderWidth: 1,
							padding: 10,
							boxPadding: 5,
							usePointStyle: true,
							callbacks: {
								title: function(context) {
									return context[0].label;
								}
							}
						}
					}
				}
			});
			
			// City-wise Available vs Sold Chart
			const cityCtx = document.getElementById('chartCity').getContext('2d');
			const cityChart = new Chart(cityCtx, {
				type: 'bar',
				data: {
					labels: <?php echo json_encode($cityLabels); ?>,
					datasets: [{
						label: 'Available',
						data: <?php echo json_encode($cityAvail); ?>,
						backgroundColor: 'rgba(252, 165, 165, 0.8)',
						borderColor: 'rgba(252, 165, 165, 1)',
						borderWidth: 0,
						borderRadius: 4,
						barPercentage: 0.6,
						categoryPercentage: 0.7
					},
					{
						label: 'Sold',
						data: <?php echo json_encode($citySold); ?>,
						backgroundColor: 'rgba(220, 38, 38, 0.8)',
						borderColor: 'rgba(220, 38, 38, 1)',
						borderWidth: 0,
						borderRadius: 4,
						barPercentage: 0.6,
						categoryPercentage: 0.7
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0,
								font: {
									size: 10
								}
							},
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						x: {
							grid: {
								display: false
							},
							ticks: {
								font: {
									size: 10
								}
							}
						}
					},
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								usePointStyle: true,
								boxWidth: 8,
								padding: 15,
								font: {
									size: 11
								}
							}
						},
						tooltip: {
							backgroundColor: 'rgba(255, 255, 255, 0.9)',
							titleColor: '#1e293b',
							bodyColor: '#1e293b',
							borderColor: 'rgba(0, 0, 0, 0.1)',
							borderWidth: 1,
							padding: 10,
							boxPadding: 5,
							usePointStyle: true
						}
					}
				}
			});
		});
		
		async function openDrawer(data){
			const drawer = document.getElementById('propDrawer');
			const backdrop = document.getElementById('drawerBackdrop');
			document.getElementById('drawerTitle').textContent = data.title || 'Property';
			const body = document.getElementById('drawerBody');
			
			// Show loading state
			body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading images...</p></div>';
			
			drawer.classList.add('open');
			backdrop.classList.add('open');
			
			try {
				// Fetch property images
				const response = await fetch(`../properties/get_property_details.php?id=${data.id}`);
				const result = await response.json();
				const images = result.images || [];
				
				// Build enhanced content
				const content = `
					${images.length > 0 ? `
						<div class="property-detail">
							<div class="label">Property Images (${images.length})</div>
							<img src="../../uploads/properties/${images[0].image_url}" 
								 alt="Property Image" 
								 class="drawer-image" 
								 id="mainImage"
								 loading="lazy">
							${images.length > 1 ? `
								<div class="drawer-image-gallery">
									${images.slice(0, 4).map((img, index) => `
										<img src="../../uploads/properties/${img.image_url}" 
											 alt="Property Image ${index + 1}" 
											 class="drawer-image-thumb ${index === 0 ? 'active' : ''}" 
											 data-image-url="${img.image_url}"
											 data-index="${index}"
											 loading="lazy">
									`).join('')}
									${images.length > 4 ? `
										<div class="drawer-image-thumb more-images-btn" 
											 data-total="${images.length}" 
											 data-remaining="${images.length - 4}">
											<div class="more-images-content">
												<i class="fa-solid fa-plus"></i>
												<span class="more-count">+${images.length - 4}</span>
											</div>
										</div>
									` : ''}
								</div>
							` : ''}
						</div>
						<div class="divider"></div>
					` : ''}
					
					<div class="property-detail">
						<div class="label">Category</div>
						<div class="value badge bg-light text-dark border">${escapeHtml(data.category_name) || '—'}</div>
					</div>
					<div class="property-detail">
						<div class="label">Listing</div>
						<div class="value badge badge-soft">${escapeHtml(data.listing_type) || ''}</div>
					</div>
					<div class="two-col">
						<div class="property-detail">
							<div class="label">Price</div>
							<div class="value price">₹${formatNumber(data.price)}</div>
						</div>
						<div class="property-detail">
							<div class="label">Area</div>
							<div class="value area">${escapeHtml(data.area) || '—'} sqft</div>
						</div>
					</div>
					<div class="property-detail">
						<div class="label">Location</div>
						<div class="value location">
							<i class="fa-solid fa-location-dot"></i>
							${escapeHtml(data.location) || ''} ${data.landmark ? '(' + escapeHtml(data.landmark) + ')' : ''}
						</div>
					</div>
					<div class="divider"></div>
					<div class="two-col">
						<div class="property-detail">
							<div class="label">Config</div>
							<div class="value">${escapeHtml(data.configuration) || '—'}</div>
						</div>
						<div class="property-detail">
							<div class="label">Furniture</div>
							<div class="value">${escapeHtml(data.furniture_status) || '—'}</div>
						</div>
						<div class="property-detail">
							<div class="label">Ownership</div>
							<div class="value">${escapeHtml(data.ownership_type) || '—'}</div>
						</div>
						<div class="property-detail">
							<div class="label">Facing</div>
							<div class="value">${escapeHtml(data.facing) || '—'}</div>
						</div>
						<div class="property-detail">
							<div class="label">Parking</div>
							<div class="value">${escapeHtml(data.parking) || '—'}</div>
						</div>
						<div class="property-detail">
							<div class="label">Balcony</div>
							<div class="value">${data.balcony || 0}</div>
						</div>
					</div>
					${data.description ? `
						<div class="divider"></div>
						<div class="property-detail">
							<div class="label">Description</div>
							<div class="value">${escapeHtml(data.description).substring(0, 300)}${data.description.length > 300 ? '...' : ''}</div>
						</div>
					` : ''}
					${data.map_embed_link ? `
						<div class="divider"></div>
						<div class="property-detail">
							<div class="label">Location Map</div>
							<div class="value">
								<div class="drawer-map-container">
									<iframe 
										src="${escapeHtml(data.map_embed_link)}" 
										width="100%" 
										height="100%" 
										style="border:0; border-radius: 8px;" 
										allowfullscreen="" 
										loading="lazy" 
										referrerpolicy="no-referrer-when-downgrade">
									</iframe>
								</div>
								<small class="text-muted mt-2 d-block">
									<i class="fa-solid fa-info-circle me-1"></i>
									Interactive map showing property location
								</small>
							</div>
						</div>
					` : ''}
					<div class="divider"></div>
					<div class="property-detail">
						<div class="label">Property ID</div>
						<div class="value">#${data.id}</div>
					</div>
					<div class="property-detail">
						<div class="label">Status</div>
						<div class="value">
							<span class="badge ${getStatusBadgeClass(data.status)}">${escapeHtml(data.status)}</span>
						</div>
					</div>
					<div class="property-detail">
						<div class="label">Created</div>
						<div class="value">${formatDate(data.created_at)}</div>
					</div>
					<div class="divider"></div>
					<div class="property-detail">
						<div class="label">Actions</div>
						<div class="value">
							<div class="d-flex gap-2 flex-wrap">
								<a href="../properties/view.php?id=${data.id}" class="btn btn-outline-primary btn-sm" target="_blank">
									<i class="fa-solid fa-eye me-1"></i>View Details
								</a>
								<a href="../properties/edit.php?id=${data.id}" class="btn btn-outline-success btn-sm" target="_blank">
									<i class="fa-solid fa-pen me-1"></i>Edit Property
								</a>
							</div>
						</div>
					</div>
				`;
				
				body.innerHTML = content;
				
				// Add image gallery functionality
				if (images.length > 1) {
					document.querySelectorAll('.drawer-image-thumb').forEach((thumb, index) => {
						thumb.addEventListener('click', function() {
							if (this.classList.contains('more-images-btn')) return;
							
							// Update main image
							const mainImage = document.getElementById('mainImage');
							mainImage.src = this.src;
							
							// Update active thumbnail
							document.querySelectorAll('.drawer-image-thumb').forEach(t => t.classList.remove('active'));
							this.classList.add('active');
						});
					});
				}
				
			} catch (error) {
				console.error('Error loading property details:', error);
				body.innerHTML = `
					<div class="alert alert-warning">
						<i class="fa-solid fa-exclamation-triangle me-2"></i>
						Unable to load property details. Showing basic information.
					</div>
					<div class="property-detail">
						<div class="label">Category</div>
						<div class="value badge bg-light text-dark border">${escapeHtml(data.category_name) || '—'}</div>
					</div>
					<div class="property-detail">
						<div class="label">Price</div>
						<div class="value">₹${formatNumber(data.price)}</div>
					</div>
					<div class="property-detail">
						<div class="label">Location</div>
						<div class="value">${escapeHtml(data.location) || ''}</div>
					</div>
				`;
			}
		}
		function closeDrawer(){
			document.getElementById('propDrawer').classList.remove('open');
			document.getElementById('drawerBackdrop').classList.remove('open');
		}
		// attach events
		document.addEventListener('DOMContentLoaded', function(){
			// Add ripple effect to modern buttons (match properties)
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
			document.querySelectorAll('.modern-btn').forEach(btn => {
				btn.addEventListener('click', createRipple);
			});
			// Enhanced button click animation
			document.addEventListener('click', function(event) {
				const modernBtn = event.target.closest('.modern-btn');
				if (modernBtn) {
					modernBtn.style.animation = 'none';
					modernBtn.style.transform = 'scale(0.95)';
					setTimeout(() => {
						modernBtn.style.animation = '';
						modernBtn.style.transform = '';
					}, 150);
				}
			});
			// Toggleable chip filters for listing type (lt) and category (cat)
			document.querySelectorAll('.js-filter').forEach(function(chip){
				chip.addEventListener('click', function(e){
					e.preventDefault();
					var param = this.getAttribute('data-filter');
					var val = this.getAttribute('data-value');
					var url = new URL(window.location.href);
					var params = url.searchParams;
					// Normalize keys: our PHP accepts lt and cat as lightweight params
					var key = param === 'lt' ? 'lt' : (param === 'cat' ? 'cat' : param);
					var currently = params.get(key);
					if (currently && currently.toString() === val.toString()) {
						// Clicking the active chip clears this filter
						params.delete(key);
					} else {
						// Set or switch the filter
						params.set(key, val);
					}
					// Keep other params intact; navigate
					url.search = params.toString();
					window.location.href = url.toString();
				});
			});
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