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

// Handle social media link operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_social_link') {
		$platform = trim($_POST['platform'] ?? '');
		$url = trim($_POST['url'] ?? '');
		
		if ($platform && $url) {
			// Check if platform already exists
			$check_stmt = $mysqli->prepare('SELECT COUNT(*) FROM social_links WHERE platform = ?');
			$check_stmt->bind_param('s', $platform);
			$check_stmt->execute();
			$exists = $check_stmt->get_result()->fetch_row()[0];
			$check_stmt->close();
			
			if ($exists > 0) {
				$_SESSION['error_message'] = 'Social media platform already exists!';
			} else {
				$stmt = $mysqli->prepare('INSERT INTO social_links (platform, url) VALUES (?, ?)');
				if ($stmt) {
					$stmt->bind_param('ss', $platform, $url);
					if ($stmt->execute()) {
						$_SESSION['success_message'] = 'Social media link added successfully!';
						logActivity($mysqli, 'Added social media link', 'Platform: ' . $platform);
					} else {
						$_SESSION['error_message'] = 'Failed to add social media link: ' . $mysqli->error;
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
				}
			}
		} else {
			$_SESSION['error_message'] = 'Platform and URL are required.';
		}
	} elseif ($action === 'edit_social_link') {
		$id = (int)($_POST['id'] ?? 0);
		$platform = trim($_POST['platform'] ?? '');
		$url = trim($_POST['url'] ?? '');
		
		if ($id && $platform && $url) {
			// Check if platform already exists for different link
			$check_stmt = $mysqli->prepare('SELECT COUNT(*) FROM social_links WHERE platform = ? AND id != ?');
			$check_stmt->bind_param('si', $platform, $id);
			$check_stmt->execute();
			$exists = $check_stmt->get_result()->fetch_row()[0];
			$check_stmt->close();
			
			if ($exists > 0) {
				$_SESSION['error_message'] = 'Social media platform already exists!';
			} else {
				$stmt = $mysqli->prepare('UPDATE social_links SET platform = ?, url = ? WHERE id = ?');
				if ($stmt) {
					$stmt->bind_param('ssi', $platform, $url, $id);
					if ($stmt->execute()) {
						if ($stmt->affected_rows > 0) {
							$_SESSION['success_message'] = 'Social media link updated successfully!';
							logActivity($mysqli, 'Updated social media link', 'Platform: ' . $platform . ', ID: ' . $id);
						} else {
							$_SESSION['error_message'] = 'No changes made or social media link not found.';
						}
					} else {
						$_SESSION['error_message'] = 'Failed to update social media link: ' . $mysqli->error;
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
				}
			}
		} else {
			$_SESSION['error_message'] = 'Platform and URL are required.';
		}
	} elseif ($action === 'delete_social_link') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			// Get platform for logging
			$get_stmt = $mysqli->prepare('SELECT platform FROM social_links WHERE id = ?');
			$get_stmt->bind_param('i', $id);
			$get_stmt->execute();
			$link = $get_stmt->get_result()->fetch_assoc();
			$get_stmt->close();
			
			$stmt = $mysqli->prepare('DELETE FROM social_links WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Social media link deleted successfully!';
						logActivity($mysqli, 'Deleted social media link', 'Platform: ' . ($link['platform'] ?? 'Unknown') . ', ID: ' . $id);
					} else {
						$_SESSION['error_message'] = 'Social media link not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete social media link: ' . $mysqli->error;
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

// Get social media links with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE platform LIKE ? OR url LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = 'SELECT id, platform, url FROM social_links' . $whereClause . ' ORDER BY platform ASC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$socialLinks = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, platform, url FROM social_links ORDER BY platform ASC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM social_links' . $whereClause;
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

// Recent social media links
$recentSocialLinks = $mysqli->query("SELECT platform, url FROM social_links ORDER BY id DESC LIMIT 8");

// Function to get platform icon
function getPlatformIcon($platform) {
	$icons = [
		'facebook' => 'fa-facebook',
		'twitter' => 'fa-twitter',
		'instagram' => 'fa-instagram',
		'linkedin' => 'fa-linkedin',
		'youtube' => 'fa-youtube',
		'tiktok' => 'fa-tiktok',
		'whatsapp' => 'fa-whatsapp',
		'telegram' => 'fa-telegram',
		'snapchat' => 'fa-snapchat',
		'pinterest' => 'fa-pinterest',
		'github' => 'fa-github',
		'discord' => 'fa-discord'
	];
	return $icons[strtolower($platform)] ?? 'fa-share-nodes';
}

// Function to get platform color
function getPlatformColor($platform) {
	$colors = [
		'facebook' => '#1877f2',
		'twitter' => '#1da1f2',
		'instagram' => '#e4405f',
		'linkedin' => '#0077b5',
		'youtube' => '#ff0000',
		'tiktok' => '#000000',
		'whatsapp' => '#25d366',
		'telegram' => '#0088cc',
		'snapchat' => '#fffc00',
		'pinterest' => '#bd081c',
		'github' => '#333333',
		'discord' => '#5865f2'
	];
	return $colors[strtolower($platform)] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Social Media Links - Big Deal</title>
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
		.table{ --bs-table-bg:transparent; border-collapse:collapse; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
		/* Use row-level separators to avoid tiny gaps between cells */
		.table tbody td{ border-top:0; border-bottom:0; }
		.table tbody tr{ box-shadow: inset 0 1px 0 var(--line); }
		.table tbody tr:last-child{ box-shadow: inset 0 1px 0 var(--line), inset 0 -1px 0 var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.actions-cell{ display:flex; gap:8px; justify-content:flex-start; align-items:center; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; flex-shrink:0; }
		.actions-cell .btn:disabled{ opacity:0.5; cursor:not-allowed; }
		/* Platform icons */
		.platform-icon{ width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; color:white; font-size:14px; }
		/* URL styling */
		.url-link{ color:#1d4ed8; text-decoration:none; }
		.url-link:hover{ text-decoration:underline; }
		/* Badges */
		.badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
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
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('social-media'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Social Media Links'); ?>

		<div class="container-fluid p-4">
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

			<!-- Search toolbar -->
			<div class="toolbar mb-4">
				<div class="row-top">
					<form class="d-flex flex-grow-1" method="get">
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search social media links by platform or URL">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addSocialLinkModal">
						<span class="text">Add Social Link</span>
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
								<div class="h6 mb-0">Social Media Links</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="socialLinksTable">
									<thead>
										<tr>
											<th>Platform</th>
											<th>URL</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $socialLinks->fetch_assoc()): ?>
										<tr data-social='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
											<td>
												<div class="d-flex align-items-center gap-2">
													<div class="platform-icon" style="background-color: <?php echo getPlatformColor($row['platform']); ?>">
														<i class="fa-brands <?php echo getPlatformIcon($row['platform']); ?>"></i>
													</div>
													<span class="fw-semibold"><?php echo htmlspecialchars(ucfirst($row['platform'])); ?></span>
												</div>
											</td>
											<td>
												<a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" class="url-link" style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;" title="<?php echo htmlspecialchars($row['url']); ?>">
													<?php echo htmlspecialchars($row['url']); ?>
												</a>
											</td>
											<td class="text-end actions-cell">
												<button class="btn btn-sm btn-outline-secondary btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editSocialLinkModal" title="Edit Social Link"><i class="fa-solid fa-pen"></i></button>
												<button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete Social Link"><i class="fa-solid fa-trash"></i></button>
											</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>

							<?php if ($totalPages > 1): ?>
							<nav aria-label="Social media links pagination">
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
								<div class="h6 mb-0">Recent Social Links</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($s = $recentSocialLinks->fetch_assoc()): ?>
								<div class="item-row d-flex align-items-center justify-content-between" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
									<div class="d-flex align-items-center gap-2">
										<div class="platform-icon" style="background-color: <?php echo getPlatformColor($s['platform']); ?>; width:24px; height:24px; font-size:12px;">
											<i class="fa-brands <?php echo getPlatformIcon($s['platform']); ?>"></i>
										</div>
										<span class="item-title fw-semibold"><?php echo htmlspecialchars(ucfirst($s['platform'])); ?></span>
									</div>
									<a href="<?php echo htmlspecialchars($s['url']); ?>" target="_blank" class="text-primary"><i class="fa-solid fa-external-link-alt"></i></a>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div> -->
			</div>
		</div>
	</div>

	<!-- Add Social Link Modal -->
	<div class="modal fade" id="addSocialLinkModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add New Social Media Link</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_social_link">
						<div class="mb-3">
							<label class="form-label">Platform</label>
							<select class="form-select" name="platform" required>
								<option value="">Select Platform</option>
								<option value="facebook">Facebook</option>
								<option value="twitter">Twitter</option>
								<option value="instagram">Instagram</option>
								<option value="linkedin">LinkedIn</option>
								<option value="youtube">YouTube</option>
								<option value="tiktok">TikTok</option>
								<option value="whatsapp">WhatsApp</option>
								<option value="telegram">Telegram</option>
								<option value="snapchat">Snapchat</option>
								<option value="pinterest">Pinterest</option>
								<option value="github">GitHub</option>
								<option value="discord">Discord</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">URL</label>
							<input type="url" class="form-control" name="url" placeholder="https://..." required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Add Social Link</span>
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

	<!-- Edit Social Link Modal -->
	<div class="modal fade" id="editSocialLinkModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit Social Media Link</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_social_link">
						<input type="hidden" name="id" id="edit_id">
						<div class="mb-3">
							<label class="form-label">Platform</label>
							<select class="form-select" name="platform" id="edit_platform" required>
								<option value="">Select Platform</option>
								<option value="facebook">Facebook</option>
								<option value="twitter">Twitter</option>
								<option value="instagram">Instagram</option>
								<option value="linkedin">LinkedIn</option>
								<option value="youtube">YouTube</option>
								<option value="tiktok">TikTok</option>
								<option value="whatsapp">WhatsApp</option>
								<option value="telegram">Telegram</option>
								<option value="snapchat">Snapchat</option>
								<option value="pinterest">Pinterest</option>
								<option value="github">GitHub</option>
								<option value="discord">Discord</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">URL</label>
							<input type="url" class="form-control" name="url" id="edit_url" placeholder="https://..." required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Update Social Link</span>
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
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Delete</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to delete this social media link? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_social_link">
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
			document.querySelectorAll('.btn-edit').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-social'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_platform').value = data.platform || '';
					document.getElementById('edit_url').value = data.url || '';
				});
			});

			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-social'));
					document.getElementById('delete_id').value = data.id;
				});
			});

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