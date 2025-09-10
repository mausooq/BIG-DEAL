<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Filters
$mysqli = db();
$filters = [
	'start_date' => $_GET['start_date'] ?? '',
	'end_date' => $_GET['end_date'] ?? '',
	'listing_type' => $_GET['listing_type'] ?? '',
	'status' => $_GET['status'] ?? '',
];

$where = [];
$types = '';
$params = [];

if ($filters['start_date'] !== '') { $where[] = 'p.created_at >= ?'; $types .= 's'; $params[] = $filters['start_date'] . ' 00:00:00'; }
if ($filters['end_date'] !== '') { $where[] = 'p.created_at <= ?'; $types .= 's'; $params[] = $filters['end_date'] . ' 23:59:59'; }
if ($filters['listing_type'] !== '') { $where[] = 'p.listing_type = ?'; $types .= 's'; $params[] = $filters['listing_type']; }
if ($filters['status'] !== '') { $where[] = 'p.status = ?'; $types .= 's'; $params[] = $filters['status']; }

// Build a base WHERE that is always valid to safely append AND conditions
$baseWhere = empty($where) ? ' WHERE 1=1' : ' WHERE ' . implode(' AND ', $where);

// KPI cards
$kpiSql = [
	// Use placeholders for status conditions and a safe base WHERE
	'total_available' => "SELECT COUNT(*) FROM properties p $baseWhere AND p.status = ?",
	'total_sold' => "SELECT COUNT(*) FROM properties p $baseWhere AND p.status = ?",
	'total_rented' => "SELECT COUNT(*) FROM properties p $baseWhere AND p.status = ?",
	'new_enquiries' => "SELECT COUNT(*) FROM enquiries e" . (
		($filters['start_date'] || $filters['end_date']) ?
		' WHERE ' . implode(' AND ', array_filter([
			$filters['start_date'] ? 'e.created_at >= ?' : '',
			$filters['end_date'] ? 'e.created_at <= ?' : ''
		])) : ''
	)
];

