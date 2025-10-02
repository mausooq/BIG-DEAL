<?php
// Interior component with 3D album carousel
require_once __DIR__ . '/../config/config.php';

// Get projects with their first images
$mysqli = getMysqliConnection();
$projects = [];

// Build paths based on current page context
if (!isset($asset_path)) { $asset_path = 'assets/'; }

// Determine path depth based on asset_path
if (strpos($asset_path, '../') === 0) {
    // We're in a subdirectory, use relative paths
    $uploads_prefix = '../uploads/projects/';
    $interior_page_link = '../creative-works/index.php';
} else {
    // We're at root level, use direct paths
    $uploads_prefix = 'uploads/projects/';
    $interior_page_link = 'creative-works/index.php';
}

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

/* Constrain section content width */
.interior-section .container {
  position: relative;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Header row (title + button) */
.interior-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.interior-link-arrow {
  position: absolute;
  right: 2rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 400;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #cc1a1a, #111111);
  color: #ffffff;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  transition: transform 0.2s ease;
}

.interior-link-arrow:hover { transform: translateY(-50%) scale(1.05); }
.interior-link-arrow svg { width: 28px; height: 28px; fill: currentColor; }

.interior-section:before {
  width: 50vw;
  height: 50vw;
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 0;
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
  height: 80vh;
  width: 100%;
  overflow: hidden;
  position: relative;
  transform-style: preserve-3d;
  perspective: 1000px;
  touch-action: pan-y;
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
  margin: 2rem 0 0.25rem 0;
  letter-spacing: 1%;
  color: #111111;
}

.interior-title-link {
  position: absolute;
  right: 5rem;
  top: 3.5rem;
  z-index: 120;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: #111111;
  font-weight: 600;
  padding: 10px 16px;
  border-radius: 999px;
  background: #111111;
  color: #ffffff;
}

.interior-title-link:hover { opacity: 0.9; }
.interior-title-link svg { width: 18px; height: 18px; fill: currentColor; }

.interior-subtitle {
  font-weight: 400;
  font-style: Regular;
  font-size: 16px;
  letter-spacing: 1px;
  color: #666;
  margin: 0 0 1.5rem 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .interior-title {
    font-size: 1.75em;
  }
  .interior-subtitle {
    font-size: 0.8125em;
  }
}

@media (max-width: 768px) {
  .interior-title {
    font-size: 1.75em;
  }
  .interior-subtitle {
    font-size: 0.8125em;
  }
}

@media (max-width: 480px) {
  .interior-title {
    font-size: 1.5em;
  }
  .interior-subtitle {
    font-size: 0.75em;
    line-height: 1.5;
    margin-bottom: 1rem;
  }
  .interior-header { flex-direction: column; align-items: flex-start; gap: 8px; }
  .interior-title-link { position: static; padding: 8px 12px; }
  .boxes { height: 60vh; }
}
</style>

<section class="interior-section">
  <div class="container">
    <div class="interior-header">
      <div>
        <h2 class="interior-title">Creative Works</h2>
        <p class="interior-subtitle">Transform your space with our innovative design solutions</p>
      </div>
      <a class="interior-title-link" href="<?php echo $interior_page_link; ?>" aria-label="View all interiors">
        <span>View all</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/></svg>
      </a>
    </div>

    <div class="boxes">
    <?php
    $counter = 1;
    foreach ($projects as $project): 
        // Skip projects without a primary image (no fallback)
        if (empty($project['image_filename'])) { continue; }
        // Build a URL that works from any page depth
        $imagePath = $uploads_prefix . $project['image_filename'];
    ?>
    <div class="box" style="--src: url(<?php echo $imagePath; ?>)">
      <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($project['name']); ?>">
    </div>
    <?php 
    $counter++;
    endforeach; 
    ?>
    
    
    </div>
  </div>
  
 
  
  
  <div class="drag-proxy"></div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const BOXES = Array.from(document.querySelectorAll('.box'));
  if (!BOXES.length) return;

  let currentIndex = 0;

  // layout function for a simple centered carousel
  const layout = () => {
    BOXES.forEach((box, index) => {
      const distance = index - currentIndex;
      const clamped = Math.max(-1, Math.min(1, distance));
      const x = clamped * 420;
      const z = -Math.abs(clamped) * 120;
      const rotateY = clamped * 20;
      const scale = index === currentIndex ? 1.15 : 0.75;
      const opacity = Math.abs(distance) <= 1 ? 1 : 0.3;
      gsap.to(box, { x, z, rotateY, scale, opacity, duration: 0.5, overwrite: true });
    });
  };

  // initial positions
  BOXES.forEach((box, index) => {
    gsap.set(box, {
      x: index * 420,
      z: -index * 120,
      rotateY: index * 20,
      scale: index === 0 ? 1.15 : 0.75,
      opacity: index <= 1 ? 1 : 0.3
    });
  });

  const next = () => {
    currentIndex = (currentIndex + 1) % BOXES.length;
    layout();
  };

  const prev = () => {
    currentIndex = (currentIndex - 1 + BOXES.length) % BOXES.length;
    layout();
  };

  // click to focus a card
  document.querySelector('.boxes').addEventListener('click', e => {
    const box = e.target.closest('.box');
    if (!box) return;
    const idx = BOXES.indexOf(box);
    if (idx === -1) return;
    currentIndex = idx;
    layout();
  });

  // autoplay: move left-to-right repeatedly
  let direction = 1; // 1 forward, -1 backward
  const autoplay = () => {
    currentIndex = (currentIndex + direction + BOXES.length) % BOXES.length;
    layout();
  };
  // ping-pong effect across items
  setInterval(() => {
    if (currentIndex === BOXES.length - 1) direction = -1;
    else if (currentIndex === 0) direction = 1;
    autoplay();
  }, 2000);
});
</script>