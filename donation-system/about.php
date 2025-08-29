<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'About - ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 30px 0;">
  <section>
    <h1 style="margin-bottom: 10px;">About Us</h1>
    <p style="color:#666;max-width:800px;margin-bottom:16px;">
      We empower nonprofits and communities by providing a simple, secure donation platform.
      Our mission is to connect donors with causes that matter and to maximize impact through
      transparency and great user experience.
    </p>
    <ul style="color:#444;line-height:1.8;margin:0 0 24px 18px;">
      <li>Secure payments and data protection</li>
      <li>Transparent campaign progress</li>
      <li>Easy, fast, and mobile-friendly donations</li>
    </ul>
    <a href="campaigns.php" class="btn btn-primary">Explore Campaigns</a>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
