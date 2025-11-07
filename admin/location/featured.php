<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

$mysqli = db();

// API: list, toggle, reorder
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
	header('Content-Type: application/json');
	$res = $mysqli->query("SELECT city_id FROM featured_cities ORDER BY order_id, id");
	$ids = [];
	if ($res) { while($r = $res->fetch_assoc()){ $ids[] = (int)$r['city_id']; } }
	echo json_encode([ 'success' => true, 'city_ids' => $ids ]);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json');
	$action = $_POST['action'] ?? '';
	if ($action === 'toggle') {
		$cityId = (int)($_POST['city_id'] ?? 0);
		if ($cityId <= 0) { echo json_encode(['success'=>false, 'error'=>'bad_id']); exit; }
		// is featured already?
		$exists = $mysqli->query("SELECT id FROM featured_cities WHERE city_id=".$cityId." LIMIT 1");
		$row = $exists ? $exists->fetch_assoc() : null;
		if ($row) {
			$mysqli->query("DELETE FROM featured_cities WHERE id=".(int)$row['id']);
			echo json_encode(['success'=>true, 'is_featured'=>false]);
			exit;
		}
		// enforce max 5
		$cnt = 0; $c = $mysqli->query("SELECT COUNT(*) c FROM featured_cities"); if ($c && $cr=$c->fetch_assoc()){ $cnt=(int)$cr['c']; }
		if ($cnt >= 5) { echo json_encode(['success'=>false, 'error'=>'limit_reached']); exit; }
		// determine next order
		$ord = 1; $o=$mysqli->query("SELECT COALESCE(MAX(order_id),0)+1 o FROM featured_cities"); if ($o && $or=$o->fetch_assoc()){ $ord=(int)$or['o']; }
		$stmt = $mysqli->prepare("INSERT INTO featured_cities(city_id, order_id) VALUES (?, ?)");
		if ($stmt) { $stmt->bind_param('ii', $cityId, $ord); $stmt->execute(); $stmt->close(); }
		echo json_encode(['success'=>true, 'is_featured'=>true]);
		exit;
	} elseif ($action === 'reorder') {
		// expects order map: order[city_id]=order_id
		$order = $_POST['order'] ?? [];
		if (!is_array($order)) { echo json_encode(['success'=>false]); exit; }
		$upd = $mysqli->prepare('UPDATE featured_cities SET order_id=? WHERE city_id=?');
		if ($upd) {
			foreach ($order as $cid => $ord) {
				$cid = (int)$cid; $ord = (int)$ord; if ($cid>0 && $ord>0) { $upd->bind_param('ii', $ord, $cid); $upd->execute(); }
			}
			$upd->close();
		}
		echo json_encode(['success'=>true]);
		exit;
	}
	echo json_encode(['success'=>false, 'error'=>'bad_action']);
	exit;
}

