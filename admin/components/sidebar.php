<?php
/**
 * Sidebar component for Admin pages
 * Usage:
 *   require_once __DIR__ . '/sidebar.php';
 *   renderAdminSidebar('dashboard'); // pass active key
 */

if (!function_exists('renderAdminSidebar')) {
    function renderAdminSidebar(string $active = ''): void {
		// Print sidebar styles once
		static $sidebarStylesPrinted = false;
		if (!$sidebarStylesPrinted) {
            echo '<style>
				/* CSS Variables for consistent theming */
				:root {
					--bg: #F1EFEC; /* page background */
					--card: #ffffff; /* surfaces */
					--muted: #6b7280; /* secondary text */
					--line: #e9eef5; /* borders */
					--brand-dark: #2f2f2f; /* logo dark */
					--primary: #e11d2a; /* logo red accent */
					--primary-600: #b91c1c; /* darker red hover */
					--radius: 16px;
				}
				
				/* Sidebar Styles */
				.sidebar { 
					width: 260px; 
					min-height: 93vh; 
					background: var(--card); 
					border-right: 1px solid var(--line); 
					position: fixed; 
					border-radius: var(--radius); 
					margin: 12px; 
					box-shadow: 0 8px 20px rgba(0,0,0,.05);
					-ms-overflow-style: none; 
					scrollbar-width: none; 
				}
				.sidebar::-webkit-scrollbar { 
					width: 0; 
					height: 0; 
					display: none; 
				}
				
				/* Content margin to account for sidebar */
				.content { 
					margin-left: 284px; /* account for sidebar margin */
				}
				
				/* Brand styling */
				.brand { 
					font-weight: 700; 
					font-size: 1.25rem; 
				}
				
				/* List group items */
				.list-group-item { 
					border: 0; 
					padding: .75rem 1rem; 
					border-radius: 10px; 
					margin: .15rem .25rem; 
					color: #111827; 
				}
				.list-group-item i { 
					width: 18px; 
				}
				.list-group-item.active { 
					background: #fff1f1; 
					color: var(--primary); 
					font-weight: 600; 
				}
                .list-group-item:hover { 
					background: #f8fafc; 
				}

                /* Sidebar badge for counts */
                .sidebar-badge{
                    display:inline-flex; align-items:center; justify-content:center;
                    min-width: 22px; height: 22px; padding: 0 6px;
                    font-size: .75rem; font-weight: 600; line-height: 1;
                    border-radius: 999px; margin-left: 8px;
                    background: #ef4444; color:#fff;
                }
				
				/* Mobile responsiveness */
				@media (max-width: 991.98px) { /* md breakpoint */
					.sidebar { 
						left: -300px; 
						right: auto; 
						transition: left .25s ease; 
						position: fixed; 
						top: 0; 
						bottom: 0; 
						margin: 12px; 
						z-index: 1050; 
					}
					.sidebar.open { 
						left: 12px; 
					}
					.content { 
						margin-left: 0; 
					}
				}
			</style>';
			$sidebarStylesPrinted = true;
        }
		
        // Fetch unread notifications count (hide badge if 0)
        $unreadNotifications = 0;
        if (!function_exists('getMysqliConnection')) {
            @require_once __DIR__ . '/../../config/config.php';
        }
        if (function_exists('getMysqliConnection')) {
            $mysqli = @getMysqliConnection();
            if ($mysqli) {
                $res = $mysqli->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = FALSE");
                if ($res && ($row = $res->fetch_assoc())) { $unreadNotifications = (int)($row['c'] ?? 0); }
                if ($res) { $res->close(); }
                // do not close shared connection here
            }
        }

        $items = [
			['key' => 'dashboard', 'icon' => 'fa-grip', 'label' => 'Dashboard', 'href' => '../dashboard/'],
			['key' => 'sales-analytics', 'icon' => 'fa-chart-line', 'label' => 'Sales Analytics', 'href' => '../sales-analytics/'],
			['key' => 'properties', 'icon' => 'fa-building-circle-check', 'label' => 'Properties', 'href' => '../properties/'],
			['key' => 'categories', 'icon' => 'fa-tags', 'label' => 'Categories', 'href' => '../categories/'],
			['key' => 'location', 'icon' => 'fa-map-location-dot', 'label' => 'Locations', 'href' => '../location/'],
			['key' => 'enquiries', 'icon' => 'fa-envelope-open-text', 'label' => 'Enquiries', 'href' => '../enquiries/'],
			['key' => 'our-builds', 'icon' => 'fa-hammer', 'label' => 'Our Builds', 'href' => '../our-builds/'],
			['key' => 'blogs', 'icon' => 'fa-newspaper', 'label' => 'Blogs', 'href' => '../blogs/'],
			['key' => 'testimonials', 'icon' => 'fa-face-smile', 'label' => 'Testimonials', 'href' => '../testimonials/'],
			['key' => 'notifications', 'icon' => 'fa-bell', 'label' => 'Notifications', 'href' => '../notification/'],
			['key' => 'faq', 'icon' => 'fa-question-circle', 'label' => 'FAQs', 'href' => '../faq/'],
			['key' => 'social-media', 'icon' => 'fa-share-nodes', 'label' => 'Social Media', 'href' => '../social-link/'],
			['key' => 'admin', 'icon' => 'fa-users', 'label' => 'Admin Users', 'href' => '../admin/'],
			['key' => 'activity-logs', 'icon' => 'fa-list-check', 'label' => 'Activity Logs', 'href' => '../activity-logs/'],
			['key' => 'logout', 'icon' => 'fa-arrow-right-from-bracket', 'label' => 'Logout', 'href' => '../logout.php'],
		];
		?>
		<div class="sidebar p-3 d-flex flex-column gap-3" style="max-height:100vh; overflow:auto; overscroll-behavior:contain;">
			<div class="d-flex align-items-center gap-2">
				<img src="../../assets/logo.jpg" alt="Big Deal" style="height:48px; width:auto; object-fit:contain; display:block;">
			</div>
			<div class="list-group list-group-flush">
                <?php foreach ($items as $item): ?>
                    <a class="list-group-item list-group-item-action <?php echo $active === $item['key'] ? 'active' : ''; ?>" href="<?php echo $item['href']; ?>">
                        <i class="fa-solid <?php echo $item['icon']; ?> me-2"></i><?php echo $item['label']; ?>
                        <?php if ($item['key'] === 'notifications' && $unreadNotifications > 0): ?>
                            <span class="sidebar-badge"><?php echo $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}


