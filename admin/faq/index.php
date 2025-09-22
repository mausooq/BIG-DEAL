<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Helper: activity logging
function logActivity(mysqli $mysqli, string $action, string $details): void {
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    if ($admin_id === null) {
        $stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (NULL, ?, ?, NOW())");
        $stmt && $stmt->bind_param('ss', $action, $details);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt && $stmt->bind_param('iss', $admin_id, $action, $details);
    }
    $stmt && $stmt->execute();
    $stmt && $stmt->close();
}

// Handle AJAX reorder request first
if (isset($_POST['action']) && $_POST['action'] === 'reorder_faqs') {
	header('Content-Type: application/json');
	$faq_orders_json = $_POST['faq_orders'] ?? '[]';
	$faq_orders = json_decode($faq_orders_json, true) ?? [];
	$mysqli = db();
	
	try {
		$mysqli->begin_transaction();
		
		foreach ($faq_orders as $order_data) {
			$id = (int)$order_data['id'];
			$order_id = (int)$order_data['order_id'];
			
			$stmt = $mysqli->prepare('UPDATE faqs SET order_id = ? WHERE id = ?');
			$stmt->bind_param('ii', $order_id, $id);
			$stmt->execute();
			$stmt->close();
		}
		
		$mysqli->commit();
		logActivity($mysqli, 'Reordered FAQs', 'Updated FAQ order sequence');
		echo json_encode(['success' => true]);
	} catch (Exception $e) {
		$mysqli->rollback();
		echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	}
	exit;
}

