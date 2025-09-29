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
    <link rel="icon" href="../assets/images/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $asset_path; ?>css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
    /* Theme alignment & unique layout for Agent page */
    .agent-page { font-family: 'DM Sans', sans-serif; color: #111111; background: #ffffff; }
    .agent-hero { position: relative; padding: 72px 0; overflow: hidden; }
    .agent-hero:before { content: ''; position: absolute; inset: -20% -10% auto -10%; height: 70%; pointer-events: none; }
    .agent-title { font-weight: 700; font-size: 2.75rem; letter-spacing: 1px; margin: 0 0 12px 0; }
    .agent-subtitle { font-weight: 700; font-size: 1.25rem; color: #cc1a1a; margin: 0 0 8px 0; }
    .agent-desc { color: #666666; line-height: 1.7; margin: 0 0 16px 0; max-width: 56ch; }
    .agent-actions { display: flex; gap: 12px; align-items: center; margin-top: 6px; }
    .btn-primary { background-color: #cc1a1a; border-color: #cc1a1a; border-radius: 999px; padding: 10px 18px; }
    .btn-primary:hover { background-color: #b31717; border-color: #b31717; }
    .btn-outline-secondary { color: #111111; border-color: #e9ecef; border-radius: 999px; padding: 10px 18px; }
    .btn-outline-secondary:hover { background: #f8f9fa; border-color: #dee2e6; color: #111111; }
    .agent-visual { position: relative; border-radius: 16px; overflow: hidden;}
    .agent-visual img { width: 100%; height: auto; max-height: 22em; object-fit: contain; display: block; transition: transform 0.6s ease;}
    .agent-visual:hover img { transform: scale(1.03); }
    .agent-feature-strip { margin-top: 36px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .feature-card { background: #ffffff; border: 1px solid #e9ecef; border-radius: 12px; padding: 16px; display: flex; gap: 12px; align-items: center; }
    .feature-badge { width: 48px; height: 48px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(204,26,26,0.08); color: #cc1a1a; }
    .feature-card h4 { font-weight: 700; font-size: 1rem; margin: 0; }
    .feature-card p { color: #666666; font-size: 0.925rem; margin: 0; }
    @media (max-width: 992px) { .agent-coming-soon { flex-direction: column-reverse; } .agent-feature-strip { grid-template-columns: 1fr; } .agent-visual img { max-height: 18em; } .agent-title { font-size: 2.25rem; } }
    @media (max-width: 576px) { .agent-coming-soon { flex-direction: column-reverse; } .agent-hero { padding-top: 24px; padding-bottom: 48px; } }
    </style>
</head>
<body class="agent-page">
    <!-- Include Navbar -->
    <?php include '../components/navbar.php'; ?>

    <!-- Agent Hero - Split layout -->
    <section class="agent-hero">
        <div class="container">
            <div class="row align-items-center g-4 agent-coming-soon">
                <div class="col-12 col-lg-7">
                    <div class="mb-2 agent-subtitle">Agent Portal</div>
                    <h1 class="agent-title">Build your real estate business with a powerful platform</h1>
                    <p class="agent-desc">We’re crafting a modern portal to help you manage listings, track leads, and stay connected with clients — all with Big Deal’s refined experience and tools.</p>
                    <div class="agent-actions">
                        <a href="<?php echo $site_base_path; ?>contact/" class="btn btn-primary">Get Notified</a>
                        <a href="<?php echo $site_base_path; ?>" class="btn btn-outline-secondary">Back to Home</a>
                    </div>
                    <div class="agent-feature-strip">
                        <div class="feature-card">
                            <div class="feature-badge">
                                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-7l9 7M5 10v10h14V10M9 20v-6h6v6"/></svg>
                            </div>
                            <div>
                                <h4>Manage Listings</h4>
                                <p>All properties in one place</p>
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-badge">
                                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16v-4M12 16V8M17 16v-7"/></svg>
                            </div>
                            <div>
                                <h4>Analytics</h4>
                                <p>Performance & insights</p>
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-badge">
                                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
                            </div>
                            <div>
                                <h4>Client Chat</h4>
                                <p>Stay in touch easily</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="agent-visual">
                        <img src="<?php echo $asset_path; ?>images/House-searching.gif" alt="Agent Portal Preview">
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
