<?php
// Services Component with Carousel Functionality (Updated for Real Estate)
?>
<section class="our-work Service-section">
    <div class="container sticky">
        <div class="row align-items-center">
            <div class="col-12 col-md-4 sfpro">
                <h4 style="color : #cc1a1a !important">Bigdeal.property </h4>
                <h1>Your Trusted Property Service</h1>
                <p class="service-subtitle">Comprehensive solutions for all your property needs</p>
            </div>
            <div class="col-12 col-md-8 slider">
                <div class="carousel__slider">
                    <div class="carousel__item" id="our-work-1" style="--bg-image: url('<?php echo $asset_path; ?>images/prop/prop1.png')">
                        <div class="blurred-box__step wow animated" style="visibility: visible;">1</div>
                        <h2 class="blurred-box__title wow animated" style="visibility: visible;">Tech-Driven Search & Support</h2>
                        <div class="blurred-box__footer">
                            <span class="blurred-box__footer-line wow animated" style="visibility: visible;"></span>
                            <p class="blurred-box__text wow animated" style="visibility: visible;">We begin with a detailed consultation to understand your goals (buy, rent, or invest).</p>
                            <p class="blurred-box__text wow animated" style="visibility: visible;">Leverage our data platform for **AI-powered property recommendations** and initial market support.</p>
                        </div>
                    </div>
                    <div class="carousel__item" id="our-work-2" style="--bg-image: url('<?php echo $asset_path; ?>images/prop/prop2.png')">
                        <div class="blurred-box__step">2</div>
                        <h2 class="blurred-box__title">Expert Evaluation & Purchase</h2>
                        <div class="blurred-box__footer">
                            <span class="blurred-box__footer-line"></span>
                            <p class="blurred-box__text">We provide in-depth analysis on selected properties covering investment potential and future value.</p>
                            <p class="blurred-box__text">Our experts handle aggressive **negotiation and price finalization** to secure your best deal.</p>
                        </div>
                    </div>
                    <div class="carousel__item" id="our-work-3" style="--bg-image: url('<?php echo $asset_path; ?>images/prop/prop3.png')">
                        <div class="blurred-box__step">3</div>
                        <h2 class="blurred-box__title">Seamless Documentation & Registration</h2>
                        <div class="blurred-box__footer">
                            <span class="blurred-box__footer-line"></span>
                            <p class="blurred-box__text">Manage all complex legal documentation, stamp duty payments, and government formalities.</p>
                            <p class="blurred-box__text">Ensure a smooth, transparent, and **error-free property registration process** from start to finish.</p>
                        </div>
                    </div>
                    <div class="carousel__item" id="our-work-4" style="--bg-image: url('<?php echo $asset_path; ?>images/prop/prop4.png')">
                        <div class="blurred-box__step">4</div>
                        <h2 class="blurred-box__title">Design & Interiors Services</h2>
                        <div class="blurred-box__footer">
                            <span class="blurred-box__footer-line"></span>
                            <p class="blurred-box__text">Connect you with top-tier partners for **turnkey interior design and furnishing solutions**.</p>
                            <p class="blurred-box__text">From concept to execution, we ensure the property matches your vision and is ready for occupancy.</p>
                        </div>
                    </div>
                    <div class="carousel__item" id="our-work-5" style="--bg-image: url('<?php echo $asset_path; ?>images/prop/prop5.png')">
                        <div class="blurred-box__step">5</div>
                        <h2 class="blurred-box__title">Professional Property Management</h2>
                        <div class="blurred-box__footer">
                            <span class="blurred-box__footer-line"></span>
                            <p class="blurred-box__text">Maximize your return on investment with our professional rental management and tenant screening services.</p>
                            <p class="blurred-box__text">Handle all maintenance, rent collection, and regulatory compliance on your behalf.</p>
                        </p>
                        </div>
                    </div>
                </div>
                <ul class="carousel__nav">
                    <li class="carousel__nav__item" data-target="our-work-1"></li>
                    <li class="carousel__nav__item" data-target="our-work-2"></li>
                    <li class="carousel__nav__item" data-target="our-work-3"></li>
                    <li class="carousel__nav__item" data-target="our-work-4"></li>
                    <li class="carousel__nav__item" data-target="our-work-5"></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