// Handle FAQ operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_faq') {
		$question = trim($_POST['question'] ?? '');
		$answer = trim($_POST['answer'] ?? '');
		
		if ($question && $answer) {
			// Start transaction to ensure data consistency
			$mysqli->begin_transaction();
			
			try {
				// Shift all existing FAQs down by 1
				$mysqli->query('UPDATE faqs SET order_id = order_id + 1');
				
				// Insert new FAQ at the top (order_id = 1)
				$stmt = $mysqli->prepare('INSERT INTO faqs (question, answer, order_id) VALUES (?, ?, 1)');
				if ($stmt) {
					$stmt->bind_param('ss', $question, $answer);
					if ($stmt->execute()) {
						$_SESSION['success_message'] = 'FAQ added successfully!';
						logActivity($mysqli, 'Added FAQ', 'Question: ' . substr($question, 0, 50) . '...');
						$mysqli->commit();
					} else {
						$_SESSION['error_message'] = 'Failed to add FAQ: ' . $mysqli->error;
						$mysqli->rollback();
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
					$mysqli->rollback();
				}
			} catch (Exception $e) {
				$mysqli->rollback();
				$_SESSION['error_message'] = 'Error adding FAQ: ' . $e->getMessage();
			}
		} else {
			$_SESSION['error_message'] = 'Question and answer are required.';
		}
	} elseif ($action === 'edit_faq') {
		$id = (int)($_POST['id'] ?? 0);
		$question = trim($_POST['question'] ?? '');
		$answer = trim($_POST['answer'] ?? '');
		
		if ($id && $question && $answer) {
			$stmt = $mysqli->prepare('UPDATE faqs SET question = ?, answer = ? WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('ssi', $question, $answer, $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'FAQ updated successfully!';
						logActivity($mysqli, 'Updated FAQ', 'Question: ' . substr($question, 0, 50) . '..., ID: ' . $id);
					} else {
						$_SESSION['error_message'] = 'No changes made or FAQ not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to update FAQ: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Question and answer are required.';
		}
	} elseif ($action === 'delete_faq') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			// Get question for logging
			$get_stmt = $mysqli->prepare('SELECT question FROM faqs WHERE id = ?');
			$get_stmt->bind_param('i', $id);
			$get_stmt->execute();
			$faq = $get_stmt->get_result()->fetch_assoc();
			$get_stmt->close();
			
			$stmt = $mysqli->prepare('DELETE FROM faqs WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'FAQ deleted successfully!';
						logActivity($mysqli, 'Deleted FAQ', 'Question: ' . substr($faq['question'] ?? 'Unknown', 0, 50) . '..., ID: ' . $id);
					} else {
						$_SESSION['error_message'] = 'FAQ not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete FAQ: ' . $mysqli->error;
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

// Get FAQs with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE question LIKE ? OR answer LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = 'SELECT id, question, answer, order_id FROM faqs' . $whereClause . ' ORDER BY order_id ASC, id ASC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$faqs = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, question, answer FROM faqs ORDER BY id DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM faqs' . $whereClause;
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

// Recent FAQs
$recentFaqs = $mysqli->query("SELECT question, answer FROM faqs ORDER BY order_id ASC, id ASC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FAQs - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		/* Cards */
		
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; border-collapse:collapse; margin-bottom:0; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
		/* Use row-level separators to avoid tiny gaps between cells */
		.table tbody td{ border-top:0; border-bottom:0; }
		.table tbody tr{ box-shadow: inset 0 1px 0 var(--line); }
		.table tbody tr:last-child{ box-shadow: inset 0 1px 0 var(--line), inset 0 -1px 0 var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.table thead th.actions-cell{ text-align:center; padding-right:99px; }
		.table tbody td.actions-cell{ display:flex; gap:8px; justify-content:flex-end; align-items:center; white-space:nowrap; overflow:hidden; padding-right:8px; }
		.table tbody td.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; flex-shrink:0; }
		.actions-cell .btn:disabled{ opacity:0.5; cursor:not-allowed; }
		/* FAQ content styling */
		.faq-question{ font-weight:600; color:#111827; margin-bottom:4px; }
		.faq-answer{ color:var(--muted); font-size:0.9rem; line-height:1.4; }
		.faq-preview{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
		.faq-question-preview{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
		.faq-answer-preview{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
		/* Drag and drop styling */
		.drag-handle{ cursor:grab; color:var(--muted); padding:8px; border-radius:6px; transition:all 0.2s ease; }
		.drag-handle:hover{ background:#f3f4f6; color:var(--primary); }
		.drag-handle:active{ cursor:grabbing; }
		.table tbody tr.dragging{ opacity:0.5; background:#f8f9fa; }
		.table tbody tr.drag-over{ border-top:2px solid var(--primary); }
		.order-cell{ width:60px; text-align:center; }
		.question-cell{ width:30%; }
		.answer-cell{ width:45%; }
		.actions-cell{ width:150px; }
		#faqsTable{ width:100%; table-layout:fixed; }
		/* Badges */
		.badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Modern Animated Action Buttons (match Properties) */
		.modern-btn { width:36px; height:36px; border:none; border-radius:12px; cursor:pointer; position:relative; overflow:hidden; transition: all .4s cubic-bezier(0.175,0.885,0.32,1.275); backdrop-filter: blur(10px); box-shadow: 0 4px 16px rgba(0,0,0,.15), inset 0 1px 0 rgba(255,255,255,.2); display:inline-flex; align-items:center; justify-content:center; font-size:14px; margin:0 2px; }
		.modern-btn::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent); transition:left .6s; }
		.modern-btn:hover::before { left:100%; }
		.modern-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 24px rgba(0,0,0,.2), inset 0 1px 0 rgba(255,255,255,.3); filter: drop-shadow(0 0 12px rgba(255,255,255,.3)); }
		.modern-btn:active { transform: translateY(-1px) scale(1.02); transition: all .1s ease; }
		.modern-btn .icon { transition: all .3s ease; }
		.modern-btn:hover .icon { transform: scale(1.2) rotate(5deg); }
		.view-btn, .edit-btn { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
		.view-btn:hover, .edit-btn:hover { background:#e5e7eb; border-color:#d1d5db; color:#374151; }
		.delete-btn { background: var(--primary); color:#fff; border:1px solid var(--primary-600); }
		.delete-btn:hover { background: var(--primary-600); border-color: var(--primary-600); color:#fff; }
		.ripple { position:absolute; border-radius:50%; background: rgba(255,255,255,.4); transform: scale(0); animation: ripple-animation .6s linear; pointer-events:none; }
		@keyframes ripple-animation { to { transform: scale(4); opacity: 0; } }
		/* Mobile responsiveness */

			.table{ font-size:.9rem; }
		}
		@media (max-width: 575.98px){
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.actions-cell{ justify-content:center; }
			.table thead th:last-child, .table tbody td:last-child{ text-align:center; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('faq'); ?>
	<div class="content">
	<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'FAQs'); ?>

		<div class="container-fluid p-4">
			<!-- PHP Messages -->
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
			
			<!-- JavaScript Messages Container -->
			<div id="jsAlertContainer"></div>

			<!-- Search toolbar -->
			<div class="toolbar mb-4">
				<div class="row-top">
					<form class="d-flex flex-grow-1" method="get">
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search FAQs by question or answer">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addFaqModal">
						<span class="text">Add FAQ</span>
						<span class="icon">
							<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
							<span class="buttonSpan">+</span>
						</span>
					</button>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-12">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">FAQs</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div>
								<table class="table align-middle" id="faqsTable">
									<thead>
										<tr>
											<th class="order-cell">Order</th>
											<th class="question-cell">Question</th>
											<th class="answer-cell">Answer</th>
											<th class="actions-cell">Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $faqs->fetch_assoc()): ?>
										<tr data-faq='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>' data-order="<?php echo $row['order_id']; ?>">
											<td class="order-cell">
												<div class="drag-handle" title="Drag to reorder">
													<i class="fa-solid fa-grip-vertical"></i>
												</div>
											</td>
											<td class="question-cell">
												<div class="faq-question faq-question-preview" title="<?php echo htmlspecialchars($row['question']); ?>">
													<?php echo htmlspecialchars($row['question']); ?>
												</div>
											</td>
											<td class="answer-cell">
												<div class="faq-answer faq-answer-preview" title="<?php echo htmlspecialchars($row['answer']); ?>">
													<?php echo htmlspecialchars($row['answer']); ?>
												</div>
											</td>
											<td class="text-end actions-cell">
												<button class="modern-btn edit-btn btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editFaqModal" title="Edit FAQ"><span class="icon"><i class="fa-solid fa-pen"></i></span></button>
												<button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete FAQ"><span class="icon"><i class="fa-solid fa-trash"></i></span></button>
											</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>

							<?php if ($totalPages > 1): ?>
							<nav aria-label="FAQs pagination">
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

				<!-- <div class="col-xl-4">
					<div class="card h-100 sticky-side">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<div class="h6 mb-0">Recent FAQs</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($f = $recentFaqs->fetch_assoc()): ?>
								<div class="item-row" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
									<div class="faq-question" style="font-size:0.9rem; margin-bottom:4px;">
										<?php echo htmlspecialchars(substr($f['question'], 0, 60) . (strlen($f['question']) > 60 ? '...' : '')); ?>
									</div>
									<div class="faq-answer" style="font-size:0.8rem;">
										<?php echo htmlspecialchars(substr($f['answer'], 0, 20) . (strlen($f['answer']) > 20 ? '...' : '')); ?>
									</div>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div> -->
			</div>
		</div>
	</div>

    <style>
    /* Center modals and blur backdrop for consistency */
    .modal-dialog.modal-dialog-centered { display:flex; align-items:center; min-height:calc(100% - 3.5rem); }
    .modal-backdrop.show { background: rgba(0,0,0,.4); backdrop-filter: blur(3px); }
    .modal-content { border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); border:1px solid rgba(255,255,255,0.1); }
    .btn.btn-secondary { background:#fff; color:#111827; border:1px solid #E0E0E0; }
    .btn.btn-secondary:hover { background:#f5f5f5; }
    /* Match blogs add/edit form controls */
    :root { --border-color:#E0E0E0; --label-color:#555; }
    .modal-body label.form-label { font-size:.9rem; color:var(--label-color); font-weight:500; margin-bottom:6px; }
    .modal-body input[type="text"], .modal-body textarea, .modal-body select {
        width:100%; padding:.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:1rem; box-sizing:border-box;
    }
    .modal-footer { gap:.5rem; }
    </style>

    <!-- Add FAQ Modal -->
	<div class="modal fade" id="addFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add New FAQ</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_faq">
						<div class="mb-3">
							<label class="form-label">Question</label>
							<textarea class="form-control" name="question" rows="3" placeholder="Enter the frequently asked question..." required></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Answer</label>
							<textarea class="form-control" name="answer" rows="5" placeholder="Enter the detailed answer..." required></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Add FAQ</span>
							<span class="icon">
								<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
								</svg>
							</span>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

    <!-- Edit FAQ Modal -->
	<div class="modal fade" id="editFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit FAQ</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_faq">
						<input type="hidden" name="id" id="edit_id">
						<div class="mb-3">
							<label class="form-label">Question</label>
							<textarea class="form-control" name="question" id="edit_question" rows="3" placeholder="Enter the frequently asked question..." required></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Answer</label>
							<textarea class="form-control" name="answer" id="edit_answer" rows="5" placeholder="Enter the detailed answer..." required></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Update FAQ</span>
							<span class="icon">
								<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
								</svg>
							</span>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

    <!-- Delete Confirmation Modal -->
	<div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Delete</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to delete this FAQ? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_faq">
						<input type="hidden" name="id" id="delete_id">
						<button type="submit" class="btn btn-danger">Delete</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function(){
			// Ripple + click animation for modern buttons
			document.addEventListener('click', function(e){
				const modernBtn = e.target.closest('.modern-btn');
				if (modernBtn) {
					const circle = document.createElement('span');
					const diameter = Math.max(modernBtn.clientWidth, modernBtn.clientHeight);
					const radius = diameter / 2;
					const rect = modernBtn.getBoundingClientRect();
					circle.style.width = circle.style.height = `${diameter}px`;
					circle.style.left = `${(e.clientX - rect.left) - radius}px`;
					circle.style.top = `${(e.clientY - rect.top) - radius}px`;
					circle.classList.add('ripple');
					const existing = modernBtn.querySelector('.ripple');
					if (existing) existing.remove();
					modernBtn.appendChild(circle);
					modernBtn.style.animation = 'none';
					modernBtn.style.transform = 'scale(0.95)';
					setTimeout(() => { modernBtn.style.animation = ''; modernBtn.style.transform = ''; }, 150);
				}
			});
			// Edit and Delete functionality
			document.querySelectorAll('.btn-edit').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-faq'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_question').value = data.question || '';
					document.getElementById('edit_answer').value = data.answer || '';
				});
			});

			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-faq'));
					document.getElementById('delete_id').value = data.id;
				});
			});

			// Drag and Drop functionality
			let draggedElement = null;
			const tbody = document.querySelector('#faqsTable tbody');
			
			// Make rows draggable
			document.querySelectorAll('#faqsTable tbody tr').forEach(row => {
				const dragHandle = row.querySelector('.drag-handle');
				
				dragHandle.addEventListener('mousedown', function(e) {
					draggedElement = row;
					row.classList.add('dragging');
					
					// Add drag events to the document
					document.addEventListener('mousemove', handleDrag);
					document.addEventListener('mouseup', handleDrop);
					
					e.preventDefault();
				});
				
				// Add hover effects for drop zones
				row.addEventListener('mouseenter', function() {
					if (draggedElement && draggedElement !== row) {
						row.classList.add('drag-over');
					}
				});
				
				row.addEventListener('mouseleave', function() {
					row.classList.remove('drag-over');
				});
			});
			
			function handleDrag(e) {
				if (!draggedElement) return;
				
				// Find the row under the cursor
				const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
				const rowBelow = elementBelow ? elementBelow.closest('tr') : null;
				
				// Remove all drag-over classes
				document.querySelectorAll('#faqsTable tbody tr').forEach(row => {
					row.classList.remove('drag-over');
				});
				
				// Add drag-over to the row below cursor
				if (rowBelow && rowBelow !== draggedElement) {
					rowBelow.classList.add('drag-over');
				}
			}
			
			function handleDrop(e) {
				if (!draggedElement) return;
				
				// Find the row under the cursor
				const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
				const targetRow = elementBelow ? elementBelow.closest('tr') : null;
				
				// Remove all drag classes
				document.querySelectorAll('#faqsTable tbody tr').forEach(row => {
					row.classList.remove('dragging', 'drag-over');
				});
				
				// If we have a valid target, reorder
				if (targetRow && targetRow !== draggedElement) {
					const draggedOrder = parseInt(draggedElement.getAttribute('data-order'));
					const targetOrder = parseInt(targetRow.getAttribute('data-order'));
					
					// Determine if we're moving up or down
					const draggedIndex = Array.from(tbody.children).indexOf(draggedElement);
					const targetIndex = Array.from(tbody.children).indexOf(targetRow);
					
					if (draggedIndex < targetIndex) {
						// Moving down - insert after target
						targetRow.parentNode.insertBefore(draggedElement, targetRow.nextSibling);
					} else {
						// Moving up - insert before target
						targetRow.parentNode.insertBefore(draggedElement, targetRow);
					}
					
					// Update order in database
					updateFAQOrder();
				}
				
				// Clean up
				draggedElement = null;
				document.removeEventListener('mousemove', handleDrag);
				document.removeEventListener('mouseup', handleDrop);
			}
			
			function updateFAQOrder() {
				const rows = document.querySelectorAll('#faqsTable tbody tr');
				const faqOrders = [];
				
				rows.forEach((row, index) => {
					const faqData = JSON.parse(row.getAttribute('data-faq'));
					faqOrders.push({
						id: faqData.id,
						order_id: index + 1
					});
					row.setAttribute('data-order', index + 1);
				});
				
				// Send AJAX request to update order
				const formData = new FormData();
				formData.append('action', 'reorder_faqs');
				formData.append('faq_orders', JSON.stringify(faqOrders));
				
				fetch('', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Show success message
						showAlert('FAQ order updated successfully!', 'success');
					} else {
						showAlert('Failed to update FAQ order: ' + (data.error || 'Unknown error'), 'danger');
					}
				})
				.catch(error => {
					showAlert('Error updating FAQ order: ' + error.message, 'danger');
				});
			}
			
			function showAlert(message, type) {
				const alertContainer = document.getElementById('jsAlertContainer');
				const alertId = 'alert-' + Date.now();
				
				const alertHtml = `
					<div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
						${message}
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				`;
				
				alertContainer.insertAdjacentHTML('beforeend', alertHtml);
				
				// Auto-remove after 5 seconds
				setTimeout(() => {
					const alertElement = document.getElementById(alertId);
					if (alertElement) {
						alertElement.remove();
					}
				}, 5000);
			}

			// Auto-hide alerts after 5 seconds
			setTimeout(function(){
				document.querySelectorAll('.alert').forEach(a => {
					try { (new bootstrap.Alert(a)).close(); } catch(e) {}
				});
			}, 5000);
		});
	</script>
</body>
</html>

