<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Handle contact form submission (PHPMailer via SMTP)
$contactSuccess = false;
$contactError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = isset($_POST['first_name']) ? trim((string)$_POST['first_name']) : '';
  $last = isset($_POST['last_name']) ? trim((string)$_POST['last_name']) : '';
  $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
  $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';

  if ($first === '' || $email === '' || $message === '') {
    $contactError = 'Please fill in the required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $contactError = 'Please enter a valid email address.';
  } else {
    $fullName = trim($first . ' ' . $last);

    $mailer = new PHPMailer(true);
    try {
      // SMTP settings
      $mailer->isSMTP();
      $mailer->Host = 'smtp.hostinger.com';
      $mailer->SMTPAuth = true;
      $mailer->Username = 'office@bigdeal.property';
      // Prefer env var if present, else placeholder (replace in production)
      $smtpPassword = getenv('SMTP_PASSWORD') ?: 'Brandweave@24';
      $mailer->Password = $smtpPassword;
      $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mailer->Port = 587;

      // Sender & recipient
      $mailer->setFrom('office@bigdeal.property', 'Big Deal Website');
      $mailer->addAddress('office@bigdeal.property');
      $mailer->addReplyTo($email, $fullName !== '' ? $fullName : $email);

      // Content
      $mailer->isHTML(false);
      $mailer->Subject = 'New contact form submission';
      $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
      $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
      $mailer->Body = "Name: {$fullName}\nEmail: {$email}\n\nMessage:\n{$message}\n\n---\nIP: {$ip}\nUA: {$ua}\nTime: " . date('Y-m-d H:i:s');

      $mailer->send();
      $contactSuccess = true;
      $_POST = [];
    } catch (Exception $e) {
      $contactError = 'Message could not be sent. Error: ' . $mailer->ErrorInfo;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Big Deal Ventures</title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/contact.css" />
  <link href="https://fonts.cdnfonts.com/css/sf-pro-display" rel="stylesheet">
                
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>


<body class="contact-page">
  <?php $asset_path = '../assets/'; require_once __DIR__ . '/../components/navbar.php'; ?>
 <section class="hero-banner " style="
    margin-top: 16px;
" >
    <div class="centered">
     
      <h1>Contact Us</h1>
       <div class="breadcrumbs">
        <a href="../index.php">Home</a> > <span>Contact Us</span>
      </div>
    </div>

    <div class=" container-fluid forms px-5">
        <div class="row ">
            <div class="col-md-7">
                <form action="" method="post" class="contact-form" autocomplete="on" novalidate>
                    <?php if ($contactSuccess): ?>
                      <div class="alert alert-success" role="alert">Thank you! Your message has been sent.</div>
                    <?php elseif ($contactError !== ''): ?>
                      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <div class=" row g-3">
                        <div class="col-md-6">
                            <input type="text" name="first_name" placeholder="First name" required class="form-control" autocomplete="given-name" autocapitalize="words" spellcheck="false" value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="last_name" placeholder="Last name" class="form-control" autocomplete="family-name" autocapitalize="words" spellcheck="false" value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                        </div>
                    </div>
                    <div class="form-item">
                        <input type="email" name="email" placeholder="email@example.com" required class="form-control" autocomplete="email" inputmode="email" spellcheck="false" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                    <div class="form-item">
                        <textarea name="message" placeholder="Enter your question or message" rows="8" required class="form-control" autocomplete="on"><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-item">
                        <button type="submit" class="btn btn-dark w-100">Submit</button>
                    </div>
                </form>
            </div>
            <div class="col-md-5  ">
                <div class="contact-map shadow rounded-4 overflow-hidden" style="height: 100%;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.5891905270946!2d74.85795507572209!3d12.86978921710093!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba35b42801d3787%3A0x6382971e18a25583!2sBig%20Deal%20Ventures%20India!5e0!3m2!1sen!2sin!4v1759227103128!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map location: Big Deal Ventures India" fetchpriority="low"></iframe>
                    
                </div>
            </div>
        </div>
    </div>
  </section>

  <!-- Contact Information Section -->
  <section class="contact-info-section py-5">
    <div class="container">
      <div class="row g-4 justify-content-center">
        <div class="col-12 col-md-3 contact-info-item">
          <div class="d-flex align-items-center gap-3">
            <div class="contact-icon-wrapper">
              <i class="fas fa-phone contact-icon"></i>
            </div>
            <div class="contact-info-text">
              <h6 class="contact-info-title mb-1">Call Now</h6>
              <a href="tel:+919901805505" class="contact-info-detail">+91 99018 05505</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-3 contact-info-item">
          <div class="d-flex align-items-center gap-3">
            <div class="contact-icon-wrapper">
              <i class="fas fa-map-marker-alt contact-icon"></i>
            </div>
            <div class="contact-info-text">
              <h6 class="contact-info-title mb-1">Location</h6>
              <p class="contact-info-detail mb-0">Kankanady, Mangaluru, Karnataka 575002</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-3 contact-info-item">
          <div class="d-flex align-items-center gap-3">
            <div class="contact-icon-wrapper">
              <i class="fas fa-envelope contact-icon"></i>
            </div>
            <div class="contact-info-text">
              <h6 class="contact-info-title mb-1">Email</h6>
              <a href="mailto:office@bigdeal.property" class="contact-info-detail">office@bigdeal.property</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-3 contact-info-item">
          <div class="d-flex align-items-center gap-3">
            <div class="contact-icon-wrapper">
              <i class="fab fa-whatsapp contact-icon whatsapp-icon"></i>
            </div>
            <div class="contact-info-text">
              <h6 class="contact-info-title mb-1">WhatsApp</h6>
              <a href="https://wa.me/919901805505?text=Hi! I'm interested in your properties. Please provide more information." class="contact-info-detail" target="_blank">Chat with us</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include '../components/search-banner.php'; ?>

    <?php include '../components/process.php';?> 
<!-- contact  -->
<?php include '../components/letsconnect.php'; ?>

<?php include '../components/faq.php'; ?>

  <!-- footer -->
<?php include '../components/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="../assets/js/scripts.js" defer></script>
</body>
</html>
