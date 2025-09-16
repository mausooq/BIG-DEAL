<?php
/**
 * Topbar component for Admin pages
 * Usage:
 *   require_once __DIR__ . '/topbar.php';
 *   renderAdminTopbar($username, $title);
 */

if (!function_exists('renderAdminTopbar')) {
	function renderAdminTopbar(string $username = 'Admin', string $title = ''): void {
		// Print topbar styles once
		static $topbarStylesPrinted = false;
		if (!$topbarStylesPrinted) {
			echo '<style>
				/* CSS Variables */
				:root {
					--bg: #F1EFEC; /* page background */
					--card: #ffffff; /* surfaces */
					--muted: #6b7280; /* secondary text */
					--line: #e9eef5; /* borders */
					--brand-dark: #2f2f2f; /* logo dark */
					--primary: #e11d2a; /* brand red */
					--primary-600: #b91c1c; /* darker red hover */
					--primary-50: #fff1f1; /* soft card bg */
					--radius: 16px;
				}
				
				/* Topbar Styling */
				.navbar { 
					background: var(--card) !important; 
					border-radius: 16px; 
					margin: 12px; 
					box-shadow: 0 8px 20px rgba(0,0,0,.05); 
				}
				
				/* Override sticky behavior for specific pages */
				.navbar.sticky-top { 
					position: static; 
				}
				
				/* Text colors */
				.text-primary { 
					color: var(--primary) !important; 
				}
				
				/* Input group styling */
				.input-group .form-control { 
					border-color: var(--line); 
				}
				.input-group-text { 
					border-color: var(--line); 
				}
				
				/* Cards */
				.card { 
					border: 0; 
					border-radius: var(--radius); 
					background: var(--card); 
				}
				
				/* Mobile responsiveness */
				@media (max-width: 991.98px) {
					.navbar { 
						margin: 8px; 
						border-radius: 12px; 
					}
				}
			</style>';
			$topbarStylesPrinted = true;
		}
		?>
		<nav class="navbar navbar-light bg-white border-bottom sticky-top">
			<div class="container-fluid">
				<button class="btn btn-outline-secondary d-md-none" id="sidebarToggle" type="button" title="Toggle menu"><i class="fa-solid fa-bars"></i></button>
				<?php if ($title !== ''): ?>
					<div class="h5 mb-0 text-dark fw-semibold ms-3"><?php echo htmlspecialchars($title); ?></div>
				<?php endif; ?>
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