/* Services Carousel Styles */
* {
	margin: 0;
	padding: 0;
}

.Service-section {
	height: 100vh;
}


.our-work {
	display: flex;
	align-items: center;
	background-color: #ffffff;
	background-position: center;
	background-size: cover;
	position: relative;
	overflow: hidden;
}

.our-work:before {
	width: 50vw;
	height: 50vw;
	position: absolute;
	top: 50%;
	left: 50%;
	z-index: 0;
	background: #f9f0f0;
	border-radius: 760px;
	transform: translate(-50%, -50%);
	backface-visibility: hidden;
	opacity: 0.4;
	filter: blur(270px);
	content: '';
	pointer-events: none;
	will-change: transform;
}

.our-work h1 {
	font-size: 48px;
	font-weight: 700;
	font-style: Bold;
	/* margin-top: 1em; */
	letter-spacing: 1%;
	color: #111111;
	display: inline-flex;
	line-height: 1.1;
	font-family: 'DM Sans', sans-serif;
}

.service-subtitle {
	font-weight: 400;
	font-style: Regular;
	font-size: 16px;
	letter-spacing: 1px;
	color: #666;
	margin-top: 1em;
	font-family: 'DM Sans', sans-serif;
}

.slider {
	display: flex;
	gap: 55px;
	align-items: center;
	position: relative;
}

.carousel__slider {
	position: relative;
	width: 100%;
	min-height: 440px;
}

.carousel__item {
	width: 100%;
	min-height: 440px;
	padding: 48px;
	color: #111111;
	background: rgba(255, 255, 255, 0.95);
	background-image: var(--bg-image);
	background-size: cover;
	background-position: center;
	background-repeat: no-repeat;
	border: none;
	border-radius: 8px;
	position: absolute;
	top: 0%;
	opacity: 0;
	backdrop-filter: blur(10px);
	transition: all 0.3s ease;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.carousel__item::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255, 255, 255, 0.85);
	border-radius: 8px;
	z-index: 1;
	pointer-events: none;
}

.carousel__item > * {
	position: relative;
	z-index: 2;
}

