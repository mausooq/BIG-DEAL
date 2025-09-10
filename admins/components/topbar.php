<?php
/**
 * Topbar component for Admin pages
 * Usage:
 *   require_once __DIR__ . '/topbar.php';
 *   renderAdminTopbar($username);
 */

if (!function_exists('renderAdminTopbar')) {
	function renderAdminTopbar(string $username = 'Admin'): void {
		?>
		<nav class="navbar navbar-light bg-white border-bottom sticky-top">
			<div class="container-fluid">
				<button class="btn btn-outline-secondary d-md-none" id="sidebarToggle" type="button" title="Toggle menu"><i class="fa-solid fa-bars"></i></button>
				<div class="d-flex align-items-center gap-3 ms-auto">
					<!-- Notifications dropdown -->
					<div class="dropdown">
						<button class="btn btn-link text-decoration-none text-dark" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
							<i class="fa-regular fa-bell"></i>
						</button>
						<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width:260px;">
							<li class="dropdown-header small text-muted">Notifications</li>
							<li><hr class="dropdown-divider"></li>
							<li class="px-3 py-2 small text-muted">No new notifications</li>
							<li><hr class="dropdown-divider"></li>
							<li><a class="dropdown-item" href="../notification/"><i class="fa-regular fa-bell me-2"></i>View all</a></li>
						</ul>
					</div>

					<!-- Profile dropdown -->
					<div class="dropdown">
						<button class="btn d-flex align-items-center gap-2" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
							<div class="fw-semibold"><?php echo htmlspecialchars($username); ?></div>
							<img src="../../assets/profile_default.png" class="rounded-circle" width="32" height="32" alt="avatar">
						</button>
						<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
							<li class="dropdown-header">Account</li>
							<li><a class="dropdown-item" href="../admin/index.php?edit_id=<?php echo (int)($_SESSION['admin_id'] ?? 0); ?>"><i class="fa-solid fa-user-gear me-2"></i>Profile Settings</a></li>
							<li><hr class="dropdown-divider"></li>
							<li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
						</ul>
					</div>
				</div>
			</div>
		</nav>
		<script>
		document.addEventListener('DOMContentLoaded', function(){
			var btn = document.getElementById('sidebarToggle');
			if (btn) {
				btn.addEventListener('click', function(){
					var sb = document.querySelector('.sidebar');
					if (sb) { sb.classList.toggle('open'); }
				});
			}
		});
		</script>
		<?php
	}
}