// Page
$rows = $mysqli->query("SELECT fc.city_id, fc.order_id, c.name, d.name AS district, s.name AS state
	FROM featured_cities fc
	JOIN cities c ON c.id = fc.city_id
	JOIN districts d ON d.id = c.district_id
	JOIN states s ON s.id = d.state_id
	ORDER BY fc.order_id, fc.id");
$list = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Locations</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ef4444; /* red */
            --primary-600: #b91c1c;
            --bg: #F4F7FA;
            --card: #FFFFFF;
            --line: #E0E0E0;
            --muted: #6b7280;
        }
        body{ background:var(--bg); color:#111827; }
        .card{ border:0; border-radius:12px; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .page-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
        .hint{ color: var(--muted); font-size:.9rem; }
        /* Table - match admin UI */
        .table-wrap{ border:0; border-radius:12px; overflow:hidden; background:#fff; }
        .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600; }
        .table-inner td, .table-inner th{ border-left:0; border-right:0; }
        .table-inner tbody td{ border-top:1px solid var(--line) !important; vertical-align: middle; }
        .table-inner tbody tr:hover{ background:#f9fafb; }
        .drag-row{ cursor:grab; }
        /* Buttons */
        .btn-primary{ background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover{ background: var(--primary-600); border-color: var(--primary-600); }
        .badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        /* Featured star button (match property drawer/location cards) */
        .btn-feature-city{ width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; padding:0; position:relative; overflow:hidden; }
        .btn-feature-city.btn-primary{ background:var(--primary); border-color:var(--primary); color:#fff; }
        .btn-feature-city.btn-outline-primary{ border-color:var(--primary); color:var(--primary); background:#fff; }
        .btn-feature-city.btn-outline-primary i{ color: var(--primary); }
        .btn-feature-city.btn-primary i{ color: #fff; }
        .btn-feature-city::before, .btn-feature-city::after{ content: none !important; }
    </style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Featured Locations'); ?>
        <div class="container-fluid p-3">
            
            <div class="table-wrap">
                <table class="table table-inner align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>City</th>
                            <th>District</th>
                            <th>State</th>
                            <th style="width:110px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="featTable">
						<?php foreach($list as $i => $row): ?>
							<tr class="drag-row" data-city-id="<?php echo (int)$row['city_id']; ?>">
								<td class="text-muted"><?php echo (int)$row['order_id']; ?></td>
								<td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
								<td class="text-muted"><?php echo htmlspecialchars($row['district']); ?></td>
								<td class="text-muted"><?php echo htmlspecialchars($row['state']); ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary btn-feature-city" title="Unstar (remove from featured)" aria-label="Unstar" data-city-id="<?php echo (int)$row['city_id']; ?>">
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                </td>
							</tr>
						<?php endforeach; ?>
                    </tbody>
                </table>
			</div>
            <div class="mt-3 d-flex justify-content-end">
                <button id="saveOrder" class="btn btn-primary">Save Order</button>
            </div>
		</div>
	</div>

    <!-- Order Saved Modal -->
    <div class="modal fade" id="orderSavedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px; border:1px solid var(--line);">
                <div class="modal-header" style="border-bottom:1px solid var(--line);">
                    <h5 class="modal-title" style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-circle-check" style="color: var(--primary);"></i>
                        Order Saved
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Your featured cities order has been updated successfully.
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--line);">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	(function(){
		const tbody = document.getElementById('featTable');
		if (!tbody) return;
		let dragSrc = null;
		function handleDragStart(){ dragSrc = this; this.style.opacity = '0.6'; }
		function handleDragOver(e){ if (e.preventDefault) e.preventDefault(); return false; }
		function handleDrop(e){ if (e.stopPropagation) e.stopPropagation(); if (dragSrc && dragSrc !== this){ const nodes=[...tbody.children]; const s=nodes.indexOf(dragSrc), t=nodes.indexOf(this); if (s>-1&&t>-1){ if (s<t) tbody.insertBefore(dragSrc,this.nextSibling); else tbody.insertBefore(dragSrc,this); } } return false; }
		function handleDragEnd(){ this.style.opacity = '1'; }
		[...tbody.children].forEach(tr=>{ tr.setAttribute('draggable','true'); tr.addEventListener('dragstart',handleDragStart,false); tr.addEventListener('dragover',handleDragOver,false); tr.addEventListener('drop',handleDrop,false); tr.addEventListener('dragend',handleDragEnd,false); });
		const save = document.getElementById('saveOrder');
		save?.addEventListener('click', async function(){
			const map = {}; [...tbody.children].forEach((tr,idx)=>{ const id=tr.getAttribute('data-city-id'); if (id) map[id]=idx+1; });
			const form = new FormData(); form.append('action','reorder'); Object.entries(map).forEach(([k,v])=> form.append('order['+k+']', v));
			const r = await fetch('featured.php', { method:'POST', body: form }); const j = await r.json().catch(()=>null);
            if (!j || !j.success) { alert('Failed to save order'); return; }
			// refresh numbers
			[...tbody.children].forEach((tr,idx)=>{ tr.children[0].textContent = String(idx+1); });
			try{ const modal = new bootstrap.Modal(document.getElementById('orderSavedModal')); modal.show(); }catch(e){ /* fallback */ alert('Order saved'); }
		});

        // Toggle star (unstar when listed here). If toggled off, remove the row.
        async function toggleFeaturedCity(cityId){
            try{
                const form = new FormData();
                form.append('action','toggle');
                form.append('city_id', String(cityId));
                const res = await fetch('featured.php', { method:'POST', body: form, headers:{ 'Accept':'application/json' } });
                const json = await res.json();
                if (json && json.success){
                    const row = tbody.querySelector('tr[data-city-id="'+cityId+'"]');
                    if (row && json.is_featured === false){ row.remove(); }
                } else if (json && json.error === 'limit_reached'){
                    // Show themed modal if present in parent pages
                    let modal = document.getElementById('featuredLimitModal');
                    if (!modal){
                        // create lightweight modal inline to match theme
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = '\n<div class="modal fade" id="featuredLimitModal" tabindex="-1">\n  <div class="modal-dialog modal-dialog-centered">\n    <div class="modal-content" style="border-radius:12px; border:1px solid var(--line);">\n      <div class="modal-header" style="border-bottom:1px solid var(--line);">\n        <h5 class="modal-title" style="display:flex; align-items:center; gap:8px;">\n          <i class="fa-solid fa-triangle-exclamation" style="color: var(--primary);"></i> Featured Limit Reached\n        </h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">You can feature up to <span class="badge badge-soft">5</span> cities. Remove one to add another.</div>\n      <div class="modal-footer" style="border-top:1px solid var(--line);">\n        <a href="featured.php" class="btn btn-primary"><i class="fa-solid fa-star me-1"></i>Manage Featured</a>\n        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>\n      </div>\n    </div>\n  </div>\n</div>';
                        document.body.appendChild(wrapper.firstChild);
                    }
                    modal = document.getElementById('featuredLimitModal');
                    if (modal){ const m = new bootstrap.Modal(modal); m.show(); }
                }
            } catch(e){ /* noop */ }
        }

        tbody.addEventListener('click', function(e){
            const btn = e.target.closest('.btn-feature-city');
            if (!btn) return;
            e.preventDefault();
            const cid = parseInt(btn.getAttribute('data-city-id'));
            if (cid>0){ toggleFeaturedCity(cid); }
        });
	})();
	</script>
</body>
</html>
