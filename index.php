<?php
// Maintenance page configuration
$asset_path = '/BIG-DEAL/assets/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Big Deal Ventures | Maintenance</title>
  <link rel="icon" href="<?php echo $asset_path; ?>images/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ---------- General ---------- */
    body {
      margin: 0;
      padding: 0;
      font-family: 'DM Sans', sans-serif;
      color: #2c3e50;
      overflow-x: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    /* ---------- Container ---------- */
    .maintenance-container {
      max-width: 700px;
      width: 90%;
      text-align: center;
      padding: 3rem 2rem;
      transition: transform 0.3s ease;
    }

    /* .maintenance-container:hover {
      transform: translateY(-5px);
    } */

    /* ---------- Animation ---------- */
    .maintenance-gif {
      width: 450px;
      height: 450px;
      overflow: hidden;
      margin: 0 auto 2rem;
      animation: pulse 2s infinite;
    }

    .maintenance-gif img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    /* ---------- Text ---------- */
    .maintenance-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: #e14c4c;
      margin-bottom: 0.6rem;
    }

    .maintenance-subtitle {
      font-size: 1.1rem;
      color: #6c757d;
      line-height: 1.6;
      margin-bottom: 2rem;
    }

    /* ---------- Button ---------- */
    .notify-button {
      background: linear-gradient(135deg, #e14c4c, #ff6b6b);
      color: white;
      border: none;
      padding: 0.9em 2.5em;
      border-radius: 50px;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      box-shadow: 0 5px 15px rgba(225, 76, 76, 0.3);
      display: inline-block;
    }

    .notify-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(225, 76, 76, 0.4);
      color: #fff;
      text-decoration: none;
    }

    /* ---------- Social Links ---------- */
    .social-links {
      margin-top: 2rem;
      display: flex;
      justify-content: center;
      gap: 1.2rem;
      flex-wrap: wrap;
    }

    .social-links a {
      color: #7f8c8d;
      font-size: 1.5rem;
      transition: all 0.3s ease;
    }

    .social-links a:hover {
      color: #e14c4c;
      transform: translateY(-3px);
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 992px) {
      .maintenance-title {
        font-size: 2.2rem;
      }

      .maintenance-subtitle {
        font-size: 1rem;
      }

      .maintenance-container {
        padding: 2.5rem 1.5rem;
      }
    }

    @media (max-width: 768px) {
      .maintenance-gif {
        width: 200px;
        height: 200px;
        margin-bottom: 1.5rem;
      }

      .maintenance-title {
        font-size: 1.9rem;
      }

      .notify-button {
        width: 100%;
        max-width: 300px;
        padding: 0.9em 1.5em;
      }
    }

    @media (max-width: 480px) {
      .maintenance-container {
        padding: 2rem 1rem;
      }

      .maintenance-title {
        font-size: 1.6rem;
      }

      .maintenance-subtitle {
        font-size: 0.95rem;
      }

      .social-links a {
        font-size: 1.3rem;
      }
    }
  </style>
</head>

<body>
  <div class="maintenance-container">
    <div class="maintenance-gif">
      <img src="<?php echo $asset_path; ?>error.gif" alt="Under Maintenance">
    </div>

    <h1 class="maintenance-title">We’ll Be Right Back!</h1>
    <p class="maintenance-subtitle">
      Our website is currently undergoing maintenance.<br>
      Please check back again soon.
    </p>

    <a href="mailto:office@bigdeal.property" class="notify-button">
      <i class="fas fa-envelope"></i> Contact Support
    </a>

    <div class="social-links">
      <?php
        // Ensure DB connection is available
        if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
          if (!function_exists('getMysqliConnection')) {
            $cfg = __DIR__ . '/config/config.php';
            if (file_exists($cfg)) { require_once $cfg; }
          }
          if (function_exists('getMysqliConnection')) {
            try { $mysqli = getMysqliConnection(); } catch (Throwable $e) { $mysqli = null; }
          }
        }

        $socialLinks = [];
        if (isset($mysqli) && $mysqli instanceof mysqli) {
          try {
            if ($res = $mysqli->query("SELECT platform, url FROM social_links")) {
              while ($row = $res->fetch_assoc()) { $socialLinks[] = $row; }
              $res->free();
            }
          } catch (Throwable $e) { /* silently ignore in maintenance page */ }
        }

        // If no social links found in database, show default icons
        if (empty($socialLinks)) {
          echo '<a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>';
          echo '<a href="#" target="_blank"><i class="fab fa-twitter"></i></a>';
          echo '<a href="#" target="_blank"><i class="fab fa-instagram"></i></a>';
          echo '<a href="#" target="_blank"><i class="fab fa-linkedin-in"></i></a>';
        } else {
          // Map platform name to Font Awesome icon class
          $iconMap = [
            'facebook' => 'fab fa-facebook-f',
            'instagram' => 'fab fa-instagram',
            'linkedin' => 'fab fa-linkedin-in',
            'youtube' => 'fab fa-youtube',
            'twitter' => 'fab fa-twitter',
            'x' => 'fab fa-twitter'
          ];

          foreach ($socialLinks as $link) {
            $platform = strtolower(trim((string)($link['platform'] ?? '')));
            $url = trim((string)($link['url'] ?? ''));
            if ($url === '') { continue; }
            $icon = $iconMap[$platform] ?? 'fab fa-link';
            echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">';
            echo '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i></a>';
          }
        }
      ?>
    </div>
  </div>
</body>
</html>
