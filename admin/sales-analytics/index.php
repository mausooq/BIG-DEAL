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
	),
	'notifications' => "SELECT COUNT(*) FROM notifications n" . (
		($filters['start_date'] || $filters['end_date']) ?
		' WHERE ' . implode(' AND ', array_filter([
			$filters['start_date'] ? 'n.created_at >= ?' : '',
			$filters['end_date'] ? 'n.created_at <= ?' : ''
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
	'notifications' => fetchCount($kpiSql['notifications'], $enqTypes, $enqParams),
];

// Chart: monthly sold properties (always count Sold regardless of selected status filter)
// Build a WHERE that excludes the status filter to avoid conflicting conditions
$monthlyWhere = [];
$monthlyTypes = '';
$monthlyParams = [];
if ($filters['start_date'] !== '') { $monthlyWhere[] = 'p.created_at >= ?'; $monthlyTypes .= 's'; $monthlyParams[] = $filters['start_date'] . ' 00:00:00'; }
if ($filters['end_date'] !== '') { $monthlyWhere[] = 'p.created_at <= ?'; $monthlyTypes .= 's'; $monthlyParams[] = $filters['end_date'] . ' 23:59:59'; }
if ($filters['listing_type'] !== '') { $monthlyWhere[] = 'p.listing_type = ?'; $monthlyTypes .= 's'; $monthlyParams[] = $filters['listing_type']; }
$monthlyBaseWhere = empty($monthlyWhere) ? ' WHERE 1=1' : ' WHERE ' . implode(' AND ', $monthlyWhere);

$monthlySql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') ym, COUNT(*) cnt
	FROM properties p $monthlyBaseWhere AND p.status = 'Sold'
	GROUP BY ym
	ORDER BY ym ASC";
$stmt = $mysqli->prepare($monthlySql);
if ($stmt && $monthlyTypes !== '') { $stmt->bind_param($monthlyTypes, ...$monthlyParams); }
$stmt && $stmt->execute();
$monthly = $stmt ? $stmt->get_result() : $mysqli->query($monthlySql);
// Map query results
$monthlyMap = [];
while ($r = $monthly->fetch_assoc()) { $monthlyMap[$r['ym']] = (int)$r['cnt']; }

// Build month sequence: use provided date range; else last 12 months including current
$monthlyLabels = [];
$monthlyCounts = [];
try {
    $endDate = null; $startDate = null;
    if ($filters['end_date'] !== '') { $endDate = new DateTime($filters['end_date'] . ' 00:00:00'); $endDate->modify('first day of this month'); }
    if ($filters['start_date'] !== '') { $startDate = new DateTime($filters['start_date'] . ' 00:00:00'); $startDate->modify('first day of this month'); }
    if ($startDate && !$endDate) { $endDate = (clone $startDate); $endDate->modify('+11 months'); }
    if ($endDate && !$startDate) { $startDate = (clone $endDate); $startDate->modify('-11 months'); }
    if (!$startDate || !$endDate) {
        $endDate = new DateTime('first day of this month');
        $startDate = (clone $endDate)->modify('-11 months');
    }
    if ($startDate > $endDate) { $tmp = $startDate; $startDate = $endDate; $endDate = $tmp; }
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

// Chart: city-wise available vs sold (top 10 cities by total) using properties_location.city_id
$citySql = "SELECT COALESCE(ci.name, 'Unknown City') AS city,
    SUM(CASE WHEN p.status='Available' THEN 1 ELSE 0 END) AS avail_cnt,
    SUM(CASE WHEN p.status='Sold' THEN 1 ELSE 0 END) AS sold_cnt
    FROM properties p
    LEFT JOIN properties_location pl ON pl.property_id = p.id
    LEFT JOIN cities ci ON ci.id = pl.city_id
    $baseWhere
    GROUP BY city
    ORDER BY (SUM(CASE WHEN p.status='Available' THEN 1 ELSE 0 END)+SUM(CASE WHEN p.status='Sold' THEN 1 ELSE 0 END)) DESC
    LIMIT 10";
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
		body{ background:var(--bg);color:#111827;}
		/* Cards (match dashboard) */
		.card{ border:0; border-radius:var(--radius); background:var(--card); }
		.card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
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
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
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
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Sales Analytics'); ?>

		<div class="container-fluid p-4">

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
						<div style="color: var(--primary);"><i class="fa-solid fa-circle-check fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">Sold</div><div class="h4 mb-0"><?php echo $kpis['sold']; ?></div></div>
						<div style="color: var(--primary);"><i class="fa-solid fa-sack-dollar fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">New Enquiries</div><div class="h4 mb-0"><?php echo $kpis['enquiries']; ?></div></div>
						<div style="color: var(--primary);"><i class="fa-regular fa-envelope fa-lg"></i></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat"><div class="card-body d-flex align-items-center justify-content-between">
						<div><div class="text-muted small">Notifications</div><div class="h4 mb-0"><?php echo $kpis['notifications']; ?></div></div>
						<div style="color: var(--primary);"><i class="fa-solid fa-bell fa-lg"></i></div>
					</div></div>
				</div>
			</div>



			<div class="row g-4">
				<div class="col-xl-6">	
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Monthly Sales Trend</div>
						<div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
					</div></div>
				</div>
				<div class="col-xl-6">
					<div class="card"><div class="card-body">
						<div class="h6 mb-3">Top Performing Cities</div>
						<div class="chart-wrap"><canvas id="chartCity"></canvas></div>
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

		const brand = 'rgb(0, 0, 0)';
		const brand600 = 'rgb(0, 0, 0)';

		const gridColor = 'rgba(17,24,39,0.06)';
		const ticksColor = '#6b7280';
		const titleFont = { size: 13, weight: '600' };
		
		// Compute a dynamic Y max rounded to next even number (min 2)
		const monthlyMax = (monthlyCounts && monthlyCounts.length) ? Math.max(...monthlyCounts) : 0;
		const yMaxEven = Math.max(2, Math.ceil(monthlyMax / 2) * 2);

		new Chart(document.getElementById('chartMonthly'), {
			type: 'line',
			data: { 
				labels: monthlyLabels, 
				datasets: [{ 
					label: 'Monthly Sales', 
					data: monthlyCounts, 
					borderColor: brand, 
					backgroundColor: 'rgba(0, 0, 0, 0.08)', 
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
					y: { beginAtZero: true, suggestedMax: yMaxEven, grid: { color: gridColor }, ticks: { color: ticksColor, stepSize: 2, precision: 0 } } 
				}, 
				plugins: { legend: { display: false } } 
			}
		});
		
		// Compute dynamic even Y max for city chart (based on max of both datasets)
		const cityMax = Math.max(0, ...((cityAvail||[])), ...((citySold||[])));
		const cityYMaxEven = Math.max(2, Math.ceil(cityMax / 2) * 2);
		new Chart(document.getElementById('chartCity'), {
			type: 'bar',
			data: { labels: cityLabels, datasets: [
				{ label: 'Available', data: cityAvail, backgroundColor: 'rgba(0, 0, 0, 0.25)', borderRadius: 6, maxBarThickness: 28 },
				{ label: 'Sold', data: citySold, backgroundColor: 'rgb(0, 0, 0)', borderRadius: 6, maxBarThickness: 28 }
			] },
			options: { 
				maintainAspectRatio: false,
				responsive: true, 
				scales: { 
					x: { grid: { color: gridColor }, ticks: { color: ticksColor } }, 
					y: { beginAtZero: true, suggestedMax: cityYMaxEven, grid: { color: gridColor }, ticks: { color: ticksColor, stepSize: 2, precision: 0 } } 
				}, 
				plugins: { legend: { position: 'bottom' } } 
			}
		});
		
		// Compute dynamic even Y max for stacked category chart (sum per category)
		const catStackMax = (catAvail||[]).reduce((maxVal, v, i) => {
			const total = (parseInt(v||0,10)) + (parseInt((catSold||[])[i]||0,10));
			return Math.max(maxVal, total);
		}, 0);
		const catYMaxEven = Math.max(2, Math.ceil(catStackMax / 2) * 2);
		new Chart(document.getElementById('chartCategory'), {
			type: 'bar',
			data: {
				labels: catLabels,
				datasets: [
					{ label: 'Sold', data: catSold, backgroundColor: 'rgb(0, 0, 0)', borderRadius: 0, maxBarThickness: 32, stack: 'stack1' },
					{ label: 'Available', data: catAvail, backgroundColor: 'rgba(0, 0, 0, 0.25)', borderRadius: 0, maxBarThickness: 32, stack: 'stack1' }
				]
			},
			options: {
				responsive: true,
				scales: {
					x: { grid: { color: gridColor }, ticks: { color: ticksColor }, stacked: true },
					y: { beginAtZero: true, suggestedMax: catYMaxEven, grid: { color: gridColor }, ticks: { color: ticksColor, stepSize: 2, precision: 0 }, stacked: true }
				},
				plugins: { legend: { position: 'bottom' } },
				categoryPercentage: 0.6,
				barPercentage: 0.8
			}
		});

		// Handle Enter key press in form inputs
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('form');
			const inputs = form.querySelectorAll('input, select');
			
			inputs.forEach(input => {
				input.addEventListener('keypress', function(e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						form.submit();
					}
				});
			});
		});
	</script>
</body>
</html>