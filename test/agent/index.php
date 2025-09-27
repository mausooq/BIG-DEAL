<?php
  // Set the asset path for this page
  $asset_path = '../assets/';
  $site_base_path = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Big Deal Ventures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $asset_path; ?>css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="agent-page">
    <!-- Include Navbar -->
    <?php include '../components/navbar.php'; ?>

    <!-- Coming Soon Section -->
    <section class="coming-soon-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 col-12">
                    <div class="coming-soon-content text-center">
                        <div class="coming-soon-icon">
                            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H19V9Z" fill="#cc1a1a"/>
                            </svg>
                        </div>
                        <h1 class="coming-soon-title">Agent Portal</h1>
                        <h2 class="coming-soon-subtitle">Coming Soon</h2>
                        <p class="coming-soon-description">
                            We're working hard to bring you a comprehensive agent portal that will help you manage your properties, 
                            track leads, and grow your business. Stay tuned for updates!
                        </p>
                        <div class="coming-soon-features">
                            <div class="feature-item">
                                <div class="feature-icon">🏠</div>
                                <h4>Property Management</h4>
                                <p>Manage all your listings in one place</p>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">📊</div>
                                <h4>Analytics Dashboard</h4>
                                <p>Track your performance and leads</p>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">💬</div>
                                <h4>Client Communication</h4>
                                <p>Stay connected with your clients</p>
                            </div>
                        </div>
                        <div class="coming-soon-actions">
                            <a href="<?php echo $site_base_path; ?>contact/" class="btn btn-primary">Get Notified</a>
                            <a href="<?php echo $site_base_path; ?>" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
