<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// JSON helpers
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// AJAX: fetch children lists
if (($_GET['action'] ?? '') === 'fetch') {
    $level = $_GET['level'] ?? '';
    $mysqli = db();
    if ($level === 'districts') {
        $stateId = (int)($_GET['state_id'] ?? 0);
        $stmt = $mysqli->prepare('SELECT id, name FROM districts WHERE state_id = ? ORDER BY name');
        $stmt && $stmt->bind_param('i', $stateId) && $stmt->execute();
        $res = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        $stmt && $stmt->close();
        jsonResponse($res);
    } elseif ($level === 'cities') {
        $districtId = (int)($_GET['district_id'] ?? 0);
        $stmt = $mysqli->prepare('SELECT id, name FROM cities WHERE district_id = ? ORDER BY name');
        $stmt && $stmt->bind_param('i', $districtId) && $stmt->execute();
        $res = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        $stmt && $stmt->close();
        jsonResponse($res);
    } elseif ($level === 'towns') {
        $cityId = (int)($_GET['city_id'] ?? 0);
        $stmt = $mysqli->prepare('SELECT id, name FROM towns WHERE city_id = ? ORDER BY name');
        $stmt && $stmt->bind_param('i', $cityId) && $stmt->execute();
        $res = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        $stmt && $stmt->close();
        jsonResponse($res);
    }
    jsonResponse([]);
}

