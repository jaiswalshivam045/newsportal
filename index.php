<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/news_api.php';
require_once __DIR__ . '/includes/bookmark.php';
require_once __DIR__ . '/includes/admin_posts.php';
require_once __DIR__ . '/includes/article_card.php';

$search      = trim($_GET['q']        ?? '');
$category    = trim($_GET['category'] ?? '');
$dateFilter  = trim($_GET['date']     ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));

if ($category && !array_key_exists($category, CATEGORIES)) $category = '';

$keywords = [];

if ($search || $category) {
    // If actively searching or filtering by category, ignore saved preferences
    if ($search) $keywords[] = $search;
} else {
    // Otherwise, use personalized feed preferences
    $prefs    = getUserPreferences();
    $keywords = $prefs['keywords'] ?? [];
    $prefCats = $prefs['categories'] ?? [];
    if ($prefCats) $keywords = array_merge($keywords, $prefCats);
}

$keywords = array_unique(array_filter($keywords));

$fromDate = match($dateFilter) {
    '24h' => date('Y-m-d', strtotime('-1 day')),
    '7d'  => date('Y-m-d', strtotime('-7 days')),
    default => ''
};

$apiResult    = fetchArticles($keywords, $category, $page, 'publishedAt', $fromDate);
$apiArticles  = $apiResult['articles'] ?? [];
$totalResults = $apiResult['totalResults'] ?? 0;
$apiError     = $apiResult['error'] ?? null;

$normalizedApi = array_map('normalizeApiArticle', $apiArticles);

$adminPosts = [];
if ($page === 1) {
    $adminPosts = array_map('normalizeAdminPost', getAllAdminPosts($category));
}
$allArticles = array_merge($adminPosts, $normalizedApi);

$bookmarkedUrls = isLoggedIn() ? getBookmarkedUrls((int)$_SESSION['user_id']) : [];
$totalPages     = max(1, (int)ceil(($totalResults + count($adminPosts)) / NEWS_PER_PAGE));

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    foreach ($allArticles as $i => $article) {
        $bmarked = in_array($article['url'], $bookmarkedUrls, true);
        renderCard($article, $bmarked, $i);
    }
    exit;
}

$pageTitle = $search ? 'Search: ' . $search : ($category ? (CATEGORIES[$category] ?? ucfirst($category)) : 'Your Feed');
$pageDesc  = SITE_TAGLINE;

include __DIR__ . '/includes/header.php';
?>

<!-- ── Category Strip ──────────────────────────────────────── -->
<nav class="category-strip" aria-label="Topics">
  <div class="category-strip-inner" style="padding-top:12px;padding-bottom:0;">
    <a href="<?= BASE_URL ?>/index.php<?= $search ? '?q='.urlencode($search) : '' ?>"
       class="cat-pill <?= !$category ? 'active' : '' ?>" id="cat-all">
      🌐 All
    </a>
    <?php foreach (CATEGORIES as $key => $label): ?>
    <a href="<?= BASE_URL ?>/index.php?category=<?= $key ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="cat-pill <?= $category === $key ? 'active' : '' ?>" id="cat-<?= $key ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<!-- ── Main content ─────────────────────────────────────────── -->
