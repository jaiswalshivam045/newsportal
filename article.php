<?php
// ─────────────────────────────────────────────
// Article Detail Page (Admin Posts)
// /article.php?id=...
// ─────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin_posts.php';
require_once __DIR__ . '/includes/bookmark.php';

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getAdminPost($id) : null;

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Article Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="max-w-screen-xl px-6" style="padding-top:80px;padding-bottom:80px;text-align:center;">
        <h1 class="type-display-xl" style="margin-bottom:16px;">404 — Not Found</h1>
        <p style="color:var(--color-muted);font-size:16px;margin-bottom:32px;">This article does not exist or has been removed.</p>
        <a href="' . BASE_URL . '/index.php" class="btn-primary">← Back to Feed</a>
    </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$pageDesc  = mb_substr(strip_tags($post['content']), 0, 160);

$articleUrl    = BASE_URL . '/article.php?id=' . $post['id'];
$isBookmarked  = isLoggedIn() && isBookmarked((int)$_SESSION['user_id'], $articleUrl);
$categoryLabel = CATEGORIES[$post['category']] ?? ucfirst($post['category']);
$published     = date('F j, Y', strtotime($post['created_at']));

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-screen-xl px-6" style="padding-top:40px;padding-bottom:80px;max-width:800px;margin:0 auto;">

    <!-- Breadcrumb -->
    <nav style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--color-muted);margin-bottom:32px;font-weight:500;">
        <a href="<?= BASE_URL ?>/index.php" style="color:var(--color-ink);text-decoration:none;">Home</a>
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="<?= BASE_URL ?>/index.php?category=<?= $post['category'] ?>" style="color:var(--color-ink);text-decoration:none;"><?= $categoryLabel ?></a>
    </nav>

    <article class="animate-slide-up">

        <!-- Title -->
        <h1 style="font-size:36px;font-weight:700;color:var(--color-ink);line-height:1.2;margin:0 0 24px;letter-spacing:-0.5px;">
            <?= htmlspecialchars($post['title']) ?>
        </h1>

        <!-- Meta Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;padding-bottom:24px;border-bottom:1px solid var(--color-hairline);margin-bottom:32px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--color-surface-soft);display:flex;align-items:center;justify-content:center;border:1px solid var(--color-hairline);">
                    <svg width="24" height="24" fill="none" stroke="#222" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p style="font-size:15px;font-weight:600;color:var(--color-ink);margin:0;">Editorial Team</p>
                    <p style="font-size:14px;color:var(--color-muted);margin:2px 0 0;"><?= $published ?> · Featured Post</p>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <button class="bookmark-btn <?= $isBookmarked ? 'saved' : '' ?>"
                    style="display:flex;align-items:center;gap:8px;padding:8px 16px;border-radius:9999px;border:1px solid <?= $isBookmarked ? 'var(--color-rausch)' : 'var(--color-ink)' ?>;background:<?= $isBookmarked ? 'var(--color-rausch-pale)' : 'transparent' ?>;color:<?= $isBookmarked ? 'var(--color-rausch)' : 'var(--color-ink)' ?>;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s;"
                    data-url="<?= htmlspecialchars($articleUrl) ?>"
                    data-title="<?= htmlspecialchars($post['title']) ?>"
                    data-image="<?= htmlspecialchars($post['image_url'] ?? '') ?>"
                    data-source="<?= SITE_NAME ?>"
                    data-bookmarked="<?= $isBookmarked ? '1' : '0' ?>">
                <?php if ($isBookmarked): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <span>Saved</span>
                <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <span>Save</span>
                <?php endif; ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- Cover image -->
        <?php if (!empty($post['image_url'])): ?>
        <div style="margin-bottom:40px;border-radius:14px;overflow:hidden;background:var(--color-surface-soft);">
            <img src="<?= htmlspecialchars($post['image_url']) ?>"
                 alt="<?= htmlspecialchars($post['title']) ?>"
                 style="width:100%;max-height:480px;object-fit:cover;display:block;"
                 loading="lazy">
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div style="font-size:18px;color:var(--color-body);line-height:1.7;font-family:'Inter',sans-serif;">
            <?php
            $content = htmlspecialchars($post['content']);
            $paragraphs = array_filter(explode("\n\n", $content));
            foreach ($paragraphs as $para):
                $para = nl2br(trim($para));
            ?>
            <p style="margin-bottom:24px;"><?= $para ?></p>
            <?php endforeach; ?>
        </div>

        <!-- Footer actions -->
        <div style="margin-top:48px;padding-top:32px;border-top:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;gap:12px;">
                <a href="<?= BASE_URL ?>/index.php" class="btn-secondary" style="height:44px;padding:0 20px;font-size:14px;">
                    ← Back to Feed
                </a>
                <a href="<?= BASE_URL ?>/index.php?category=<?= $post['category'] ?>" class="btn-secondary" style="height:44px;padding:0 20px;font-size:14px;">
                    More in <?= $categoryLabel ?>
                </a>
            </div>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" style="font-size:14px;color:var(--color-muted);font-weight:500;text-decoration:none;">
                Admin Panel
            </a>
            <?php endif; ?>
        </div>
    </article>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
