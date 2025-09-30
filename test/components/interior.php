<?php
// Interior component with 3D album carousel
require_once __DIR__ . '/../config/config.php';

// Get projects with their first images
$mysqli = getMysqliConnection();
$projects = [];

// Derive uploads prefix relative to current page depth using $asset_path when available
// This mirrors the strategy used in other components to make URLs work from any route
if (!isset($asset_path)) { $asset_path = 'assets/'; }
$dotdotCount = substr_count($asset_path, '../');
$uploads_prefix = str_repeat('../', $dotdotCount + 1); // from current page to project root

$query = "
    SELECT p.id, p.name, p.description, p.location, pi.image_filename
    FROM projects p
    LEFT JOIN project_images pi ON p.id = pi.project_id AND pi.display_order = 1
    ORDER BY p.order_id ASC
";

$result = $mysqli->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

$mysqli->close();
?>

<style>
* {
  box-sizing: border-box;
}

:root {
  --bg: hsl(0, 0%, 10%);
  --min-size: 280px;
}

.interior-section {
  display: flex;
  align-items: center;
  min-height: 100vh;
  padding: 0;
  margin: 0;
  overflow-y: hidden;
  background: #ffffff;
  background-position: center;
  background-size: cover;
  position: relative;
  overflow: hidden;
}

