<?php
require_once '../config/config.php';

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

  // Fetch related properties (same category, excluding current property)
  $relatedQuery = "SELECT p.*, c.name as category_name,
                     (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as main_image
                     FROM properties p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.category_id = ? AND p.id != ? AND p.status = 'Available'
                     ORDER BY p.created_at DESC 
                     LIMIT 3";

  $relatedStmt = $mysqli->prepare($relatedQuery);
  $relatedStmt->bind_param("ii", $property['category_id'], $propertyId);
  $relatedStmt->execute();
  $relatedResult = $relatedStmt->get_result();

  while ($row = $relatedResult->fetch_assoc()) {
    $relatedProperties[] = $row;
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
  <link rel="icon" href="../assets/images/logo.png" type="image/png">
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
        <div class="property-main-image">
          <img id="mainImage" src="<?php echo !empty($propertyImages) ? '../../uploads/properties/' . $propertyImages[0] : '../assets/images/prop/prop6.png'; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" />
        </div>
        <div class="property-thumbnails">
          <?php if (!empty($propertyImages)): ?>
            <?php foreach (array_slice($propertyImages, 0, 3) as $index => $image): ?>
              <img src="../../uploads/properties/<?php echo $image; ?>"
                alt="Thumbnail <?php echo $index + 1; ?>"
                onclick="changeMainImage(this.src)"
                style="cursor: pointer;" />
            <?php endforeach; ?>
            <?php if (count($propertyImages) > 3): ?>
              <div class="thumbnail-more" onclick="toggleMoreImages()" style="cursor: pointer;">
                <img src="../../uploads/properties/<?php echo $propertyImages[3]; ?>" alt="More images" />
                <span class="overlay-text"><?php echo count($propertyImages) - 3; ?>+</span>
                <span class="overlay-subtext">View Gallery</span>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <img src="../assets/images/prop/prop6.png" alt="Default image" />
          <?php endif; ?>
        </div>
        <?php if (!empty($propertyImages) && count($propertyImages) > 3): ?>
        <div id="moreImages" class="more-images" style="display:none;">
          <div class="container-fluid px-0">
            <div class="row g-2">
              <?php foreach ($propertyImages as $img): ?>
                <div class="col-4 col-md-3">
                  <img src="../../uploads/properties/<?php echo $img; ?>" alt="Property image" class="more-img" />
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
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
              width="428px" height="350px" style="border:0; pointer-events:none;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <?php else: ?>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.58734013592!2d74.85801117481817!3d12.869908517100171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba35a3245f37f65%3A0x66a8be45c5d10bc9!2sKankanady%20gate!5e0!3m2!1sen!2sin!4v1757567792855!5m2!1sen!2sin"
              width="428px" height="350px" style="border:0; pointer-events:none;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <?php endif; ?>
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
            <td class="tmain">Address</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['location']); ?></td>
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
            <td class="tmain">Listing Type</td>
            <td class="tdesc"><?php echo htmlspecialchars($property['listing_type']); ?></td>
          </tr>
        </table>
        <a href="#" class="tminfo">More Information <span><img src="../assets/images/icon/parrowdown.svg" alt="arrow down" class="pdarrow"></span></a>

        <div class="property-desc">
          <h1>Description</h1>
          <p class="pdesc">
            <?php echo !empty($property['description']) ? htmlspecialchars($property['description']) : 'No description available for this property.'; ?>
          </p>
          <?php if (!empty($property['description']) && strlen($property['description']) > 200): ?>
            <a href="#" class="view-more" onclick="toggleDescription()">
              View More
              <span>
                <img src="../assets/images/icon/parrowdown.svg" alt="arrow down" class="pdarrow">
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
              <img src="<?php echo !empty($relatedProperty['main_image']) ? '../../uploads/properties/' . $relatedProperty['main_image'] : '../assets/images/prop/prop1.png'; ?>"
                alt="<?php echo htmlspecialchars($relatedProperty['title']); ?>" class="propimg">
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
    function changeMainImage(imageSrc) {
      document.getElementById('mainImage').src = imageSrc;
    }

    function toggleMoreImages() {
      var el = document.getElementById('moreImages');
      if (!el) return;
      el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }

    function goToPropertyDetails(propertyId) {
      window.location.href = 'product-details.php?id=' + propertyId;
    }

    function toggleDescription() {
      // You can implement expand/collapse functionality for description
      alert('Toggle description functionality');
    }

    // AJAX submit for enquiry form (stay on same page)
    document.addEventListener('DOMContentLoaded', function () {
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