<div class="max-w-screen-xl px-6" style="padding-top:40px;padding-bottom:80px;">

  <!-- Hero greeting -->
  <?php if ($page === 1 && !$search && !$category && isLoggedIn()): ?>
  <div class="animate-fade-in" style="margin-bottom:32px; padding: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--color-hairline); border-radius: 16px;">
    <h1 style="font-size:24px;font-weight:700;color:var(--color-ink);line-height:1.3;margin:0 0 6px;font-family:'Outfit',sans-serif;">
      Good <?= date('G') < 12 ? 'morning' : (date('G') < 17 ? 'afternoon' : 'evening') ?>,
      <?= htmlspecialchars($_SESSION['user_name'] ?? 'there') ?> 👋
    </h1>
    <p style="font-size:14px;color:var(--color-muted);margin:0;font-weight:500;">Here's your personalized feed for <?= date('l, F j') ?></p>
  </div>
  <?php elseif ($search || $category): ?>
  <div class="animate-fade-in" style="margin-bottom:24px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--color-ink);margin:0;font-family:'Outfit',sans-serif;">
      <?= $search ? 'Results for "'.htmlspecialchars($search).'"' : htmlspecialchars(CATEGORIES[$category] ?? ucfirst($category)) ?>
    </h1>
  </div>
  <?php endif; ?>

  <!-- Date filter pills -->
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:24px;" class="animate-fade-in">
    <span style="font-size:13px;color:var(--color-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-right:4px;">Filter:</span>
    <?php
    $dateOpts = ['' => 'Any time', '24h' => 'Last 24h', '7d' => 'Last 7 days'];
    foreach ($dateOpts as $dk => $dl):
        $dActive = $dateFilter === $dk;
        $params  = array_filter(['q' => $search, 'category' => $category, 'date' => $dk]);
    ?>
    <a href="<?= BASE_URL ?>/index.php<?= $params ? '?'.http_build_query($params) : '' ?>"
       id="date-<?= $dk ?: 'all' ?>"
       style="font-size:13px;font-weight:600;padding:6px 16px;border-radius:9999px;text-decoration:none;
              border:1px solid <?= $dActive ? 'transparent' : 'var(--color-hairline-soft)' ?>;
              background:<?= $dActive ? 'var(--gradient-accent)' : 'rgba(255, 255, 255, 0.03)' ?>;
              color:<?= $dActive ? '#fff' : 'var(--color-muted)' ?>;
              box-shadow:<?= $dActive ? '0 4px 10px rgba(99, 102, 241, 0.2)' : 'none' ?>;
              transition:all .2s;"
       onmouseover="if(<?= $dActive ? 'false' : 'true' ?>) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='var(--color-ink)'; }"
       onmouseout="if(<?= $dActive ? 'false' : 'true' ?>) { this.style.background='rgba(255,255,255,0.03)'; this.style.color='var(--color-muted)'; }">
      <?= $dl ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- API error -->
  <?php if ($apiError): ?>
  <div class="alert alert-info animate-fade-in" style="margin-bottom:24px;">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
    </svg>
    <span><?= htmlspecialchars($apiError) ?>
      <?php if (NEWS_API_KEY === 'YOUR_API_KEY_HERE'): ?>
        — Add your key to <code>config/config.php</code>.
        <a href="https://newsapi.org/register" target="_blank" style="color:inherit;font-weight:600;">Get one free →</a>
      <?php endif; ?>
    </span>
  </div>
  <?php endif; ?>

  <!-- Results count -->
  <?php if (!empty($allArticles)): ?>
  <p style="font-size:13px;color:var(--color-muted);margin-bottom:20px;font-weight:500;">
    <?= count($allArticles) ?> articles<?php if ($totalResults > NEWS_PER_PAGE): ?> of ~<?= number_format($totalResults) ?><?php endif; ?>
    <?= $search ? ' for <strong style="color:var(--color-ink)">'.htmlspecialchars($search).'</strong>' : '' ?>
  </p>
  <?php endif; ?>

  <!-- Card grid -->
  <?php if (!empty($allArticles)): ?>
  <div id="articles-grid"
       style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:48px;">
    <?php foreach ($allArticles as $i => $article):
        $bmarked = in_array($article['url'], $bookmarkedUrls, true);
        renderCard($article, $bmarked, $i);
    endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1):
      $base = BASE_URL . '/index.php?' . http_build_query(array_filter(['q'=>$search,'category'=>$category,'date'=>$dateFilter]));
  ?>
  <div id="pagination-container">
    <nav style="display:flex;align-items:center;justify-content:center;gap:6px;" aria-label="Pages" id="standard-pagination">
      <?php if ($page > 1): ?>
      <a href="<?= $base ?>&page=<?= $page-1 ?>" id="page-prev" class="page-btn-arrow">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
        Prev
      </a>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <a href="<?= $base ?>&page=<?= $p ?>" id="page-<?= $p ?>" class="page-btn <?= $p===$page?'active':'' ?>">
        <?= $p ?>
      </a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="<?= $base ?>&page=<?= $page+1 ?>" id="page-next" class="page-btn-arrow">
        Next
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
      </a>
      <?php endif; ?>
    </nav>
    
    <!-- Infinite Scroll Loader -->
    <div id="infinite-scroll-loader" style="display:none;text-align:center;padding:20px;">
      <div class="skeleton" style="display:inline-block;width:40px;height:40px;border-radius:50%;opacity:0.6;"></div>
    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <!-- Empty state -->
  <div class="animate-fade-in" style="text-align:center;padding:80px 0;">
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.02);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <svg width="36" height="36" fill="none" stroke="var(--color-muted)" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
      </svg>
    </div>
    <h2 style="font-size:22px;font-weight:700;color:var(--color-ink);margin:0 0 8px;font-family:'Outfit',sans-serif;">No articles found</h2>
    <p style="font-size:15px;color:var(--color-muted);max-width:380px;margin:0 auto 24px;line-height:1.5;">
      <?= $search ? 'No results for "'.htmlspecialchars($search).'". Try a different keyword.' : 'Try a different category or check back later.' ?>
    </p>
    <a href="<?= BASE_URL ?>/index.php" class="btn-primary" style="font-size:14px;height:42px;padding:0 24px;">Back to Feed</a>
  </div>
  <?php endif; ?>

  <!-- Guest CTA -->
  <?php if (!isLoggedIn() && $page === 1): ?>
  <div class="animate-fade-in" style="margin-top:64px;padding:40px;background:rgba(99, 102, 241, 0.03);border:1px solid rgba(99, 102, 241, 0.15);border-radius:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
    <div>
      <h3 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:700;color:var(--color-ink);margin:0 0 6px;">Personalize your feed</h3>
      <p style="font-size:15px;color:var(--color-muted);margin:0;">Follow topics, save articles, and get news tailored to your interests.</p>
    </div>
    <div style="display:flex;gap:12px;flex-shrink:0;">
      <a href="<?= BASE_URL ?>/register.php" class="btn-primary" style="font-size:14px;height:42px;padding:0 22px;">Get started</a>
      <a href="<?= BASE_URL ?>/login.php"    class="btn-secondary" style="font-size:14px;height:42px;padding:0 20px;">Log in</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<style>
