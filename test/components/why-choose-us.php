<?php
// Why Choose Us Component
?>

<section class="wcu-section">
	<div class="container">
		<div class="row wcu-header">
			<div class="col-12 col-lg-8">
				<h2 class="wcu-title">Why Choose Us?</h2>
				<p class="wcu-desc">We are a real estate firm with over 20 years of expertise, and our main goal is to provide amazing locations to our partners and clients. Within the luxury real estate market, our agency offers customized solutions.</p>
			</div>
			<div class="col-12 col-lg-4 wcu-cta-col">
				<a href="#contact" class="wcu-btn">Contact Us <span class="wcu-btn-arrow">→</span></a>
			</div>
		</div>

		<div class="row wcu-icons-row">
			<div class="col-12 col-md-4 wcu-icon-card">
				<div class="wcu-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6a3 3 0 0 0 0 6Zm8.94-2a1 1 0 0 0 .06-.34v-1.32a1 1 0 0 0-.06-.34l-2.02-.78a7.95 7.95 0 0 0-.6-1.45l1.17-1.79a1 1 0 0 0-.24-1.36L17.42 2.9a1 1 0 0 0-1.36.24l-1.79 1.17c-.47-.23-.96-.43-1.47-.58L12 1.71a1 1 0 0 0-.34-.06h-1.32a1 1 0 0 0-.34.06l-.78 2.02c-.5.15-1 .35-1.47.58L5.96 3.14a1 1 0 0 0-1.36-.24L2.9 4.58a1 1 0 0 0-.24 1.36l1.17 1.79c-.23.47-.43.96-.58 1.47L1.71 12c-.04.11-.06.22-.06.34v1.32c0 .12.02.23.06.34l2.02.78c.15.5.35 1 .58 1.47l-1.17 1.79a1 1 0 0 0 .24 1.36l1.7 1.68a1 1 0 0 0 1.36-.24l1.79-1.17c.47.23.96.43 1.47.58l.78 2.02c.11.04.22.06.34.06h1.32c.12 0 .23-.02.34-.06l.78-2.02c.5-.15 1-.35 1.47-.58l1.79 1.17a1 1 0 0 0 1.36-.24l1.68-1.7a1 1 0 0 0 .24-1.36l-1.17-1.79c.23-.47.43-.96.58-1.47l2.02-.78Z"/>
					</svg>
				</div>
				<h3 class="wcu-icon-title">Property Valuation</h3>
				<p class="wcu-icon-desc">All living, dining, kitchen and play areas were devised by attached to the home.</p>
			</div>
			<div class="col-12 col-md-4 wcu-icon-card">
				<div class="wcu-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-7l9 7M5 10v10h14V10M9 20v-6h6v6"/>
					</svg>
				</div>
				<h3 class="wcu-icon-title">Property Management</h3>
				<p class="wcu-icon-desc">Generous amounts of south facing glazing maximize the solar gains for most of the year.</p>
			</div>
			<div class="col-12 col-md-4 wcu-icon-card">
				<div class="wcu-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 4h18"/>
					</svg>
				</div>
				<h3 class="wcu-icon-title">Invest Opportunities</h3>
				<p class="wcu-icon-desc">All-inclusive real estate services to facilitate the easy management of your properties.</p>
			</div>
		</div>

		<div class="row wcu-cards-row">
			<div class="col-12 col-md-4">
				<div class="wcu-card">
					<img class="wcu-card-img" src="<?php echo $asset_path; ?>images/why-choose-us/property-valuation.jpg" alt="Property Valuation">
				</div>
			</div>
			<div class="col-12 col-md-4">
				<div class="wcu-card">
					<img class="wcu-card-img" src="<?php echo $asset_path; ?>images/why-choose-us/property-management.jpg" alt="Property Management">
				</div>
			</div>
			<div class="col-12 col-md-4">
				<div class="wcu-card">
					<img class="wcu-card-img" src="<?php echo $asset_path; ?>images/why-choose-us/invest-opportunities.jpg" alt="Invest Opportunities">
				</div>
			</div>
		</div>
	</div>
</section>

<style>
/* Why Choose Us - Scoped Styles */
.wcu-section {
	padding: 64px 0;
	background: #ffffff;
	color: #111111;
}