.blurred-box__step {
	width: 60px;
	height: 60px;
	background: linear-gradient(135deg, #cc1a1a, #111111);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24px;
	font-weight: 700;
	color: #ffffff;
	margin-bottom: 24px;
	font-family: 'DM Sans', sans-serif;
}

.blurred-box__title {
	font-size: 28px;
	font-weight: 600;
	color: #111111;
	margin-bottom: 24px;
	line-height: 1.3;
	font-family: 'DM Sans', sans-serif;
}

.blurred-box__footer {
	margin-top: 24px;
}

.blurred-box__footer-line {
	display: block;
	width: 60px;
	height: 3px;
	background: linear-gradient(135deg, #cc1a1a, #111111);
	margin-bottom: 16px;
	border-radius: 2px;
}

.blurred-box__text {
	font-size: 16px;
	color: #3a3a3a;
	line-height: 1.6;
	margin-bottom: 12px;
	font-family: 'DM Sans', sans-serif;
}

.blurred-box__text:last-child {
	margin-bottom: 0;
}

.carousel__nav {
	display: flex;
	gap: 16px;
	align-items: center;
	flex-direction: column;
	list-style: none;
	margin: 0;
	padding: 0;
}

.carousel__nav__item {
	background: #f9f0f0;
	box-shadow: none;
	display: block;
	width: 12px;
	height: 12px;
	border: 1px solid rgba(17, 17, 17, 0.2);
	border-radius: 50%;
	cursor: pointer;
	transition: all 0.3s ease;
}

.carousel__nav__item:hover {
	background: #cc1a1a;
	transform: scale(1.2);
}

.carousel__nav__item--active {
	background: #cc1a1a;
	box-shadow: 0 0 16px #cc1a1a;
}

.sticky {
	position: relative;
	z-index: 10;
}

/* Responsive Design */
@media (max-width: 768px) {
	.our-work h1 {
		font-size: 2.5rem;
		margin-bottom: 30px;
	}
	
	.slider {
		flex-direction: column;
		gap: 30px;
	}
	
	.carousel__slider {
		min-height: 350px;
	}
	
	.carousel__item {
		min-height: 350px;
		padding: 30px;
	}
	
	.blurred-box__title {
		font-size: 24px;
	}
	
	.blurred-box__step {
		width: 50px;
		height: 50px;
		font-size: 20px;
	}
	
	.carousel__nav {
		flex-direction: row;
		justify-content: center;
		margin-top: 20px;
	}
}

@media (max-width: 576px) {
	.our-work h1 {
		font-size: 2rem;
	}
	
	.carousel__item {
		padding: 20px;
		min-height: 300px;
	}
	
	.blurred-box__title {
		font-size: 20px;
	}
	
	.blurred-box__text {
		font-size: 14px;
	}
	
	.blurred-box__step {
		width: 40px;
		height: 40px;
		font-size: 18px;
	}
}

/* Animation Classes */
.wow.animated {
	animation-duration: 1s;
	animation-fill-mode: both;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
	width: 8px;
}

::-webkit-scrollbar-track {
	background: #f9f0f0;
}

::-webkit-scrollbar-thumb {
	background: #cc1a1a;
	border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
	background: #111111;
}

/* Loading States */
.carousel__item.loading {
	opacity: 0.5;
	pointer-events: none;
}

.carousel__item.loading::after {
	content: "";
	position: absolute;
	top: 50%;
	left: 50%;
	width: 30px;
	height: 30px;
	margin: -15px 0 0 -15px;
	border: 3px solid rgba(255, 255, 255, 0.3);
	border-top: 3px solid #cc1a1a;
	border-radius: 50%;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

/* Accessibility */
.carousel__nav__item:focus {
	outline: 2px solid #cc1a1a;
	outline-offset: 2px;
}

.carousel__item:focus-within {
	outline: 2px solid #cc1a1a;
	outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
	.carousel__item {
		background: rgba(255, 255, 255, 0.9);
		border: none;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
	}
	
	.blurred-box__text {
		color: #111111;
	}
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
	.carousel__item,
	.carousel__nav__item,
	.blurred-box__step {
		transition: none;
	}
	
	.carousel__nav__item:hover {
		transform: none;
	}
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Register GSAP plugins
	gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

	const list = document.querySelector(".carousel__nav");
	const listItems = gsap.utils.toArray(".carousel__nav__item", list);
	const slides = gsap.utils.toArray(".carousel__item");
	
	if (!list || !listItems.length || !slides.length) {
		console.log('Carousel elements not found');
		return;
	}

	const tl = gsap.timeline();

	const myST = ScrollTrigger.create({
		animation: tl,
		id: "st",
		trigger: ".our-work",
		start: "top top",
		end: "+=500%",
		pin: ".our-work",
		scrub: true,
		snap: {
			snapTo: 1 / (slides.length)
		},
		markers: false // Set to true for debugging
	});

	// Set initial state for slides
	gsap.set(slides, { yPercent: 125, scale: 0.5, opacity: 0 });

	listItems.forEach((item, i) => {
		// Add click event listener
		item.addEventListener("click", e => {
			e.preventDefault();
			const percent = tl.labels[e.target.getAttribute("data-target")] / tl.totalDuration();
			const scrollPos = myST.start + (myST.end - myST.start) * percent;
			gsap.to(window, { duration: 2, scrollTo: scrollPos });
		});

		const previousItem = listItems[i - 1];
		
		if (previousItem) {
			tl
				.to(item, { 
					background: "#cc1a1a", 
					boxShadow: '0 0 16px #cc1a1a' 
				}, 0.5 * (i - 1))
				.to(slides[i], {
					opacity: 1,
					yPercent: 0,
					scale: 1,
				}, '<')
				.to(previousItem, { 
					backgroundColor: '#f9f0f0', 
					boxShadow: '0 0 16px transparent' 
				}, '<')
				.to(slides[i - 1], {
					opacity: 0,
					yPercent: -125,
					scale: 0.5,
				}, '<')
				.add("our-work-" + (i + 1));
		} else {
			// First item
			gsap.set(item, { 
				background: "#cc1a1a", 
				boxShadow: '0 0 16px #cc1a1a' 
			});
			tl.to(slides[i], { 
				yPercent: 0, 
				opacity: 1,
				scale: 1, 
				duration: 0
			}, 0);
			tl.add("our-work-" + (i + 1), "+=0.5");
		}
	});

	console.log('Services carousel initialized with labels:', tl.labels);
});
</script>