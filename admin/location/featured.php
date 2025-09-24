<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

$mysqli = db();

// Ensure table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS featured_cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
)");

// JSON list or toggle endpoint
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    $ids = [];
    $rs = $mysqli->query('SELECT city_id FROM featured_cities');
    if ($rs) { while($r = $rs->fetch_assoc()){ $ids[] = (int)$r['city_id']; } }
    echo json_encode([ 'success' => true, 'city_ids' => $ids ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    header('Content-Type: application/json');
    $cityId = (int)($_POST['city_id'] ?? 0);
    if ($cityId <= 0) { echo json_encode([ 'success' => false, 'error' => 'invalid_city' ]); exit; }
    $exists = $mysqli->prepare('SELECT id FROM featured_cities WHERE city_id = ? LIMIT 1');
    $exists && $exists->bind_param('i', $cityId) && $exists->execute();
    $row = $exists ? $exists->get_result()->fetch_assoc() : null;
    $exists && $exists->close();
    if ($row) {
        $del = $mysqli->prepare('DELETE FROM featured_cities WHERE city_id = ?');
        if ($del) { $del->bind_param('i', $cityId); $del->execute(); $del->close(); }
        echo json_encode([ 'success' => true, 'is_featured' => false ]); exit;
    }
    // Enforce max 5
    $cnt = 0; $rc = $mysqli->query('SELECT COUNT(*) AS c FROM featured_cities'); if ($rc && $r=$rc->fetch_assoc()) { $cnt=(int)$r['c']; }
    if ($cnt >= 5) { echo json_encode([ 'success' => false, 'error' => 'limit_reached', 'limit' => 5 ]); exit; }
    $ins = $mysqli->prepare('INSERT INTO featured_cities (city_id) VALUES (?)');
    if ($ins) { $ins->bind_param('i', $cityId); $ok = $ins->execute(); $ins->close(); }
    echo json_encode([ 'success' => true, 'is_featured' => true ]); exit;
}

// Normal page: list featured cities
$res = $mysqli->query("SELECT fc.id, c.id AS city_id, c.name AS city_name, d.name AS district_name, s.name AS state_name
                       FROM featured_cities fc
                       JOIN cities c ON c.id = fc.city_id
                       JOIN districts d ON d.id = c.district_id
                       JOIN states s ON s.id = d.state_id
                       ORDER BY fc.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Locations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{ --primary:#ef4444; --primary-600:#b91c1c; --bg:#F4F7FA; --line:#E0E0E0; --card:#FFFFFF; }
        body{ background:var(--bg); color:#111827; }
        .card{ border:1px solid var(--line); border-radius:12px; }
        /* Red theme for feature toggle like properties */
        .feature-toggle.btn-primary{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
        .feature-toggle.btn-primary:hover{ background-color: var(--primary-600); border-color: var(--primary-600); }
        .feature-toggle.btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
        .feature-toggle.btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
        .feature-toggle i{ pointer-events:none; }
    </style>
<?php /* reuse admin chrome */ ?>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Featured Locations'); ?>
        <div class="container-fluid p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0">Featured Cities (max 5)</h5>
                <a href="index.php#cities" class="btn btn-outline-secondary">Back to Locations</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php if ($res && $res->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>City</th><th>District</th><th>State</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                <?php while($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($row['district_name']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($row['state_name']); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm feature-toggle btn-primary" data-city-id="<?php echo (int)$row['city_id']; ?>" title="Featured">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">No featured cities yet. Go to Cities tab and add up to 5 with the star toggle.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('click', async function(e){
            const btn = e.target.closest('.feature-toggle');
            if (!btn) return;
            const id = btn.getAttribute('data-city-id');
            const form = new FormData(); form.append('action','toggle'); form.append('city_id', id);
            const r = await fetch('featured.php', { method:'POST', body: form });
            const j = await r.json().catch(()=>null);
            if (!j || !j.success){
                if (j && j.error === 'limit_reached') alert('You can only feature up to 5 cities.');
                return;
            }
            // If toggled off from featured page, just reload list
            location.reload();
        });
    </script>
</body>
</html>


