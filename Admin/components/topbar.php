<?php
/**
 * Topbar component for Admin pages
 * Usage:
 *   require_once __DIR__ . '/topbar.php';
 *   renderAdminTopbar($username, $searchPlaceholder = 'Search anything...');
 */

if (!function_exists('renderAdminTopbar')) {
	function renderAdminTopbar(string $username = 'Admin', string $searchPlaceholder = 'Search anything...'): void {
		?>
		<nav class="navbar navbar-light bg-white border-bottom sticky-top">
			<div class="container-fluid">
				<button class="btn btn-outline-secondary d-md-none" id="sidebarToggle" type="button" title="Toggle menu"><i class="fa-solid fa-bars"></i></button>
				<form class="d-none d-md-flex" role="search">
					<div class="input-group">
						<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input class="form-control border-start-0" type="search" placeholder="<?php echo htmlspecialchars($searchPlaceholder); ?>">
					</div>
				</form>
				<div class="d-flex align-items-center gap-3">
					<i class="fa-regular fa-bell"></i>
					<div class="d-flex align-items-center gap-2">
						<div class="fw-semibold"><?php echo htmlspecialchars($username); ?></div>
						<img src="../../assets/profile_default.png" class="rounded-circle" width="32" height="32" alt="avatar">
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


