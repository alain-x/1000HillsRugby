<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Campaigns - ' . SITE_NAME;
include __DIR__ . '/includes/header.php';

$campaigns = getActiveCampaigns();
?>
<main class="container" style="padding: 30px 0;">
  <section>
    <h1 style="margin-bottom: 10px;">Active Campaigns</h1>
    <p style="color:#666;margin-bottom:24px;">Choose a campaign to support and make an impact today.</p>

    <?php if (empty($campaigns)): ?>
      <div class="alert alert-info">No active campaigns at the moment. Please check back soon.</div>
    <?php else: ?>
      <div class="campaign-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;">
        <?php foreach ($campaigns as $c): 
          $goal = (float)($c['goal_amount'] ?? 0);
          $current = (float)($c['current_amount'] ?? 0);
          $pct = $goal > 0 ? min(100, round(($current / $goal) * 100)) : 0;
        ?>
          <article class="campaign-card" style="border:1px solid #eee;border-radius:8px;overflow:hidden;background:#fff;display:flex;flex-direction:column;">
            <div style="padding:16px;flex:1;">
              <h3 style="margin:0 0 8px; font-size:1.1rem;">
                <a href="campaign.php?id=<?= (int)$c['id'] ?>" style="color:#222;"><?= htmlspecialchars($c['title']) ?></a>
              </h3>
              <p style="color:#666; margin:0 0 12px;">
                <?= htmlspecialchars(mb_strimwidth($c['description'], 0, 140, '…')) ?>
              </p>
              <div class="progress" style="background:#f1f1f1;border-radius:6px;height:10px;overflow:hidden;margin-bottom:8px;">
                <div class="progress-bar" style="height:10px;background:#4caf50;width: <?= $pct ?>%;"></div>
              </div>
              <div style="display:flex;justify-content:space-between;color:#555;font-size:0.9rem;">
                <span>Raised: $<?= formatMoney($current) ?></span>
                <span>Goal: $<?= formatMoney($goal) ?></span>
              </div>
            </div>
            <div style="padding:12px 16px;border-top:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
              <span style="font-weight:600;color:#4caf50;"><?= $pct ?>%</span>
              <a class="btn btn-primary" href="campaign.php?id=<?= (int)$c['id'] ?>">Donate</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