.interior-section:before {
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

.drag-proxy {
  visibility: hidden;
  position: absolute;
}

.controls {
  position: absolute;
  top: calc(50% + clamp(var(--min-size), 20vmin, 20vmin));
  left: 50%;
  transform: translate(-50%, -50%) scale(1.5);
  display: flex;
  justify-content: space-between;
  min-width: var(--min-size);
  height: 44px;
  width: 20vmin;
  z-index: 300;
}

.interior-section button {
  height: 48px;
  width: 48px;
  border-radius: 50%;
  position: absolute;
  top: 0%;
  outline: transparent;
  cursor: pointer;
  background: none;
  appearance: none;
  border: 0;
  transition: transform 0.1s;
  transform: translate(0, calc(var(--y, 0)));
}

.interior-section button:before {
  border: 2px solid #cc1a1a;
  background: linear-gradient(135deg, #cc1a1a, #111111);
  content: '';
  box-sizing: border-box;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  height: 80%;
  width: 80%;
  border-radius: 50%;
}

.interior-section button:active:before {
  background: linear-gradient(135deg, #111111, #cc1a1a);
}

.interior-section button:nth-of-type(1) {
  right: 100%;
}

.interior-section button:nth-of-type(2) {
  left: 100%;
}

.interior-section button span {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

.interior-section button:hover {
  --y: -5%;
}

.interior-section button svg {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(0deg) translate(2%, 0);
  height: 30%;
  fill: #ffffff;
}

.interior-section button:nth-of-type(1) svg {
  transform: translate(-50%, -50%) rotate(180deg) translate(2%, 0);
}

.scroll-icon {
  height: 30px;
  position: fixed;
  top: 1rem;
  right: 1rem;
  color: #cc1a1a;
  animation: action 4s infinite;
}

@keyframes action {
  0%, 25%, 50%, 100% {
    transform: translate(0, 0);
  }
  12.5%, 37.5% {
    transform: translate(0, 25%);
  }
}

.boxes {
  height: 100vh;
  width: 100%;
  overflow: hidden;
  position: absolute;
  transform-style: preserve-3d;
  perspective: 1000px;
  touch-action: pan-y; /* allow vertical scroll on touch devices */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.box {
  transform-style: preserve-3d;
  position: absolute;
  top: 50%;
  left: 50%;
  height: 30vmin;
  width: 30vmin;
  min-height: 320px;
  min-width: 320px;
  display: block;
  opacity: 1;
  transform: translate(-50%, -50%);
  background: rgba(255, 255, 255, 0.95);
  border: none;
  border-radius: 8px;
  overflow: hidden;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.box:after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  height: 100%;
  width: 100%;
  background-image: var(--src);
  background-size: cover;
  transform: translate(-50%, -50%) rotate(180deg) translate(0, -100%) translate(0, -0.5vmin);
  opacity: 0.75;
}

.box:before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  height: 100%;
  width: 100%;
  background: linear-gradient(var(--bg) 50%, transparent);
  transform: translate(-50%, -50%) rotate(180deg) translate(0, -100%) translate(0, -0.5vmin) scale(1.01);
  z-index: 2;
}

.box img {
  position: absolute;
  height: 100%;
  width: 100%;
  top: 0;
  left: 0;
  object-fit: cover;
  border-radius: 8px;
  z-index: 1;
}

.box span {
  position: absolute;
  top: 10px;
  left: 10px;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #cc1a1a, #111111);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  z-index: 2;
  font-family: 'DM Sans', sans-serif;
}

/* Remove background colors to show images properly */

@supports(-webkit-box-reflect: below) {
  .box {
    -webkit-box-reflect: below 0.5vmin linear-gradient(transparent 0 50%, white 100%);
  }
  
  .box:after,
  .box:before {
    display: none;
  }
}

.interior-title {
  font-size: 48px;
  font-weight: 700;
  font-style: Bold;
  margin-top: 1em;
  letter-spacing: 1%;
  color: #111111;
  z-index: 100;
  position: absolute;
  top: 2rem;
  left: 2.3em;
}

.interior-subtitle {
  font-weight: 400;
  font-style: Regular;
  font-size: 16px;
  letter-spacing: 1px;
  color: #666;
  margin-top: 3.5em;
  z-index: 100;
  position: absolute;
  top: 5rem;
  left: 2rem;
  margin-left: 5em;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .interior-title {
    font-size: 1.75em;
    top: 1.5rem;
  }
  .interior-subtitle {
    font-size: 0.8125em;
    top: 4rem;
  }
}

@media (max-width: 768px) {
  .interior-title {
    font-size: 1.75em;
    top: 1rem;
  }
  .interior-subtitle {
    font-size: 0.8125em;
    top: 3.5rem;
  }
}

@media (max-width: 480px) {
  .interior-title {
    font-size: 1.5em;
    top: 1rem;
  }
  .interior-subtitle {
    font-size: 0.75em;
    line-height: 1.5;
    top: 3rem;
  }
  /* Improve scroll performance on mobile */
  .boxes { will-change: transform; }
  .box { will-change: transform, opacity; }
}
</style>

<section class="interior-section">
  <div class="container">
    <h2 class="interior-title">Our Interior Designs</h2>
    <p class="interior-subtitle">Transform your space with our innovative design solutions</p>
  </div>
  
  <div class="boxes">
    <?php 
    $counter = 1;
    foreach ($projects as $project): 
        // Skip projects without a primary image (no fallback)
        if (empty($project['image_filename'])) { continue; }
        // Build a URL that works from any page depth
        $imagePath = $uploads_prefix . 'uploads/projects/' . $project['image_filename'];
    ?>
    <div class="box" style="--src: url(<?php echo $imagePath; ?>)">
      <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($project['name']); ?>" loading="lazy" decoding="async">
    </div>
    <?php 
    $counter++;
    endforeach; 
    ?>
    
    <!-- <div class="controls">
      <button class="prev">
        <span>Previous album</span>
        <svg viewBox="0 0 448 512" width="100" title="Previous Album">
          <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z"/>
        </svg>
      </button>
      <button class="next">
        <span>Next album</span>
        <svg viewBox="0 0 448 512" width="100" title="Next Album">
          <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z"/>
        </svg>
      </button> -->
    </div>
  </div>
  
  
  <div class="drag-proxy"></div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Register GSAP plugins
  gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

  const BOXES = document.querySelectorAll('.box');
  
  if (!BOXES.length) {
    console.log('No boxes found');
    return;
  }

  const tl = gsap.timeline();

  // Mobile-friendly adjustments
  const isMobile = window.matchMedia('(max-width: 768px)').matches;

  const myST = ScrollTrigger.create({
    animation: tl,
    id: "interior-st",
    trigger: ".interior-section",
    start: "top top",
    end: isMobile ? "+=250%" : "+=500%",
    pin: ".interior-section",
    scrub: isMobile ? 0.5 : true,
    anticipatePin: 1,
    snap: isMobile ? {
      snapTo: (value) => {
        const step = 1 / BOXES.length;
        return Math.round(value / step) * step;
      },
      duration: 0.25,
      delay: 0,
    } : {
      snapTo: 1 / (BOXES.length)
    },
    markers: false // Set to true for debugging
  });

  // Set initial state for 3D carousel - start from left and move right
  gsap.set(BOXES, { 
    x: (index) => index * 420,
    z: (index) => -index * 120,
    rotateY: (index) => index * 20,
    scale: (index) => index === 0 ? 1.15 : 0.75,
    opacity: (index) => index <= 1 ? 1 : 0.3
  });

  // Create animations for each box - rotate carousel
  BOXES.forEach((box, i) => {
    const centerIndex = Math.floor(BOXES.length / 2);
    const offset = i - centerIndex;
    
    tl
      .to(box, {
        x: 0,
        z: 0,
        rotateY: 0,
        scale: 1.1,
        opacity: 1,
        duration: 0.5
      }, 0.5 * i)
      .to(BOXES, {
        x: (index) => (index - i) * 420,
        z: (index) => -Math.abs(index - i) * 120,
        rotateY: (index) => (index - i) * 20,
        scale: (index) => index === i ? 1.15 : 0.75,
        opacity: (index) => Math.abs(index - i) <= 1 ? 1 : 0.3,
        duration: 0.5
      }, '<')
      .add("interior-work-" + (i + 1));
  });

  // Navigation functions
  const NEXT = () => {
    const currentProgress = tl.progress();
    const nextProgress = Math.min(1, currentProgress + (1 / BOXES.length));
    gsap.to(window, { duration: 1, scrollTo: myST.start + (myST.end - myST.start) * nextProgress });
  };
  
  const PREV = () => {
    const currentProgress = tl.progress();
    const prevProgress = Math.max(0, currentProgress - (1 / BOXES.length));
    gsap.to(window, { duration: 1, scrollTo: myST.start + (myST.end - myST.start) * prevProgress });
  };

  // Event listeners
  document.addEventListener('keydown', event => {
    if (event.code === 'ArrowLeft' || event.code === 'KeyA') PREV();
    if (event.code === 'ArrowRight' || event.code === 'KeyD') NEXT();
  });

  document.querySelector('.boxes').addEventListener('click', e => {
    const BOX = e.target.closest('.box');
    if (BOX) {
      const index = Array.from(BOXES).indexOf(BOX);
      if (index !== -1) {
        const targetProgress = index / BOXES.length;
        gsap.to(window, { duration: 1, scrollTo: myST.start + (myST.end - myST.start) * targetProgress });
      }
    }
  });

  // Button events
  const nextBtn = document.querySelector('.next');
  const prevBtn = document.querySelector('.prev');
  
  console.log('Next button found:', nextBtn);
  console.log('Prev button found:', prevBtn);
  
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      console.log('Next button clicked');
      NEXT();
    });
  }
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      console.log('Prev button clicked');
      PREV();
    });
  }

  console.log('Interior carousel initialized with ScrollTrigger');
  console.log('Timeline labels:', tl.labels);
});
</script>