<?php
require_once __DIR__ . '/../config/config.php';

// Build asset path and uploads path - use correct relative paths
$asset_path = '../assets/';
$uploads_prefix = '../uploads/projects/';

$mysqli = getMysqliConnection();

$projects = [];
$projectImages = [];
$sql = "
  SELECT p.id, p.name, p.description, p.location, p.order_id,
         pi.image_filename
  FROM projects p
  LEFT JOIN project_images pi
    ON p.id = pi.project_id AND pi.display_order = 1
  ORDER BY p.order_id ASC, p.created_at DESC
";

if ($res = $mysqli->query($sql)) {
  while ($row = $res->fetch_assoc()) {
    $projects[] = $row;
  }
  $res->free();
}

$mysqli->close();
// Fetch all images for gallery modal
try {
  $mysqli2 = getMysqliConnection();
  $imgSql = "SELECT project_id, image_filename, display_order FROM project_images ORDER BY project_id ASC, display_order ASC, id ASC";
  if ($imgRes = $mysqli2->query($imgSql)) {
    while ($r = $imgRes->fetch_assoc()) {
      $pid = (int)$r['project_id'];
      if (!isset($projectImages[$pid])) { $projectImages[$pid] = []; }
      $projectImages[$pid][] = $r['image_filename'];
    }
    $imgRes->free();
  }
  $mysqli2->close();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Our Works - Big Deal Ventures</title>
  <!-- Favicon for Google Search Console - must use root /favicon.ico -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
  <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $asset_path; ?>css/style.css" />
  <style>
    body { font-family: 'DM Sans', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }

    .hero-banner {
      position: relative;
      min-height: 38vh;
      background: linear-gradient(180deg, rgba(255,255,255,0.85) 0%, rgba(255,255,255,0.85) 100%), url('<?php echo $asset_path; ?>images/service-bg.jpg');
      background-size: cover; background-position: center; display: grid; place-items: center;
    }
    .hero-banner .centered { text-align: center; }
    .hero-banner h1 { font-weight: 700; color: #111111; margin-bottom: 0.5rem; }
    .breadcrumbs { color: #666; font-size: 14px; }
    .breadcrumbs a { color: #cc1a1a; text-decoration: none; }

    .works-section { padding: 56px 0; background: #ffffff; position: relative; }
    .works-header { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
    .works-title { font-size: 32px; font-weight: 700; color: #111111; margin: 0; }
    .works-subtitle { color: #444; margin: 0; font-size: 16px; }

    .work-card {
      position: relative; border: none; border-radius: 12px; overflow: hidden;
      background: rgba(255,255,255,0.95); box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      height: 100%; transition: transform 200ms ease, box-shadow 200ms ease;
    }
    .work-card:hover { transform: translateY(-4px); box-shadow: 0 14px 32px rgba(0,0,0,0.12); }
    .work-thumb { position: relative; aspect-ratio: 16 / 10; width: 100%; overflow: hidden; background: #f6f6f6; }
    .work-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .work-chip { position: absolute; top: 12px; left: 12px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #fff;
      background: linear-gradient(135deg, #cc1a1a, #111111); box-shadow: 0 6px 16px rgba(204,26,26,0.25); }
    .work-eye { position: absolute; right: 12px; bottom: 12px; width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color:#fff; background: linear-gradient(135deg, #cc1a1a, #111111); cursor: pointer; box-shadow: 0 10px 22px rgba(0,0,0,.18); transition: transform .15s ease; }
    .work-eye:hover { transform: translateY(-2px); }
    .work-eye i { pointer-events: none; }
    .work-body { padding: 16px 16px 18px 16px; }
    .work-name { font-size: 18px; font-weight: 700; color: #111111; margin: 0 0 6px 0; }
    .work-meta { display: flex; align-items: center; gap: 8px; color: #666; font-size: 14px; margin-bottom: 10px; }
    .work-desc { color: #444; font-size: 14px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    .works-grid { row-gap: 24px; }

    /* CTA */
    .works-cta { display: inline-flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 999px; text-decoration: none;
      color: #fff; background: #111111; transition: transform 150ms ease; }
    .works-cta:hover { transform: translateY(-2px); opacity: .95; }
    .works-cta svg { width: 18px; height: 18px; fill: currentColor; }

    @media (max-width: 576px) {
      .works-title { font-size: 24px; }
      .works-subtitle { font-size: 14px; }
      .work-name { font-size: 16px; }
    }

    /* Lightbox modal */
    .lightbox-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.75); display: none; align-items: center; justify-content: center; z-index: 9998; }
    .lightbox-backdrop.show { display: flex; }
    .lightbox { position: relative; width: min(92vw, 1100px); height: min(86vh, 720px); background: #000; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.35); }
    .lightbox img { width: 100%; height: 100%; object-fit: contain; background: #000; }
    .lightbox-close { position: absolute; top: 10px; right: 10px; width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(17,17,17,.85); color:#fff; cursor: pointer; z-index: 2; }
    .lightbox-nav { position: absolute; inset: 0; display: flex; align-items: center; justify-content: space-between; pointer-events: none; }
    .lightbox-btn { pointer-events: all; width: 56px; height: 56px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(17,17,17,.6); color:#fff; margin: 0 10px; cursor: pointer; }
    .lightbox-counter { position: absolute; left: 12px; bottom: 10px; color: #fff; font-size: 13px; background: rgba(17,17,17,.6); padding: 6px 10px; border-radius: 999px; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../components/navbar.php'; ?>

  <section class="hero-banner">
    <div class="centered">
      <h1>Our Works</h1>
      <div class="breadcrumbs"><a href="../index.php">Home</a> > <span>Our Works</span></div>
    </div>
  </section>

  <section class="works-section">
    <div class="container">
      <div class="works-header">
        <div>
          <h2 class="works-title">Featured Creative Works</h2>
          <p class="works-subtitle">Explore our latest projects and interiors</p>
        </div>
        <a href="../services/index.php#services-section" class="works-cta" aria-label="Explore Services">
          <span>Explore Services</span>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/></svg>
        </a>
      </div>

      <div class="row works-grid">
        <?php if (!count($projects)): ?>
          <div class="col-12"><p class="text-muted mb-0">No works to display yet.</p></div>
        <?php else: ?>
          <?php foreach ($projects as $idx => $p):
            // Build image path with fallback
            if (!empty($p['image_filename'])) {
              $image = $uploads_prefix . $p['image_filename'];
              // Verify file exists - if not, use fallback
              $full_path = __DIR__ . '/../uploads/projects/' . $p['image_filename'];
              if (!file_exists($full_path)) {
                $image = $asset_path . 'images/prop/aboutimg.png';
              }
            } else {
              $image = $asset_path . 'images/prop/aboutimg.png';
            }
            $name = htmlspecialchars($p['name'] ?? 'Untitled');
            $location = htmlspecialchars($p['location'] ?? '');
            $desc = htmlspecialchars($p['description'] ?? '');
            $pid = (int)$p['id'];
            $imagesForProject = $projectImages[$pid] ?? [];
            $imageUrls = [];
            foreach ($imagesForProject as $fn) { $imageUrls[] = $uploads_prefix . $fn; }
            $dataImages = htmlspecialchars(json_encode($imageUrls), ENT_QUOTES, 'UTF-8');
          ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <article class="work-card">
              <div class="work-thumb">
                <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>">
                <?php if ($location): ?><span class="work-chip"><?php echo $location; ?></span><?php endif; ?>
                <button class="work-eye" type="button" aria-label="View gallery" data-images='<?php echo $dataImages; ?>' data-start="0">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
              <div class="work-body">
                <h3 class="work-name"><?php echo $name; ?></h3>
                <?php if ($location): ?>
                <div class="work-meta"><i class="fa-regular fa-compass"></i> <span><?php echo $location; ?></span></div>
                <?php endif; ?>
                <p class="work-desc"><?php echo $desc; ?></p>
              </div>
            </article>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php include __DIR__ . '/../components/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      const backdrop = document.createElement('div');
      backdrop.className = 'lightbox-backdrop';
      backdrop.innerHTML = `
        <div class="lightbox" role="dialog" aria-modal="true" aria-label="Project gallery">
          <button class="lightbox-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          <div class="lightbox-nav">
            <button class="lightbox-btn prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="lightbox-btn next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
          </div>
          <div class="lightbox-counter" aria-live="polite">1 / 1</div>
          <img alt="Gallery image" />
        </div>`;
      document.body.appendChild(backdrop);

      const imgEl = backdrop.querySelector('img');
      const counterEl = backdrop.querySelector('.lightbox-counter');
      const closeBtn = backdrop.querySelector('.lightbox-close');
      const prevBtn = backdrop.querySelector('.prev');
      const nextBtn = backdrop.querySelector('.next');

      let images = [];
      let index = 0;

      function update() {
        if (!images.length) return;
        imgEl.src = images[index];
        counterEl.textContent = (index + 1) + ' / ' + images.length;
      }

      function open(imagesArr, startIdx) {
        images = imagesArr || [];
        index = Math.max(0, Math.min(startIdx || 0, images.length - 1));
        update();
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
      }

      function close() {
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
      }

      function prev(){ if (!images.length) return; index = (index - 1 + images.length) % images.length; update(); }
      function next(){ if (!images.length) return; index = (index + 1) % images.length; update(); }

      closeBtn.addEventListener('click', close);
      backdrop.addEventListener('click', (e) => { if (e.target === backdrop) close(); });
      prevBtn.addEventListener('click', prev);
      nextBtn.addEventListener('click', next);
      document.addEventListener('keydown', (e) => {
        if (!backdrop.classList.contains('show')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
      });

      // Swipe support (basic)
      let startX = 0;
      imgEl.addEventListener('touchstart', (e) => { startX = e.changedTouches[0].clientX; }, { passive: true });
      imgEl.addEventListener('touchend', (e) => {
        const dx = e.changedTouches[0].clientX - startX;
        if (dx > 30) prev();
        if (dx < -30) next();
      }, { passive: true });

      // Hook eyes
      document.querySelectorAll('.work-eye').forEach((btn) => {
        btn.addEventListener('click', () => {
          try {
            const arr = JSON.parse(btn.getAttribute('data-images') || '[]');
            if (!arr.length) {
              // Fallback to the thumbnail image in the same card
              const thumb = btn.parentElement.querySelector('img');
              open([thumb ? thumb.src : ''], 0);
            } else {
              open(arr, parseInt(btn.getAttribute('data-start') || '0', 10));
            }
          } catch(err) {
            const thumb = btn.parentElement.querySelector('img');
            open([thumb ? thumb.src : ''], 0);
          }
        });
      });
    })();
  </script>
</body>
</html>

