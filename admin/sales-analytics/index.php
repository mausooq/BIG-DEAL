<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Helper: check if a table column exists (to avoid errors when migrations not applied)
function tableColumnExists(mysqli $mysqli, string $tableName, string $columnName): bool {
    $res = $mysqli->query("SELECT DATABASE()");
    $row = $res ? $res->fetch_row() : [null];
    $dbName = $row[0] ?? null;
    if (!$dbName) { return false; }
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) { return false; }
    $stmt->bind_param('sss', $dbName, $tableName, $columnName);
    $stmt->execute();
    $cntRow = $stmt->get_result()->fetch_row();
    return ((int)($cntRow[0] ?? 0)) > 0;
}

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

// Enquiry params only include date range
$enqTypes = '';
$enqParams = [];
if ($filters['start_date'] !== '') { $enqTypes .= 's'; $enqParams[] = $filters['start_date'] . ' 00:00:00'; }
if ($filters['end_date'] !== '') { $enqTypes .= 's'; $enqParams[] = $filters['end_date'] . ' 23:59:59'; }

$kpis = [
	'available' => fetchCount($kpiSql['total_available'], $kpiTypesAvail, $kpiParamsAvail),
	'sold' => fetchCount($kpiSql['total_sold'], $kpiTypesSold, $kpiParamsSold),
	'enquiries' => fetchCount($kpiSql['new_enquiries'], $enqTypes, $enqParams),
];

// Chart: monthly sold properties (last 12 months within filter)
$monthlySql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') ym, COUNT(*) cnt
	FROM properties p $baseWhere AND p.status = 'Sold'
	GROUP BY ym
	ORDER BY ym ASC";
$stmt = $mysqli->prepare($monthlySql);
if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$monthly = $stmt ? $stmt->get_result() : $mysqli->query($monthlySql);
$monthlyLabels = [];
$monthlyCounts = [];
while ($r = $monthly->fetch_assoc()) { $monthlyLabels[] = $r['ym']; $monthlyCounts[] = (int)$r['cnt']; }

// Chart: category-wise available vs sold
$catSql = "SELECT COALESCE(c.name,'Uncategorized') cat,
	SUM(CASE WHEN p.status='Available' THEN 1 ELSE 0 END) avail_cnt,
	SUM(CASE WHEN p.status='Sold' THEN 1 ELSE 0 END) sold_cnt
	FROM properties p
	LEFT JOIN categories c ON c.id = p.category_id
	$baseWhere
	GROUP BY cat
	ORDER BY cat ASC";
$stmt2 = $mysqli->prepare($catSql);
if ($stmt2 && $types !== '') { $stmt2->bind_param($types, ...$params); }
$stmt2 && $stmt2->execute();
$cat = $stmt2 ? $stmt2->get_result() : $mysqli->query($catSql);
$catLabels = [];
$catAvail = [];
$catSold = [];
while ($r = $cat->fetch_assoc()) { $catLabels[] = $r['cat']; $catAvail[] = (int)$r['avail_cnt']; $catSold[] = (int)$r['sold_cnt']; }

// Chart: city-wise available vs sold (top 10 cities by total)
$useHierarchy = tableColumnExists($mysqli, 'properties_location', 'city_id') && tableColumnExists($mysqli, 'cities', 'name');
$cityJoin = $useHierarchy ? " LEFT JOIN properties_location pl ON pl.property_id = p.id LEFT JOIN cities ci ON ci.id = pl.city_id " : "";
$cityExpr = $useHierarchy ? "COALESCE(ci.name, p.location)" : "p.location";
$citySql = "SELECT city, SUM(avail_cnt) avail_cnt, SUM(sold_cnt) sold_cnt FROM (
    SELECT " . $cityExpr . " AS city,
           CASE WHEN p.status='Available' THEN 1 ELSE 0 END AS avail_cnt,
           CASE WHEN p.status='Sold' THEN 1 ELSE 0 END AS sold_cnt
    FROM properties p" . $cityJoin . "
    $baseWhere
) t GROUP BY city ORDER BY (SUM(avail_cnt)+SUM(sold_cnt)) DESC LIMIT 10";
$stmt3 = $mysqli->prepare($citySql);
if ($stmt3 && $types !== '') { $stmt3->bind_param($types, ...$params); }
$stmt3 && $stmt3->execute();
$cityRes = $stmt3 ? $stmt3->get_result() : $mysqli->query($citySql);
$cityLabels = [];
$cityAvail = [];
$citySold = [];
while ($r = $cityRes->fetch_assoc()) { $cityLabels[] = $r['city']; $cityAvail[] = (int)$r['avail_cnt']; $citySold[] = (int)$r['sold_cnt']; }