// Handle create operations
$message = '';
$message_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = db();
    try {
        $scope = $_POST['scope'] ?? '';
        $name  = trim($_POST['name'] ?? '');
        if ($name === '') { throw new Exception('Name is required'); }

        if ($scope === 'state') {
            $stmt = $mysqli->prepare('INSERT INTO states (name) VALUES (?)');
            $stmt && $stmt->bind_param('s', $name);
        } elseif ($scope === 'district') {
            $stateId = (int)($_POST['state_id'] ?? 0);
            if ($stateId <= 0) { throw new Exception('Select a state'); }
            $stmt = $mysqli->prepare('INSERT INTO districts (state_id, name) VALUES (?, ?)');
            $stmt && $stmt->bind_param('is', $stateId, $name);
        } elseif ($scope === 'city') {
            $districtId = (int)($_POST['district_id'] ?? 0);
            if ($districtId <= 0) { throw new Exception('Select a district'); }
            $stmt = $mysqli->prepare('INSERT INTO cities (district_id, name) VALUES (?, ?)');
            $stmt && $stmt->bind_param('is', $districtId, $name);
        } elseif ($scope === 'town') {
            $cityId = (int)($_POST['city_id'] ?? 0);
            if ($cityId <= 0) { throw new Exception('Select a city'); }
            $stmt = $mysqli->prepare('INSERT INTO towns (city_id, name) VALUES (?, ?)');
            $stmt && $stmt->bind_param('is', $cityId, $name);
        } else {
            throw new Exception('Invalid scope');
        }

        if (!$stmt || !$stmt->execute()) { throw new Exception('Failed to save'); }
        $stmt && $stmt->close();

        $message = 'Saved successfully';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Load initial lists
$mysqli = db();
$states = $mysqli->query('SELECT id, name FROM states ORDER BY name')?->fetch_all(MYSQLI_ASSOC) ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Hierarchy - Big Deal</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        
        body{ background:var(--bg); color:#111827; }

        /* Topbar */

        .small-muted{ color:var(--muted); }
          }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location-hierarchy'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="h4 mb-1 fw-semibold">Location Hierarchy</h2>
                    <p class="text-muted mb-0">Manage states, districts, cities, and towns</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back to Locations</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-xl-3 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="h6 mb-0">States</div>
                            </div>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="scope" value="state">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="name" placeholder="Add state" required>
                                    <button class="btn btn-primary" type="submit">Add</button>
                                </div>
                            </form>
                            <div style="max-height: 340px; overflow:auto;">
                                <ul class="list-group">
                                    <?php foreach ($states as $s): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($s['name']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="h6 mb-2">Districts</div>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="scope" value="district">
                                <div class="mb-2">
                                    <label class="form-label small">State</label>
                                    <select class="form-select" id="stateSelectForDistrict" name="state_id" required>
                                        <option value="">Select state</option>
                                        <?php foreach ($states as $s): ?>
                                            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="name" placeholder="Add district" required>
                                    <button class="btn btn-primary" type="submit">Add</button>
                                </div>
                            </form>
                            <div style="max-height: 340px; overflow:auto;">
                                <ul class="list-group" id="districtList"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="h6 mb-2">Cities</div>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="scope" value="city">
                                <div class="mb-2">
                                    <label class="form-label small">State</label>
                                    <select class="form-select" id="stateSelectForCity" required>
                                        <option value="">Select state</option>
                                        <?php foreach ($states as $s): ?>
                                            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">District</label>
                                    <select class="form-select" id="districtSelectForCity" name="district_id" required>
                                        <option value="">Select district</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="name" placeholder="Add city" required>
                                    <button class="btn btn-primary" type="submit">Add</button>
                                </div>
                            </form>
                            <div style="max-height: 340px; overflow:auto;">
                                <ul class="list-group" id="cityList"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="h6 mb-2">Towns</div>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="scope" value="town">
                                <div class="mb-2">
                                    <label class="form-label small">State</label>
                                    <select class="form-select" id="stateSelectForTown" required>
                                        <option value="">Select state</option>
                                        <?php foreach ($states as $s): ?>
                                            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">District</label>
                                    <select class="form-select" id="districtSelectForTown" required>
                                        <option value="">Select district</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">City</label>
                                    <select class="form-select" id="citySelectForTown" name="city_id" required>
                                        <option value="">Select city</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="name" placeholder="Add town" required>
                                    <button class="btn btn-primary" type="submit">Add</button>
                                </div>
                            </form>
                            <div style="max-height: 340px; overflow:auto;">
                                <ul class="list-group" id="townList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Helpers
        async function fetchJSON(url){ const r = await fetch(url); return r.ok ? r.json() : []; }

        // District column
        const stateSelectForDistrict = document.getElementById('stateSelectForDistrict');
        const districtList = document.getElementById('districtList');
        stateSelectForDistrict && stateSelectForDistrict.addEventListener('change', async function(){
            const sid = this.value || 0;
            const items = await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + sid);
            districtList.innerHTML = items.map(d => `<li class="list-group-item d-flex justify-content-between align-items-center"><span>${d.name}</span></li>`).join('');
        });

        // City column (chained: state -> districts -> list)
        const stateSelectForCity = document.getElementById('stateSelectForCity');
        const districtSelectForCity = document.getElementById('districtSelectForCity');
        const cityList = document.getElementById('cityList');
        stateSelectForCity && stateSelectForCity.addEventListener('change', async function(){
            const sid = this.value || 0;
            const d = await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + sid);
            districtSelectForCity.innerHTML = '<option value="">Select district</option>' + d.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            cityList.innerHTML = '';
        });
        districtSelectForCity && districtSelectForCity.addEventListener('change', async function(){
            const did = this.value || 0;
            const items = await fetchJSON('hierarchy.php?action=fetch&level=cities&district_id=' + did);
            cityList.innerHTML = items.map(c => `<li class="list-group-item d-flex justify-content-between align-items-center"><span>${c.name}</span></li>`).join('');
        });

        // Town column (chained: state -> districts -> cities -> list)
        const stateSelectForTown = document.getElementById('stateSelectForTown');
        const districtSelectForTown = document.getElementById('districtSelectForTown');
        const citySelectForTown = document.getElementById('citySelectForTown');
        const townList = document.getElementById('townList');
        stateSelectForTown && stateSelectForTown.addEventListener('change', async function(){
            const sid = this.value || 0;
            const d = await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + sid);
            districtSelectForTown.innerHTML = '<option value="">Select district</option>' + d.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            citySelectForTown.innerHTML = '<option value="">Select city</option>';
            townList.innerHTML = '';
        });
        districtSelectForTown && districtSelectForTown.addEventListener('change', async function(){
            const did = this.value || 0;
            const c = await fetchJSON('hierarchy.php?action=fetch&level=cities&district_id=' + did);
            citySelectForTown.innerHTML = '<option value="">Select city</option>' + c.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            townList.innerHTML = '';
        });
        citySelectForTown && citySelectForTown.addEventListener('change', async function(){
            const cid = this.value || 0;
            const items = await fetchJSON('hierarchy.php?action=fetch&level=towns&city_id=' + cid);
            townList.innerHTML = items.map(t => `<li class="list-group-item d-flex justify-content-between align-items-center"><span>${t.name}</span></li>`).join('');
        });
    </script>
</body>
</html>

