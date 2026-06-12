<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/bookmark.php';

requireLogin();

if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    removeBookmark((int)$_SESSION['user_id'], urldecode($_GET['remove']));
    header('Location: ' . BASE_URL . '/bookmarks.php?removed=1'); exit;
}

$bookmarks  = getUserBookmarks((int)$_SESSION['user_id']);
$removed    = isset($_GET['removed']);
$pageTitle  = 'Saved Articles';
$placeholder = BASE_URL . '/assets/images/placeholder.svg';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-screen-xl px-6" style="padding-top:40px;padding-bottom:80px;">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 class="type-display-xl" style="margin:0 0 4px;">Saved articles</h1>
      <p style="font-size:15px;color:#6a6a6a;margin:0;"><?= count($bookmarks) ?> article<?= count($bookmarks)!==1?'s':'' ?> saved</p>
    </div>
    <a href="<?= BASE_URL ?>/index.php" class="btn-secondary" style="height:40px;font-size:14px;padding:0 20px;">← Back to feed</a>
  </div>

  <?php if ($removed): ?>
  <div class="alert alert-success animate-fade-in" style="margin-bottom:24px;border-radius:10px;">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    Article removed from saved.
  </div>
  <?php endif; ?>

  <?php if (empty($bookmarks)): ?>
  <div style="text-align:center;padding:80px 0;" class="animate-fade-in">
    <div style="width:80px;height:80px;border-radius:50%;background:#f7f7f7;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <svg width="36" height="36" fill="none" stroke="#929292" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
      </svg>
    </div>
    <h2 style="font-size:22px;font-weight:700;color:#222;margin:0 0 8px;">No saved articles yet</h2>
    <p style="font-size:15px;color:#6a6a6a;max-width:360px;margin:0 auto 24px;">Tap the heart icon on any article to save it here for later.</p>
    <a href="<?= BASE_URL ?>/index.php" class="btn-primary" style="font-size:14px;height:44px;padding:0 24px;">Browse feed</a>
  </div>

  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;" id="bookmarks-grid">
    <?php foreach ($bookmarks as $i => $bm):
        $url    = htmlspecialchars($bm['article_url']   ?? '#');
        $title  = htmlspecialchars($bm['article_title'] ?? 'Saved Article');
        $image  = htmlspecialchars($bm['article_image'] ?? '');
        $source = htmlspecialchars($bm['article_source']?? '');
        $saved  = $bm['created_at'] ? date('M j, Y', strtotime($bm['created_at'])) : '';
        $delay  = min($i*50, 300);
    ?>
    <div class="article-card animate-slide-up" style="animation-delay:<?= $delay ?>ms"
         id="bm-<?= md5($bm['article_url']) ?>">
      <div class="card-photo">
        <?php if ($image): ?>
        <img src="<?= $image ?>" alt="<?= $title ?>" loading="lazy"
             onerror="this.onerror=null;this.src='<?= $placeholder ?>'">
        <?php else: ?>
        <div class="card-photo-placeholder">
          <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <?php endif; ?>
        <!-- Saved heart indicator -->
        <div style="position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.9);display:flex;align-items:center;justify-content:center;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#ff385c" stroke="#ff385c" stroke-width="1.5">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
        </div>
      </div>

      <div class="card-meta">
        <div class="card-source-row">
          <span style="font-weight:500;color:#222;font-size:13px;"><?= $source ?></span>
          <span><?= $saved ?></span>
        </div>
        <a href="<?= $url ?>" target="_blank" rel="noopener" style="text-decoration:none;">
          <p class="card-title"><?= $title ?></p>
        </a>
        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
          <a href="<?= $url ?>" target="_blank" rel="noopener" class="card-read-more">Read article →</a>
          <button class="remove-bm-btn btn-ghost"
                  data-url="<?= $url ?>"
                  data-card-id="bm-<?= md5($bm['article_url']) ?>"
                  style="font-size:13px;color:#c13515;margin-left:auto;">Remove</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<style>
@media (max-width:1128px) { #bookmarks-grid { grid-template-columns:repeat(3,1fr) !important; } }
@media (max-width:744px)  { #bookmarks-grid { grid-template-columns:repeat(2,1fr) !important; gap:16px !important; } }
@media (max-width:480px)  { #bookmarks-grid { grid-template-columns:1fr !important; } }
</style>

<script>
document.querySelectorAll('.remove-bm-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const url = btn.dataset.url; const cardId = btn.dataset.cardId;
    btn.textContent = '…'; btn.disabled = true;
    const fd = new FormData(); fd.append('action','remove'); fd.append('url', url);
    const res  = await fetch('/wt_project/api/bookmark.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.success) {
      const card = document.getElementById(cardId);
      if (card) { card.style.opacity='0'; card.style.transform='scale(0.95)'; card.style.transition='all .25s'; setTimeout(()=>card.remove(),250); }
    } else { btn.textContent='Remove'; btn.disabled=false; }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
