<?php
/**
 * Sidebar component for Admin pages
 * Usage:
 *   require_once __DIR__ . '/sidebar.php';
 *   renderAdminSidebar('dashboard'); // pass active key
 */

if (!function_exists('renderAdminSidebar')) {
	function renderAdminSidebar(string $active = ''): void {
		$items = [
			['key' => 'dashboard', 'icon' => 'fa-grip', 'label' => 'Dashboard', 'href' => '../dashboard/'],
			['key' => 'sales-analytics', 'icon' => 'fa-chart-line', 'label' => 'Sales Analytics', 'href' => '../sales-analytics/'],
			['key' => 'properties', 'icon' => 'fa-building-circle-check', 'label' => 'Properties', 'href' => '../properties/'],
			['key' => 'categories', 'icon' => 'fa-tags', 'label' => 'Categories', 'href' => '../categories/'],
			['key' => 'location', 'icon' => 'fa-map-location-dot', 'label' => 'Locations', 'href' => '../location/'],
			['key' => 'enquiries', 'icon' => 'fa-envelope-open-text', 'label' => 'Enquiries', 'href' => '../enquiries/'],
			['key' => 'blogs', 'icon' => 'fa-newspaper', 'label' => 'Blogs', 'href' => '../blogs/'],
			['key' => 'testimonials', 'icon' => 'fa-face-smile', 'label' => 'Testimonials', 'href' => '../testimonials/'],
			['key' => 'notifications', 'icon' => 'fa-bell', 'label' => 'Notifications', 'href' => '../notification/'],
			['key' => 'faq', 'icon' => 'fa-question-circle', 'label' => 'FAQs', 'href' => '../faq/'],
			['key' => 'social-media', 'icon' => 'fa-share-nodes', 'label' => 'Social Media', 'href' => '../social-link/'],
			['key' => 'admin', 'icon' => 'fa-users', 'label' => 'Admin Users', 'href' => '../admin/'],
			['key' => 'logout', 'icon' => 'fa-arrow-right-from-bracket', 'label' => 'Logout', 'href' => '../logout.php'],
		];
		?>
		<div class="sidebar p-3 d-flex flex-column gap-3">
			<div class="d-flex align-items-center gap-2">
				<img src="../../assets/logo.jpg" alt="Big Deal" style="height:48px; width:auto; object-fit:contain; display:block;">
			</div>
			<div class="list-group list-group-flush">
				<?php foreach ($items as $item): ?>
					<a class="list-group-item list-group-item-action <?php echo $active === $item['key'] ? 'active' : ''; ?>" href="<?php echo $item['href']; ?>">
						<i class="fa-solid <?php echo $item['icon']; ?> me-2"></i><?php echo $item['label']; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}