.wcu-title {
	font-family: 'DM Sans', sans-serif;
	font-size: 2.25rem;
	font-weight: 700;
	line-height: 1.2;
	letter-spacing: 1px;
	color: #111111;
	margin: 0 0 8px 0;
}


.wcu-desc {
	color: #666666;
	font-size: 1rem;
	line-height: 1.7;
	letter-spacing: 1px;
	margin: 0 0 8px 0;
}

.wcu-cta-col {
	display: flex;
	align-items: start;
	justify-content: end;
}

.wcu-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 18px;
	background: transparent;
	border: 1px solid #cc1a1a;
	border-radius: 999px;
	color: #cc1a1a;
	text-decoration: none;
	transition: all 0.2s ease;
}

.wcu-btn:hover {
	background: #cc1a1a;
	color: #ffffff;
	border-color: #cc1a1a;
}

.wcu-btn-arrow { transition: transform 0.2s ease; }
.wcu-btn:hover .wcu-btn-arrow { transform: translateX(3px); }

.wcu-icons-row {
	margin-top: 28px;
	margin-bottom: 16px;
}

.wcu-icon-card {
	margin-bottom: 24px;
}

.wcu-icon {
	width: 72px;
	height: 72px;
	border-radius: 50%;
	background: rgba(204,26,26,0.06);
	border: 2px solid rgba(204,26,26,0.35);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #cc1a1a;
	margin-bottom: 12px;
    font-size: 0; /* icons are SVG */
	position: relative;
	box-shadow: 0 0 0 6px rgba(204,26,26,0.05);
	transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, border-color 0.25s ease;
}

.wcu-icon svg {
    width: 32px;
    height: 32px;
}

.wcu-icon:hover {
	transform: scale(1.06);
	box-shadow: 0 0 0 8px rgba(204,26,26,0.08);
	background: rgba(204,26,26,0.1);
	border-color: #cc1a1a;
}

.wcu-icon-title {
	font-weight: 700;
	color: #111111;
	font-size: 1.125rem;
	margin: 0 0 6px 0;
}


.wcu-icon-desc {
	color: #666666;
	margin: 0;
	line-height: 1.6;
	font-size: 0.95rem;
}

.wcu-cards-row {
	margin-top: 12px;
}

.wcu-card {
	background: #ffffff;
	border: 1px solid #e9ecef;
	border-radius: 8px;
	overflow: hidden;
	transform: translateY(16px);
	opacity: 0;
	animation: wcu-fade-up 0.8s ease forwards;
	transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.wcu-card-img {
	display: block;
	width: 100%;
	height: 350px;
	object-fit: cover;
	transition: transform 0.6s ease;
}

.wcu-card:hover { transform: translateY(10px); box-shadow: 0 12px 28px rgba(17,17,17,0.08); }
.wcu-card:hover .wcu-card-img { transform: scale(1.06); }

/* Staggered fade-up animations */
.wcu-icons-row .wcu-icon-card {
	opacity: 0;
	transform: translateY(16px);
	animation: wcu-fade-up 0.8s ease forwards;
}
.wcu-icons-row .wcu-icon-card:nth-child(1) { animation-delay: 0.05s; }
.wcu-icons-row .wcu-icon-card:nth-child(2) { animation-delay: 0.15s; }
.wcu-icons-row .wcu-icon-card:nth-child(3) { animation-delay: 0.25s; }

.wcu-cards-row .col-12:nth-child(1) .wcu-card { animation-delay: 0.15s; }
.wcu-cards-row .col-12:nth-child(2) .wcu-card { animation-delay: 0.3s; }
.wcu-cards-row .col-12:nth-child(3) .wcu-card { animation-delay: 0.45s; }

@keyframes wcu-fade-up {
	from { opacity: 0; transform: translateY(16px); }
	to { opacity: 1; transform: translateY(0); }
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
	.wcu-card, .wcu-card-img, .wcu-btn-arrow, .wcu-icon {
		transition: none !important;
		animation: none !important;
	}
}

/* Responsive */
@media (max-width: 992px) {
	.wcu-cta-col { justify-content: start; margin-top: 16px; }
}

@media (max-width: 576px) {
	.wcu-title { font-size: 1.875rem; }
	.wcu-card-img { height: 200px; }
}
</style>