@media (max-width:1128px) { #articles-grid { grid-template-columns: repeat(3,1fr) !important; } }
@media (max-width:744px)  { #articles-grid { grid-template-columns: repeat(2,1fr) !important; gap:16px !important; } }
@media (max-width:480px)  { #articles-grid { grid-template-columns: 1fr !important; } }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const container = document.getElementById('articles-grid');
  const loader = document.getElementById('infinite-scroll-loader');
  const stdPagination = document.getElementById('standard-pagination');
  
  if (!container || !loader) return;

  let currentPage = <?= (int)$page ?>;
  const totalPages = <?= (int)$totalPages ?>;
  let isLoading = false;
  const baseUrl = <?= json_encode($base ?? '') ?>;

  if (currentPage < totalPages) {
      if (stdPagination) stdPagination.style.display = 'none';
      loader.style.display = 'block';

      const observer = new IntersectionObserver((entries) => {
          if (entries[0].isIntersecting && !isLoading && currentPage < totalPages) {
              loadNextPage();
          }
      }, { rootMargin: '200px' });
      observer.observe(loader);
  }

  async function loadNextPage() {
      isLoading = true;
      currentPage++;
      const sep = baseUrl.includes('?') ? '&' : '?';
      const url = baseUrl + sep + 'page=' + currentPage + '&ajax=1';
      
      try {
          const response = await fetch(url);
          const html = await response.text();
          if (html.trim()) {
              container.insertAdjacentHTML('beforeend', html);
              
              // Ensure newly added lazy images get the fade-in treatment
              container.querySelectorAll('img[loading="lazy"]:not([data-lazy-handled])').forEach(img => {
                img.dataset.lazyHandled = '1';
                img.style.opacity = '0';
                img.style.transition = 'opacity 0.3s ease';
                img.addEventListener('load', () => img.style.opacity = '1');
                if (img.complete) img.style.opacity = '1';
              });

              if (currentPage >= totalPages) {
                  loader.style.display = 'none';
              }
          } else {
              loader.style.display = 'none';
          }
      } catch (e) {
          console.error("Failed to load more articles", e);
      } finally {
          isLoading = false;
      }
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
