<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'Contact - ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 30px 0;">
  <section>
    <h1 style="margin-bottom: 10px;">Contact Us</h1>
    <p style="color:#666;max-width:800px;margin-bottom:16px;">
      We'd love to hear from you. Reach out with questions, feedback, or partnership inquiries.
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
      <div style="border:1px solid #eee;border-radius:8px;padding:16px;background:#fff;">
        <h3 style="margin-top:0;">General</h3>
        <p style="color:#555;margin:6px 0;">Email: <a href="mailto:info@donationsystem.com">info@donationsystem.com</a></p>
        <p style="color:#555;margin:6px 0;">Phone: (123) 456-7890</p>
      </div>
      <div style="border:1px solid #eee;border-radius:8px;padding:16px;background:#fff;">
        <h3 style="margin-top:0;">Support</h3>
        <p style="color:#555;margin:6px 0;">Email: <a href="mailto:support@donationsystem.com">support@donationsystem.com</a></p>
        <p style="color:#555;margin:6px 0;">Hours: Mon–Fri, 9am–5pm</p>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
