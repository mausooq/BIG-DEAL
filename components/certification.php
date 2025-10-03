<div class="certifications">
	<div class="certifications-inner">
		<ul class="certifications-grid">
			<li class="certifications-item">
				<div class="certifications-stack">
					<div class="certifications-caption">Registered under</div>
					<img src="assets/images/certification/rear.jpg" alt="RERA Approved Logo" loading="lazy" />
				</div>
			</li>
			<li class="certifications-item">
				<div class="certifications-stack">
					<div class="certifications-caption">"Recognized by</div>
					<img src="assets/images/certification/startupindia.png" alt="DPIIT Startup India Logo" loading="lazy" />
				</div>
			</li>
			<li class="certifications-item">
				<div class="certifications-stack">
					<div class="certifications-caption">Recognized by</div>
					<img src="assets/images/certification/startupkarantaka.jpg" alt="Startup Karnataka Logo" loading="lazy" />
				</div>
			</li>
		</ul>
	</div>
</div>

<style>
.certifications { width: 100%; margin-top: 64px; }
.certifications-inner { margin: 0 auto; max-width: 1200px; padding-left: 16px; padding-right: 16px; }
.certifications-grid {
	list-style: none;
	margin: 0;
	padding: 0;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	gap: 16px;
	align-items: center;
	justify-items: center;
}
.certifications-item { display: flex; align-items: center; justify-content: center; }
.certifications-stack { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.certifications-caption { font-family: "DM Sans", Arial, sans-serif; font-weight: 300; font-size: 12px; color: #6b7280; line-height: 1; text-align: center; white-space: nowrap; min-height: 1.2em; }
.certifications-item img {
	max-width: 100%;
	max-height: 64px;
	object-fit: contain;
	filter: none;
}
/* Enlarge first and last logos on small screens */
.certifications-item:first-child img,
.certifications-item:last-child img { max-height: 90px; }
@media (min-width: 768px) {
	.certifications { margin-top: 4rem; }
	.certifications-grid { gap: 24px; grid-template-columns: repeat(3, minmax(160px, 1fr)); }
	.certifications-caption { font-size: 14px; min-height: 1.2em; }
	.certifications-item img { max-height: 88px; }
	/* Enlarge first and last logos on larger screens */
	.certifications-item:first-child img,
	.certifications-item:last-child img { max-height: 120px; }
}
</style>