function fetchCount(string $sql, string $types = '', array $params = []) {
	$mysqli = db();
	$stmt = $mysqli->prepare($sql);
	if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
	$stmt && $stmt->execute();
	$res = $stmt ? $stmt->get_result() : $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Build param lists for KPI queries
$kpiTypesAvail = $types . 's';
$kpiParamsAvail = $params;
array_push($kpiParamsAvail, 'Available');

$kpiTypesSold = $types . 's';
$kpiParamsSold = $params;
array_push($kpiParamsSold, 'Sold');

$kpiTypesRent = $types . 's';
$kpiParamsRent = $params;
array_push($kpiParamsRent, 'Rented');

// Enquiry params only include date range
$enqTypes = '';
$enqParams = [];
if ($filters['start_date'] !== '') { $enqTypes .= 's'; $enqParams[] = $filters['start_date'] . ' 00:00:00'; }
if ($filters['end_date'] !== '') { $enqTypes .= 's'; $enqParams[] = $filters['end_date'] . ' 23:59:59'; }

$kpis = [
	'available' => fetchCount($kpiSql['total_available'], $kpiTypesAvail, $kpiParamsAvail),
	'sold' => fetchCount($kpiSql['total_sold'], $kpiTypesSold, $kpiParamsSold),
	'rented' => fetchCount($kpiSql['total_rented'], $kpiTypesRent, $kpiParamsRent),
	'enquiries' => fetchCount($kpiSql['new_enquiries'], $enqTypes, $enqParams),
];

// Chart: monthly properties added (last 12 months within filter)
$monthlySql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') ym, COUNT(*) cnt
	FROM properties p $baseWhere
	GROUP BY ym
	ORDER BY ym ASC";
$stmt = $mysqli->prepare($monthlySql);
if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$monthly = $stmt ? $stmt->get_result() : $mysqli->query($monthlySql);
$monthlyLabels = [];
$monthlyCounts = [];
while ($r = $monthly->fetch_assoc()) { $monthlyLabels[] = $r['ym']; $monthlyCounts[] = (int)$r['cnt']; }

// Chart: listing type distribution
$ltSql = "SELECT p.listing_type lt, COUNT(*) cnt FROM properties p $baseWhere GROUP BY lt";
$stmt2 = $mysqli->prepare($ltSql);
if ($stmt2 && $types !== '') { $stmt2->bind_param($types, ...$params); }
$stmt2 && $stmt2->execute();
$lt = $stmt2 ? $stmt2->get_result() : $mysqli->query($ltSql);
$ltLabels = [];
$ltCounts = [];
while ($r = $lt->fetch_assoc()) { $ltLabels[] = $r['lt']; $ltCounts[] = (int)$r['cnt']; }

// Chart: status distribution
$stSql = "SELECT p.status st, COUNT(*) cnt FROM properties p $baseWhere GROUP BY st";
$stmt3 = $mysqli->prepare($stSql);
if ($stmt3 && $types !== '') { $stmt3->bind_param($types, ...$params); }
$stmt3 && $stmt3->execute();
$st = $stmt3 ? $stmt3->get_result() : $mysqli->query($stSql);
$stLabels = [];
$stCounts = [];
while ($r = $st->fetch_assoc()) { $stLabels[] = $r['st']; $stCounts[] = (int)$r['cnt']; }

// Chart: average price by month
$avgSql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') ym, AVG(p.price) avg_price FROM properties p $baseWhere GROUP BY ym ORDER BY ym ASC";
$stmt4 = $mysqli->prepare($avgSql);
if ($stmt4 && $types !== '') { $stmt4->bind_param($types, ...$params); }
$stmt4 && $stmt4->execute();
$avg = $stmt4 ? $stmt4->get_result() : $mysqli->query($avgSql);
$avgLabels = [];
$avgValues = [];
while ($r = $avg->fetch_assoc()) { $avgLabels[] = $r['ym']; $avgValues[] = (float)$r['avg_price']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sales Analytics - Big Deal</title>
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
		.btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
		.btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
		/* Drawer */
		.drawer{ position:fixed; top:0; right:-500px; width:500px; height:100vh; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,.08); transition:right .3s cubic-bezier(0.4, 0.0, 0.2, 1); z-index:1040; }
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
			.sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
			.sidebar.open{ left:12px; }
			.content{ margin-left:0; }
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
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('sales-analytics'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

		<div class="container-fluid p-4">
			<div class="d-flex align-items-center justify-content-between mb-3">
				<div class="h5 mb-0">Sales Analytics</div>
			</div>

			<div class="toolbar mb-4">
				<form class="row g-2" method="get">
					<div class="col-sm-6 col-md-3">
						<label class="form-label">Start date</label>
						<input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
					</div>
					<div class="col-sm-6 col-md-3">
						<label class="form-label">End date</label>
						<input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
					</div>
					<div class="col-sm-6 col-md-3">
						<label class="form-label">Listing type</label>
						<select class="form-select" name="listing_type">
							<option value="">All</option>
							<?php foreach (['Buy','Rent','PG/Co-living'] as $lt): ?>
								<option value="<?php echo $lt; ?>" <?php echo ($filters['listing_type']===$lt?'selected':''); ?>><?php echo $lt; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-sm-6 col-md-3">
						<label class="form-label">Status</label>
						<select class="form-select" name="status">
							<option value="">All</option>
							<?php foreach (['Available','Sold','Rented'] as $st): ?>
								<option value="<?php echo $st; ?>" <?php echo ($filters['status']===$st?'selected':''); ?>><?php echo $st; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-12 d-flex gap-2">
						<button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
						<a class="btn btn-outline-secondary" href="index.php">Reset</a>
					</div>
				</form>
			</div>

			<div class="row mb-4">
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">Available</div><div class="h4 mb-0"><?php echo $kpis['available']; ?></div></div>
						<div class="text-success"><i class="fa-solid fa-circle-check fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">Sold</div><div class="h4 mb-0"><?php echo $kpis['sold']; ?></div></div>
						<div class="text-danger"><i class="fa-solid fa-sack-dollar fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">Rented</div><div class="h4 mb-0"><?php echo $kpis['rented']; ?></div></div>
						<div class="text-primary"><i class="fa-solid fa-key fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">New Enquiries</div><div class="h4 mb-0"><?php echo $kpis['enquiries']; ?></div></div>
						<div class="text-warning"><i class="fa-regular fa-envelope fa-lg"></i></div>
					</div></div>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-xl-8">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Monthly Properties Added</div>
						<canvas id="chartMonthly" height="120"></canvas>
					</div></div>
				</div>
				<div class="col-xl-4">
					<div class="card mb-4"><div class="card-body">
						<div class="h6 mb-3">Listing Type Mix</div>
						<canvas id="chartListing" height="180"></canvas>
					</div></div>
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Status Distribution</div>
						<canvas id="chartStatus" height="180"></canvas>
					</div></div>
				</div>
			</div>

			<div class="row g-4 mt-1">
				<div class="col-12">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Average Price by Month</div>
						<canvas id="chartAvgPrice" height="100"></canvas>
					</div></div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	<script>
		const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
		const monthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
		const ltLabels = <?php echo json_encode($ltLabels); ?>;
		const ltCounts = <?php echo json_encode($ltCounts); ?>;
		const stLabels = <?php echo json_encode($stLabels); ?>;
		const stCounts = <?php echo json_encode($stCounts); ?>;
		const avgLabels = <?php echo json_encode($avgLabels); ?>;
		const avgValues = <?php echo json_encode($avgValues); ?>;

		const brand = '#e11d2a';
		const brand600 = '#b91c1c';

		new Chart(document.getElementById('chartMonthly'), {
			type: 'bar',
			data: { labels: monthlyLabels, datasets: [{ label: 'Properties', data: monthlyCounts, backgroundColor: brand }] },
			options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
		});

		new Chart(document.getElementById('chartListing'), {
			type: 'doughnut',
			data: { labels: ltLabels, datasets: [{ data: ltCounts, backgroundColor: ['#f97316','#2563eb','#0ea5e9'] }] },
			options: { plugins: { legend: { position: 'bottom' } } }
		});

		new Chart(document.getElementById('chartStatus'), {
			type: 'pie',
			data: { labels: stLabels, datasets: [{ data: stCounts, backgroundColor: ['#16a34a','#ef4444','#3b82f6','#6b7280'] }] },
			options: { plugins: { legend: { position: 'bottom' } } }
		});

		new Chart(document.getElementById('chartAvgPrice'), {
			type: 'line',
			data: { labels: avgLabels, datasets: [{ label: 'Average Price', data: avgValues, borderColor: brand, backgroundColor: brand, tension: 0.3, fill: false }] },
			options: { scales: { y: { beginAtZero: false } }, plugins: { legend: { display: false } } }
		});
	</script>
</body>
</html>


