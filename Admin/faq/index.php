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

// Stats
$totalFaqs = fetchScalar('SELECT COUNT(*) FROM faqs');
$totalBlogs = fetchScalar('SELECT COUNT(*) FROM blogs');
$totalProperties = fetchScalar('SELECT COUNT(*) FROM properties');
$totalEnquiries = fetchScalar('SELECT COUNT(*) FROM enquiries');

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
		.sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.content{ margin-left:284px; }
		.list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
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
		/* Toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
		.table tbody tr{ border-top:1px solid var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.actions-cell{ display:flex; gap:8px; justify-content:flex-start; align-items:center; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; flex-shrink:0; }
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
		.actions-cell{ width:15%; }
		#faqsTable{ width:100%; table-layout:fixed; }
		/* Badges */
		.badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Mobile responsiveness */
		@media (max-width: 991.98px){
			.sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
			.sidebar.open{ left:12px; }
			.content{ margin-left:0; }
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
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Search FAQs...'); ?>

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

			<div class="row g-3 mb-3">
				<div class="col-12"><div class="h5 mb-0">Quick Access</div></div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">FAQs</div>
								<div class="h4 mb-0"><?php echo $totalFaqs; ?></div>
							</div>
							<div class="text-primary"><i class="fa-solid fa-question-circle fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Properties</div>
								<div class="h4 mb-0"><?php echo $totalProperties; ?></div>
							</div>
							<div class="text-info"><i class="fa-solid fa-building fa-lg"></i></div>
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
							<div class="text-warning"><i class="fa-solid fa-rss fa-lg"></i></div>
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
					<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFaqModal">
						<i class="fa-solid fa-circle-plus me-1"></i>Add FAQ
					</button>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-xl-8">
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
												<button class="btn btn-sm btn-outline-secondary btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editFaqModal" title="Edit FAQ"><i class="fa-solid fa-pen"></i></button>
												<button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete FAQ"><i class="fa-solid fa-trash"></i></button>
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

				<div class="col-xl-4">
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
				</div>
			</div>
		</div>
	</div>

	<!-- Add FAQ Modal -->
	<div class="modal fade" id="addFaqModal" tabindex="-1">
		<div class="modal-dialog modal-lg">
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
						<button type="submit" class="btn btn-primary">Add FAQ</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit FAQ Modal -->
	<div class="modal fade" id="editFaqModal" tabindex="-1">
		<div class="modal-dialog modal-lg">
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
						<button type="submit" class="btn btn-primary">Update FAQ</button>
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

