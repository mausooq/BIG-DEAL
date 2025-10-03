<?php
require_once __DIR__ . '/../config/config.php';

// Handle AJAX enquiry submit (stay on same page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_enquiry') {
  header('Content-Type: application/json');
  try {
    $mysqli = getMysqliConnection();
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phno']) ? trim($_POST['phno']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($propertyId <= 0 || $name === '' || ($email === '' && $phone === '')) {
      echo json_encode(['success' => false, 'message' => 'Please provide your name and at least one contact (phone or email).']);
      exit;
    }

    $stmt = $mysqli->prepare('INSERT INTO enquiries (property_id, name, email, phone, message) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
      echo json_encode(['success' => false, 'message' => 'Could not prepare statement.']);
      exit;
    }
    $stmt->bind_param('issss', $propertyId, $name, $email, $phone, $message);
    $ok = $stmt->execute();
    if ($ok) {
      // Create admin notification about the new enquiry with property token for linking
      $propTitle = '';
      if ($propertyId > 0) {
        $pstmt = $mysqli->prepare('SELECT title FROM properties WHERE id = ? LIMIT 1');
        if ($pstmt) { $pstmt->bind_param('i', $propertyId); $pstmt->execute(); $pstmt->bind_result($propTitle); $pstmt->fetch(); $pstmt->close(); }
      }
      $safeName = trim($name);
      // Format: New enquiry from {Name} — [PID:ID] {Property Title}
      $msg = sprintf('New enquiry from %s — [PID:%d] %s', $safeName, (int)$propertyId, $propTitle ?: ('Property #' . (int)$propertyId));
      $nstmt = $mysqli->prepare('INSERT INTO notifications (message) VALUES (?)');
      if ($nstmt) { $nstmt->bind_param('s', $msg); $nstmt->execute(); $nstmt->close(); }
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to save enquiry.']);
    }
    exit;
  } catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
  }
}

// Get property ID from URL parameter
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$propertyId) {
  header('Location: index.php');
  exit;
}


// Fetch property details with category and images
$mysqli = getMysqliConnection();
$property = null;
$propertyImages = [];
$relatedProperties = [];
$propLocation = null;

// Fetch main property details
$query = "SELECT p.*, c.name as category_name 
          FROM properties p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ? AND p.status = 'Available'";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $property = $result->fetch_assoc();

  // Fetch all images for this property
  $imageQuery = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY id";
  $imageStmt = $mysqli->prepare($imageQuery);
  $imageStmt->bind_param("i", $propertyId);
  $imageStmt->execute();
  $imageResult = $imageStmt->get_result();

  while ($row = $imageResult->fetch_assoc()) {
    $propertyImages[] = $row['image_url'];
  }

  // Fetch related properties (same category + closest matching location, excluding current property)
  // Prefer match by town, else city, else district, else state, else only category.
  $relatedQuery = "SELECT p.*, c.name as category_name,
                     (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as main_image
                   FROM properties p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN properties_location pl2
                     ON pl2.id = (SELECT id FROM properties_location WHERE property_id = p.id ORDER BY id DESC LIMIT 1)
                   WHERE p.status = 'Available' AND p.id != ? AND p.category_id = ?";

  $bindTypes = "ii"; // p.id != ?, category_id
  $bindValues = [ $propertyId, $property['category_id'] ];

  if ($propLocation && !empty($propLocation['town_id'])) {
    $relatedQuery .= " AND pl2.town_id = ?";
    $bindTypes .= "i";
    $bindValues[] = (int)$propLocation['town_id'];
  } elseif ($propLocation && !empty($propLocation['city_id'])) {
    $relatedQuery .= " AND pl2.city_id = ?";
    $bindTypes .= "i";
    $bindValues[] = (int)$propLocation['city_id'];
  } elseif ($propLocation && !empty($propLocation['district_id'])) {
    $relatedQuery .= " AND pl2.district_id = ?";
    $bindTypes .= "i";
    $bindValues[] = (int)$propLocation['district_id'];
  } elseif ($propLocation && !empty($propLocation['state_id'])) {
    $relatedQuery .= " AND pl2.state_id = ?";
    $bindTypes .= "i";
    $bindValues[] = (int)$propLocation['state_id'];
  }

  $relatedQuery .= " ORDER BY p.created_at DESC LIMIT 3";

  $relatedStmt = $mysqli->prepare($relatedQuery);
  if ($relatedStmt) {
    $relatedStmt->bind_param($bindTypes, ...$bindValues);
  }
  $relatedStmt->execute();
  $relatedResult = $relatedStmt->get_result();

  while ($row = $relatedResult->fetch_assoc()) {
    $relatedProperties[] = $row;
  }

  // Fetch properties_location with human-readable names (latest record)
  $locStmt = $mysqli->prepare(
    "SELECT pl.pincode,
            pl.state_id, pl.district_id, pl.city_id, pl.town_id,
            s.name AS state_name,
            d.name AS district_name,
            c.name AS city_name,
            t.name AS town_name
     FROM properties_location pl
     LEFT JOIN states s ON pl.state_id = s.id
     LEFT JOIN districts d ON pl.district_id = d.id
     LEFT JOIN cities c ON pl.city_id = c.id
     LEFT JOIN towns t ON pl.town_id = t.id
     WHERE pl.property_id = ?
     ORDER BY pl.id DESC
     LIMIT 1"
  );
  if ($locStmt) {
    $locStmt->bind_param("i", $propertyId);
    $locStmt->execute();
    $locRes = $locStmt->get_result();
    if ($locRes && $locRes->num_rows > 0) {
      $propLocation = $locRes->fetch_assoc();
    }
    $locStmt->close();
  }
} else {
  header('Location: index.php');
  exit;
}