// Done datasets
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
			--line:#e9eef5;/* neutral borders (dashboard) */
			--brand-dark:#2f2f2f;/* logo dark */
			--primary:#e11d2a;/* brand red */
			--primary-600:#b91c1c;/* darker red hover */
			--primary-50:#fff1f1;/* soft card bg */
			--radius:16px;
		}
		body{ background:var(--bg);color:#111827;}
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
		/* Override sticky behavior for this page */
		.navbar.sticky-top{ position:static; }
		.text-primary{ color:var(--primary)!important; }
		/* Cards */
		.card{border-radius:24px; background:var(--card); box-shadow:0 10px 20px rgba(0,0,0,.05); }
		.card-stat{box-shadow:0 10px 24px rgba(0,0,0,.08); border-radius:24px; }
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Charts */
		.chart-wrap{ position:relative; width:100%; height:360px; overflow:hidden; }
		.chart-wrap canvas{ width:100% !important; height:100% !important; display:block; }
		@media (max-width: 575.98px){ .chart-wrap{ height:200px; } }
		/* Modern toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:24px; padding:16px; display:flex; flex-direction:column; gap:10px; box-shadow:0 8px 20px rgba(0,0,0,.05); position:relative; overflow:hidden; }
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
		/* Drawer: not used on this page */
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
				<div class="h5 mb-0">Sales Dashboard</div>
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
							<?php foreach (['Available','Sold'] as $st): ?>
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
						<div><div class="text-muted small">New Enquiries</div><div class="h4 mb-0"><?php echo $kpis['enquiries']; ?></div></div>
						<div class="text-warning"><i class="fa-regular fa-envelope fa-lg"></i></div>
					</div></div>
				</div>
			</div>



			<div class="row g-4">
				<div class="col-xl-8">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Monthly Sales Trend</div>
						<div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
					</div></div>
				</div>
				<div class="col-xl-4">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Top Performing Cities</div>
						<canvas id="chartCity" height="180"></canvas>
					</div></div>
				</div>
			</div>

			<div class="row g-4 mt-1">
				<div class="col-12">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Category Performance Analysis</div>
						<canvas id="chartCategory" height="140"></canvas>
					</div></div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	<script>
		// Baseline plugin to draw a subtle line under the bars (match reference)
		const baselinePlugin = {
			id: 'baseline',
			afterDatasetsDraw(chart) {
				const { ctx, chartArea } = chart;
				if (!chartArea) return;
				ctx.save();
				ctx.strokeStyle = '#9ca3af33';
				ctx.lineWidth = 2;
				ctx.beginPath();
				ctx.moveTo(chartArea.left, chartArea.bottom - 1);
				ctx.lineTo(chartArea.right, chartArea.bottom - 1);
				ctx.stroke();
				ctx.restore();
			}
		};
		Chart.register(baselinePlugin);
		const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
		const monthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
		const catLabels = <?php echo json_encode($catLabels); ?>;
		const catAvail = <?php echo json_encode($catAvail); ?>;
		const catSold = <?php echo json_encode($catSold); ?>;
		const cityLabels = <?php echo json_encode($cityLabels); ?>;
		const cityAvail = <?php echo json_encode($cityAvail); ?>;
		const citySold = <?php echo json_encode($citySold); ?>;

		const brand = '#e11d2a';
		const brand600 = '#b91c1c';

		const gridColor = 'rgba(17,24,39,0.06)';
		const ticksColor = '#6b7280';
		const titleFont = { size: 13, weight: '600' };
		
		new Chart(document.getElementById('chartMonthly'), {
			type: 'line',
			data: { 
				labels: monthlyLabels, 
				datasets: [{ 
					label: 'Monthly Sales', 
					data: monthlyCounts, 
					borderColor: brand, 
					backgroundColor: 'rgba(225,29,72,0.08)', 
					tension: 0.35, 
					fill: true, 
					borderWidth: 2, 
					pointRadius: 0, 
					pointHoverRadius: 3
				}]
			},
			options: { 
				maintainAspectRatio: false,
				responsive: true,
				scales: { 
					x: { grid: { color: gridColor }, ticks: { color: ticksColor } }, 
					y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: ticksColor } } 
				}, 
				plugins: { legend: { display: false } } 
			}
		});
		
		new Chart(document.getElementById('chartCity'), {
			type: 'bar',
			data: { labels: cityLabels, datasets: [
				{ label: 'Available', data: cityAvail, backgroundColor: '#16a34a', borderRadius: 6, maxBarThickness: 28 },
				{ label: 'Sold', data: citySold, backgroundColor: '#ef4444', borderRadius: 6, maxBarThickness: 28 }
			] },
			options: { responsive: true, scales: { x: { grid: { color: gridColor }, ticks: { color: ticksColor } }, y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: ticksColor } } }, plugins: { legend: { position: 'bottom' } } }
		});
		
		new Chart(document.getElementById('chartCategory'), {
			type: 'bar',
			data: {
				labels: catLabels,
				datasets: [
					{ label: 'Sold', data: catSold, backgroundColor: '#dc2626', borderRadius: 0, maxBarThickness: 32, stack: 'stack1' },
					{ label: 'Available', data: catAvail, backgroundColor: 'rgba(220,38,38,0.25)', borderRadius: 0, maxBarThickness: 32, stack: 'stack1' }
				]
			},
			options: {
				responsive: true,
				scales: {
					x: { grid: { color: gridColor }, ticks: { color: ticksColor }, stacked: true },
					y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: ticksColor }, stacked: true }
				},
				plugins: { legend: { position: 'bottom' } },
				categoryPercentage: 0.6,
				barPercentage: 0.8
			}
		});
	</script>
</body>
</html>