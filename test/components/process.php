<?php
// Our Process Component
?>

<section class="process-section">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-12 col-lg-6">
				<h2 class="process-title">Our Process</h2>
				<p class="process-eyebrow">Homebuying Steps</p>

				<div class="process-step" data-image="<?php echo $asset_path; ?>images/prop/prop1.png">
					<div class="process-bar"></div>
					<div>
						<h3 class="process-step-title">Step 1: Discover Your Dream Home</h3>
						<p class="process-step-desc">Browse through a curated selection of properties tailored to your lifestyle and budget.</p>
					</div>
				</div>

				<div class="process-step" data-image="<?php echo $asset_path; ?>images/prop/prop2.png">
					<div class="process-bar"></div>
					<div>
						<h3 class="process-step-title">Step 2: Schedule A Viewing</h3>
						<p class="process-step-desc">Book a tour at your convenience and explore the space in person or virtually.</p>
					</div>
				</div>

				<div class="process-step" data-image="<?php echo $asset_path; ?>images/prop/prop3.png">
					<div class="process-bar"></div>
					<div>
						<h3 class="process-step-title">Step 3: Seal The Deal</h3>
						<p class="process-step-desc">Get expert guidance to finalize paperwork and move into your new home with confidence.</p>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-6">
				<div class="process-image-wrap">
					<img src="<?php echo $asset_path; ?>images/prop/prop1.png" alt="Process" class="process-image" id="process-image">
				</div>
			</div>
		</div>
	</div>
</section>

<style>
/* Process - Scoped Styles */
.process-section {
    /* Center content while pinned */
    padding: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    color: #111111;
}

/* Keep section content vertically centered in viewport */
.process-section .container { min-height: auto; }

.process-eyebrow {
	font-family: 'DM Sans', sans-serif;
	letter-spacing: 1.5px;
	font-size: 0.85rem;
	color: #666666;
	margin: 0 0 2em 0;
}

.process-title {
	font-family: 'DM Sans', sans-serif;
	font-size: 2.25rem;
	font-weight: 700;
	line-height: 1.2;
	letter-spacing: 1px;
}

.process-step {
	display: grid;
	grid-template-columns: 8px 1fr;
	gap: 16px;
	align-items: start;
	margin-bottom: 4em;
}

.process-step:last-child { margin-bottom: 0; }

.process-bar {
	width: 4px;
	background: #e9ecef;
	border-radius: 4px;
	height: 100%;
	min-height: 40px;
	transition: background 0.25s ease, transform 0.25s ease;
}

.process-step-title {
	font-family: 'DM Sans', sans-serif;
	font-weight: 700;
	font-size: 1.5em;
	margin: 0 0 6px 0;
}

.process-step-desc {
	color: #666666;
	margin: 0;
	font-size: 0.975rem;
	line-height: 1.7;
	letter-spacing: 1px;
}

/* Active state */
.process-step.active .process-bar { background: #cc1a1a; }
.process-step.active .process-step-title { color: #111111; }
.process-step.active { transform: translateX(0); }

.process-image-wrap {
	border-radius: 16px;
	overflow: hidden;
	box-shadow: 0 10px 30px rgba(17,17,17,0.08);
}

.process-image {
	display: block;
	width: 100%;
    height: 600px;
	object-fit: cover;
	transition: transform 0.6s ease;
	border-radius: 16px;
}

.process-image-wrap:hover .process-image { transform: scale(1.03); }

/* Responsive */
@media (max-width: 992px) {
    .process-image { height: 480px; }
}
@media (max-width: 576px) {
	.process-title { font-size: 1.875rem; }
    .process-image { height: 360px; }
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var steps = Array.prototype.slice.call(document.querySelectorAll('.process-step'));
    var img = document.getElementById('process-image');
    if (!steps.length || !img || typeof gsap === 'undefined') return;

    gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

    function setActive(index) {
        steps.forEach(function(s){ s.classList.remove('active'); });
        var el = steps[index];
        if (!el) return;
        el.classList.add('active');
        var url = el.getAttribute('data-image');
        if (url && img.getAttribute('src') !== url) {
            img.style.transition = 'opacity 250ms ease';
            img.style.opacity = '0';
            setTimeout(function(){
                img.setAttribute('src', url);
                img.style.opacity = '1';
            }, 250);
        }
    }

    // Pin the section and snap between three steps
    var currentIdx = 0;
    var st = ScrollTrigger.create({
        id: 'process-st',
        trigger: '.process-section',
        start: 'center center',
        end: '+=300%',
        pin: true,
        scrub: false,
        snap: { snapTo: [0, 0.5, 1], duration: 0.6, ease: 'power1.inOut' },
        onUpdate: function(self){
            currentIdx = Math.round(self.progress * (steps.length - 1));
            setActive(currentIdx);
        }
    });

    // One-step-per-wheel scroll within the pinned section
    var isAnimating = false;
    var sectionEl = document.querySelector('.process-section');
    function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }
    function goToIndex(idx){
        var t = clamp(idx, 0, steps.length - 1);
        var y = st.start + (st.end - st.start) * (t / (steps.length - 1));
        isAnimating = true;
        gsap.to(window, { duration: 0.6, scrollTo: y, ease: 'power1.out', onComplete: function(){ isAnimating = false; currentIdx = t; } });
    }
    function wheelHandler(e){
        if (!st || !st.pin) return; // not active
        var dir = e.deltaY > 0 ? 1 : -1;
        var p = st.progress || 0;
        var eps = 0.02;
        // If at boundaries and user scrolls outward, allow default behavior to exit the section
        if ((dir < 0 && p <= eps) || (dir > 0 && p >= 1 - eps)) {
            return; // do not preventDefault so page can scroll past
        }
        // Otherwise, handle one-step navigation smoothly
        e.preventDefault();
        if (isAnimating) return;
        goToIndex(currentIdx + dir);
    }
    sectionEl.addEventListener('wheel', wheelHandler, { passive: false });

    // Optional: keyboard navigation for smoother UX
    sectionEl.addEventListener('keydown', function(e){
        if (!st || !st.pin) return;
        if (e.key === 'ArrowDown' || e.key === 'PageDown') { e.preventDefault(); if (!isAnimating) goToIndex(currentIdx + 1); }
        if (e.key === 'ArrowUp' || e.key === 'PageUp') { e.preventDefault(); if (!isAnimating) goToIndex(currentIdx - 1); }
    });
    sectionEl.setAttribute('tabindex', '-1');

    setActive(0);
});
</script>