// Function to format price in Lakhs/Crores
function formatPrice($price)
{
  if ($price >= 10000000) {
    return '₹' . round($price / 10000000, 1) . ' Cr';
  } elseif ($price >= 100000) {
    return '₹' . round($price / 100000, 1) . ' L';
  }
  return '₹' . number_format($price);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title><?php echo htmlspecialchars($property['title']); ?> - Big Deal Ventures</title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/productdetail.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>

<body class="product-page">
  <?php $asset_path = '../assets/';
  require_once __DIR__ . '/../components/navbar.php'; ?>



  <div class=" container">
    <div class="row mt-5">
      <div class=" col-md-8">
        <div class="property-main-image" style="position: relative;">
          <img id="mainImage" src="<?php echo !empty($propertyImages) ? '../uploads/properties/' . $propertyImages[0] : '../assets/images/prop/prop6.png'; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" />
          <a href="../products/?listing=<?php echo urlencode($property['listing_type']); ?>" onclick="filterByListingFromDetails(event, '<?php echo htmlspecialchars($property['listing_type']); ?>')" class="listing-type-badge" style="position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.35); color: rgb(0, 0, 0); padding: 6px 12px; border: 1px solid rgba(0, 0, 0, 0.55); border-radius: 20px; font-size: 12px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.12); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); display: inline-block; text-decoration: none; cursor: pointer;">
            <?php echo htmlspecialchars($property['listing_type']); ?>
          </a>
        </div>
        <div class="thumbs-carousel">
          <button class="thumbs-nav prev" type="button" aria-label="Previous" id="thumbPrev">‹</button>
          <div class="thumbs-viewport">
            <div class="thumbs-track" id="thumbsTrack">
              <?php if (!empty($propertyImages)): ?>
                <?php foreach ($propertyImages as $index => $image): ?>
                  <img src="../uploads/properties/<?php echo $image; ?>" data-index="<?php echo $index; ?>" alt="Thumbnail <?php echo $index + 1; ?>" style="cursor: pointer;" />
                <?php endforeach; ?>
              <?php else: ?>
                <img src="../assets/images/prop/prop6.png" alt="Default image" />
              <?php endif; ?>
            </div>
          </div>
          <button class="thumbs-nav next" type="button" aria-label="Next" id="thumbNext">›</button>
        </div>
      </div>

      <div class=" col-md-4 property2-title">
        <h2><?php echo htmlspecialchars($property['title']); ?></h2>
        <div class="property-details2">
          <div class="detail-box">
            <span class="plabel">Facing</span>
            <span class="pvalue"><?php echo htmlspecialchars($property['facing']); ?></span>
          </div>
          <div class="detail-box">
            <span class="plabel">Configuration</span>
            <span class="pvalue"><?php echo htmlspecialchars($property['configuration']); ?></span>
          </div>
          <div class="detail-box">
            <span class="plabel">Area</span>
            <span class="pvalue"><?php echo number_format($property['area']); ?> Sq.ft</span>
          </div>
          <div class="detail-box">
            <span class="plabel">Balcony</span>
            <span class="pvalue"><?php echo $property['balcony']; ?></span>
          </div>
          <div class="detail-box">
            <span class="plabel">Parking</span>
            <span class="pvalue"><?php echo htmlspecialchars($property['parking']); ?></span>
          </div><br>
        </div>
        <div class="detail-row">
          <div class="detail-box2">
            <span class="plabel">Type of Ownership</span>
            <span class="pvalue"><?php echo htmlspecialchars($property['ownership_type']); ?></span>
          </div>
          <div class="detail-box2">
            <span class="plabel">Furnished Status</span>
            <span class="pvalue"><?php echo htmlspecialchars($property['furniture_status']); ?></span>
          </div>
        </div>



        <div class="property-map">
          <?php if (!empty($property['map_embed_link'])): ?>
            <iframe src="<?php echo htmlspecialchars($property['map_embed_link']); ?>"
              width="100%" height="250" style="border:0; pointer-events:none;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <?php else: ?>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.58734013592!2d74.85801117481817!3d12.869908517100171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba35a3245f37f65%3A0x66a8be45c5d10bc9!2sKankanady%20gate!5e0!3m2!1sen!2sin!4v1757567792855!5m2!1sen!2sin"
              width="100%" height="250" style="border:0; pointer-events:none;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <?php endif; ?>
        </div>

        <!-- Share Button -->
        <div class="property-actions" style="margin-top: 20px;">
          <div class="btn-grp" style="display: flex; gap: 10px;">
            <button
              class="btn btn-share"
              onclick="sharePropertyFromBtn(this, <?php echo $property['id']; ?>)"
              data-id="<?php echo (int)$property['id']; ?>"
              data-title="<?php echo htmlspecialchars($property['title']); ?>"
              data-desc="<?php echo htmlspecialchars($property['description']); ?>"
              data-config="<?php echo htmlspecialchars($property['configuration']); ?>"
              data-price="<?php echo formatPrice($property['price']); ?>"
              data-area="<?php echo number_format($property['area']); ?>"
              data-furniture="<?php echo htmlspecialchars($property['furniture_status']); ?>"
              data-location="<?php echo htmlspecialchars($property['location']); ?>"
              data-image="<?php echo !empty($propertyImages[0]) ? '../uploads/properties/' . $propertyImages[0] : ''; ?>"
            >Share</button>
            <button class="btn btn-contact" onclick="contactProperty(<?php echo $property['id']; ?>)">Contact us</button>
          </div>
        </div>
      </div>

    </div>
  </div>


  <section class=" container">
    <div class="row more">
      <div class="col-md-8 ">

        <h1>
          More Details
        </h1>
        <table>
          <tr>
            <td class="tmain">Price</td>
            <td class="tdesc"><?php echo formatPrice($property['price']); ?></td>
          </tr>
          <tr>
            <td class="tmain">Landmarks</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['landmark']); ?></td>
          </tr>
          <tr>
            <td class="tmain">Type of Ownership</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['ownership_type']); ?></td>
          </tr>
          
          <tr>
            <td class="tmain">Category</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['category_name']); ?></td>
          </tr>
          <tr>
            <td class="tmain">Status</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['status']); ?></td>
          </tr>
          <?php if ($propLocation): ?>
          <tr>
            <td class="tmain">Pincode</td>
            <td class="tdesc"><?php echo htmlspecialchars($propLocation['pincode']); ?></td>
          </tr>
          <tr>
            <td class="tmain">State</td>
            <td class="tdesc"><?php echo htmlspecialchars($propLocation['state_name'] ?? $propLocation['state_id']); ?></td>
          </tr>
          <tr>
            <td class="tmain">District</td>
            <td class="tdesc"><?php echo htmlspecialchars($propLocation['district_name'] ?? $propLocation['district_id']); ?></td>
          </tr>
          <tr>
            <td class="tmain">City</td>
            <td class="tdesc"><?php echo htmlspecialchars($propLocation['city_name'] ?? $propLocation['city_id']); ?></td>
          </tr>
          <tr>
            <td class="tmain">Town</td>
            <td class="tdesc"><?php echo htmlspecialchars($propLocation['town_name'] ?? $propLocation['town_id']); ?></td>
          </tr>
          <?php endif; ?>
        </table>
        <a href="#" class="tminfo">More Information <span><svg class="pdarrow-table" width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 13.2L7.4 8.6L6 10L12 16L18 10L16.6 8.6L12 13.2Z" fill="#EC1F2B"/></svg></span></a>

        <div class="property-desc">
          <h1>Description</h1>
          <p class="pdesc">
            <?php echo !empty($property['description']) ? htmlspecialchars($property['description']) : 'No description available for this property.'; ?>
          </p>
          <?php if (!empty($property['description']) && strlen($property['description']) > 200): ?>
            <a href="#" class="view-more" onclick="toggleDescription(event)" aria-expanded="false">
              <span class="label">View More</span>
              <span>
                <svg class="pdarrow-desc" width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 13.2L7.4 8.6L6 10L12 16L18 10L16.6 8.6L12 13.2Z" fill="#EC1F2B"/></svg>
              </span>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-md-4">
        <h4 style="margin: 10px 0 6px;">Enquiry</h4>
        <form class="visit-form" id="enquiryForm">
          <label for="name">Name</label>
          <input type="text" id="name" placeholder="Jane" name="name">

          <label for="email">Email (optional)</label>
          <input type="email" id="email" placeholder="jane@example.com" name="email">

          <label for="phno">Phone Number</label>
          <input type="tel" id="phno" placeholder="Phone Number" name="phno">

          <label for="message">Your message</label>
          <textarea id="message" placeholder="Enter your question or message" name="message" rows="5"></textarea>

          <button type="submit" class="visit-btn">Schedule a Visit</button>
          <div id="enquiryStatus" style="margin-top:8px;font-size:14px;display:none;"></div>
        </form>

      </div>

    </div>
  </section>

  

  <section class="container">
    <div class="property-title-section text-left">
      <h1>
        People who viewed this property also liked
      </h1>

    </div>
    <!-- Properties Grid -->
    <div class="row ">
      <?php if (!empty($relatedProperties)): ?>
        <?php foreach ($relatedProperties as $relatedProperty): ?>
          <div class="col-md-4">
            <div class="card property-card" onclick="goToPropertyDetails(<?php echo $relatedProperty['id']; ?>)" style="cursor: pointer;">
              <div style="position: relative;">
                <img src="<?php echo !empty($relatedProperty['main_image']) ? '../uploads/properties/' . $relatedProperty['main_image'] : '../assets/images/prop/prop1.png'; ?>"
                  alt="<?php echo htmlspecialchars($relatedProperty['title']); ?>" class="propimg">
                <a href="../products/?listing=<?php echo urlencode($relatedProperty['listing_type']); ?>" onclick="event.stopPropagation(); filterByListingFromDetails(event, '<?php echo htmlspecialchars($relatedProperty['listing_type']); ?>')" class="listing-type-badge" style="position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.35); color: rgb(0, 0, 0); padding: 6px 12px; border: 1px solid rgba(0, 0, 0, 0.55); border-radius: 20px; font-size: 12px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.12); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); display: inline-block; text-decoration: none; cursor: pointer;">
                  <?php echo htmlspecialchars($relatedProperty['listing_type']); ?>
                </a>
              </div>
              <div class="card-body">
                <div class="card-title"><?php echo htmlspecialchars($relatedProperty['title']); ?></div>
                <div class="property-attrs">
                  <div class="property-attr"><img src="../assets/images/icon/home_dark.svg" class="svg"> <?php echo htmlspecialchars($relatedProperty['configuration']); ?></div>
                  <div class="property-attr"><img src="../assets/images/icon/park_dark.svg" class="svg"> <?php echo $relatedProperty['parking']; ?></div>
                  <div class="property-attr"><img src="../assets/images/icon/sqft_dark.svg" class="svg"> <?php echo number_format($relatedProperty['area']); ?> sq. ft.</div>
                  <div class="property-attr"><img src="../assets/images/icon/terrace_dark.svg" class="svg"> <?php echo $relatedProperty['balcony']; ?></div>
                  <div class="property-attr"><img src="../assets/images/icon/sofa_dark.svg" class="svg"> <?php echo htmlspecialchars($relatedProperty['furniture_status']); ?></div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <p>No related properties found.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- contact  -->
  <?php include '../components/letsconnect.php'; ?>

  <?php $asset_path = '../assets/';
  require_once __DIR__ . '/../components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/scripts.js"></script>

  <script>
    // Navigate to products listing page with listing= param
    function filterByListingFromDetails(e, listing) {
      if (e && e.preventDefault) e.preventDefault();
      try {
        var url = new URL(window.location.origin + '/BIG-DEAL/products/');
        url.searchParams.set('listing', listing);
        // mutual exclusivity with category param
        url.searchParams.delete('category');
        window.location.href = url.toString();
      } catch (_) {
        // Fallback relative navigation
        window.location.href = '../products/?listing=' + encodeURIComponent(listing);
      }
    }
    function changeMainImage(imageSrc) {
      document.getElementById('mainImage').src = imageSrc;
    }

    // Simple carousel for main image using propertyImages array
    var gallerySources = <?php echo json_encode(array_map(function($p){ return '../uploads/properties/' . $p; }, $propertyImages)); ?>;
    var currentMainIndex = 0;
    function syncIndexBySrc() {
      var src = document.getElementById('mainImage').getAttribute('src');
      var idx = gallerySources.indexOf(src);
      if (idx >= 0) currentMainIndex = idx; else currentMainIndex = 0;
    }
    // Thumbnail carousel controls
    (function(){
      var track = document.getElementById('thumbsTrack');
      var btnPrev = document.getElementById('thumbPrev');
      var btnNext = document.getElementById('thumbNext');
      if (!track || !btnPrev || !btnNext) return;

      function getThumbWidth(){
        var first = track.querySelector('img');
        if (!first) return 0;
        var style = window.getComputedStyle(first);
        var width = first.getBoundingClientRect().width;
        var marginRight = parseFloat(style.marginRight || '0');
        return width + marginRight;
      }

      function setMainFromFirst(){
        var first = track.querySelector('img');
        if (first) changeMainImage(first.src);
      }

      // Click thumb to change main
      track.addEventListener('click', function(e){
        var t = e.target;
        if (t && t.tagName === 'IMG') changeMainImage(t.src);
      });

      var isAnimating = false;
      function slideNext(){
        if (isAnimating) return;
        var shift = getThumbWidth();
        if (!shift) return;
        isAnimating = true;
        track.style.transition = 'transform 250ms ease';
        track.style.transform = 'translateX(' + (-shift) + 'px)';
        track.addEventListener('transitionend', function handler(){
          track.removeEventListener('transitionend', handler);
          track.style.transition = 'none';
          track.style.transform = 'translateX(0)';
          if (track.firstElementChild) track.appendChild(track.firstElementChild);
          setMainFromFirst();
          // allow next frame to re-enable transition
          requestAnimationFrame(function(){ isAnimating = false; });
        });
      }
      function slidePrev(){
        if (isAnimating) return;
        var shift = getThumbWidth();
        if (!shift) return;
        isAnimating = true;
        if (track.lastElementChild) track.insertBefore(track.lastElementChild, track.firstElementChild);
        track.style.transition = 'none';
        track.style.transform = 'translateX(' + (-shift) + 'px)';
        requestAnimationFrame(function(){
          track.style.transition = 'transform 250ms ease';
          track.style.transform = 'translateX(0)';
        });
        track.addEventListener('transitionend', function handler(){
          track.removeEventListener('transitionend', handler);
          setMainFromFirst();
          isAnimating = false;
        });
      }

      btnNext.addEventListener('click', slideNext);
      btnPrev.addEventListener('click', slidePrev);

      // Optional auto-advance
      // setInterval(slideNext, 4000);
    })();

    function openGalleryModal() {
      var modal = new bootstrap.Modal(document.getElementById('galleryModal'));
      modal.show();
    }

    function goToPropertyDetails(propertyId) {
      window.location.href = 'product-details.php?id=' + propertyId;
    }

    // Share full card: title, description, attributes and image
    async function sharePropertyFromBtn(btn, propertyId) {
        try {
            const title = btn.getAttribute('data-title') || 'Property';
            const desc = btn.getAttribute('data-desc') || '';
            const config = btn.getAttribute('data-config') || '';
            const price = btn.getAttribute('data-price') || '';
            const area = btn.getAttribute('data-area') || '';
            const furniture = btn.getAttribute('data-furniture') || '';
            const location = btn.getAttribute('data-location') || '';
            const imageUrl = btn.getAttribute('data-image') || '';

            // Generate proper sharing URL - use current page URL as base and modify it
            const currentUrl = window.location.href;
            const urlParts = currentUrl.split('?');
            const baseUrl = urlParts[0]; // Get URL without query parameters
            const projectPath = baseUrl.replace('/products/product-details.php', '');
            const detailsUrl = projectPath + '/products/product-details.php?id=' + propertyId;

            // Build share text
            const lines = [
                `${title}`,
                location ? `Location: ${location}` : '',
                config ? `Configuration: ${config}` : '',
                area ? `Area: ${area} sq.ft` : '',
                price ? `Price: ${price}` : '',
                furniture ? `Furniture: ${furniture}` : '',
                desc ? `\n${desc}` : '',
                `\nView details: ${detailsUrl}`
            ].filter(Boolean);
            const text = lines.join('\n');

            // Try Web Share Level 2 with files (if supported and image available)
            if (navigator.share) {
                const shareData = { title, text, url: detailsUrl };

                // Attempt image share if fetchable and File constructor exists
                if (imageUrl && window.File && window.Blob) {
                    try {
                        const absImageUrl = new URL(imageUrl, window.location.href).href;
                        const res = await fetch(absImageUrl);
                        const blob = await res.blob();
                        const file = new File([blob], 'property.jpg', { type: blob.type || 'image/jpeg' });
                        if ('files' in navigator.canShare ? navigator.canShare({ files: [file] }) : false) {
                            shareData.files = [file];
                            delete shareData.url; // with files, keep text clean
                        }
                    } catch (_) {
                        // Include image URL in text if attachment fails
                        shareData.text = text + (imageUrl ? '\nImage: ' + new URL(imageUrl, window.location.href).href : '');
                    }
                }

                await navigator.share(shareData);
                return;
            }

            // Fallback: copy composed text + link (+image URL) to clipboard
            const absImageUrl = imageUrl ? new URL(imageUrl, window.location.href).href : '';
            await navigator.clipboard.writeText(text + (absImageUrl ? '\nImage: ' + absImageUrl : ''));
            alert('Property details copied. Paste to share!');
        } catch (err) {
            try {
                // Last fallback: open details page
                window.open('/products/product-details.php?id=' + propertyId, '_blank');
            } catch (_) {}
        }
    }

    function contactProperty(propertyId) {
        // Call the phone number directly
        const phoneNumber = '80888 55555';
        window.location.href = 'tel:' + phoneNumber;
    }

    function toggleDescription(e) {
      if (e && e.preventDefault) e.preventDefault();
      var link = e ? e.currentTarget : null;
      var p = document.querySelector('.property-desc .pdesc');
      if (!p) return;
      var expanded = p.classList.toggle('expanded');
      if (link) {
        link.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        var label = link.querySelector('.label');
        if (label) label.textContent = expanded ? 'View Less' : 'View More';
        var icon = link.querySelector('.pdarrow');
        if (icon) {
          icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
          icon.style.transition = 'transform 150ms ease';
        }
      }
    }

    // Collapse/expand for More Details table
    (function(){
      document.addEventListener('DOMContentLoaded', function(){
        var moreSection = document.querySelector('.more');
        if (!moreSection) return;
        var table = moreSection.querySelector('table');
        var toggleLink = moreSection.querySelector('.tminfo');
        if (!table || !toggleLink) return;

        var rows = Array.prototype.slice.call(table.querySelectorAll('tr'));
        if (!rows.length) return;

        var COLLAPSED_COUNT = 4; // show first 4 by default
        var expanded = false;

        function applyState(){
          rows.forEach(function(row, idx){
            if (!expanded && idx >= COLLAPSED_COUNT) {
              row.style.display = 'none';
            } else {
              row.style.display = '';
            }
          });
          toggleLink.firstChild.nodeValue = expanded ? 'Less Information ' : 'More Information ';
          var icon = toggleLink.querySelector('.pdarrow');
          if (icon) {
            icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
            icon.style.transition = 'transform 150ms ease';
          }
        }

        // Initialize collapsed state
        applyState();

        toggleLink.addEventListener('click', function(ev){
          ev.preventDefault();
          expanded = !expanded;
          applyState();
        });
      });
    })();

    // AJAX submit for enquiry form (stay on same page) + Gallery viewer wiring
    document.addEventListener('DOMContentLoaded', function () {
      // Build gallery array for viewer
      var gallery = Array.prototype.map.call(document.querySelectorAll('#modalGrid img'), function (el) { return el.getAttribute('src'); });
      var currentIndex = 0;
      var modalGrid = document.getElementById('modalGrid');
      var modalViewer = document.getElementById('modalViewer');
      var viewerImg = document.getElementById('viewerImg');
      var viewerPrev = document.getElementById('viewerPrev');
      var viewerNext = document.getElementById('viewerNext');
      var viewerBack = document.getElementById('viewerBack');

      function showViewer(index) {
        if (!gallery.length) return;
        currentIndex = (index + gallery.length) % gallery.length;
        viewerImg.src = gallery[currentIndex];
        modalGrid.classList.add('d-none');
        modalViewer.classList.remove('d-none');
      }
      function showGrid() {
        modalViewer.classList.add('d-none');
        modalGrid.classList.remove('d-none');
      }
      if (modalGrid) {
        modalGrid.addEventListener('click', function (e) {
          var t = e.target;
          if (t && t.tagName === 'IMG' && t.dataset.index !== undefined) {
            showViewer(parseInt(t.dataset.index, 10));
          }
        });
      }
      if (viewerPrev) viewerPrev.addEventListener('click', function () { showViewer(currentIndex - 1); });
      if (viewerNext) viewerNext.addEventListener('click', function () { showViewer(currentIndex + 1); });
      if (viewerBack) viewerBack.addEventListener('click', showGrid);

      var form = document.getElementById('enquiryForm');
      if (!form) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var statusEl = document.getElementById('enquiryStatus');
        statusEl.style.display = 'none';
        var formData = new FormData(form);
        formData.append('action', 'submit_enquiry');
        formData.append('property_id', '<?php echo $propertyId; ?>');
        fetch('', { method: 'POST', body: formData })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            statusEl.style.display = 'block';
            if (data && data.success) {
              statusEl.style.color = '#16a34a';
              statusEl.textContent = 'Thanks! We\'ll get back to you shortly.';
              form.reset();
            } else {
              statusEl.style.color = '#b91c1c';
              statusEl.textContent = (data && data.message) ? data.message : 'Something went wrong. Please try again.';
            }
          })
          .catch(function () {
            statusEl.style.display = 'block';
            statusEl.style.color = '#b91c1c';
            statusEl.textContent = 'Network error. Please try again.';
          });
      });
    });
  </script>
</body>

</html>