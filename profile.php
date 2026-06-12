<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user    = getCurrentUser();
$prefs   = json_decode($user['preferences'] ?? '{}', true) ?: ['keywords'=>[],'categories'=>[]];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $keywords   = array_values(array_unique(array_filter(array_map('trim', explode(',', $_POST['keywords'] ?? '')))));
        $categories = array_values(array_unique(array_filter($_POST['categories'] ?? [], fn($c) => array_key_exists($c, CATEGORIES))));
        $pdo  = getDB();
        $stmt = $pdo->prepare('UPDATE users SET preferences=? WHERE id=?');
        if ($stmt->execute([json_encode(compact('keywords','categories')), $_SESSION['user_id']])) {
            $prefs   = compact('keywords','categories');
            $success = 'Preferences saved!';
        } else { $error = 'Failed to save. Please try again.'; }
    }
}

$pageTitle = 'My Profile';
$csrf      = generateCsrfToken();
$welcome   = isset($_GET['welcome']);
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-screen-xl px-6" style="padding-top:40px;padding-bottom:80px;max-width:760px;">

  <h1 class="type-display-xl" style="margin:0 0 6px;">Profile &amp; Preferences</h1>
  <p style="font-size:16px;color:#6a6a6a;margin:0 0 32px;">Customize the topics and keywords that shape your feed.</p>

  <?php if ($welcome): ?>
  <div class="alert alert-info" style="margin-bottom:24px;border-radius:12px;padding:16px 20px;">
    <span style="font-size:20px;">🎉</span>
    <div>
      <strong>Welcome, <?= htmlspecialchars($user['name']) ?>!</strong>
      Set your preferences below to personalize your news feed.
    </div>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="alert alert-success animate-fade-in" style="margin-bottom:24px;border-radius:10px;">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php elseif ($error): ?>
  <div class="alert alert-error animate-fade-in" style="margin-bottom:24px;border-radius:10px;">
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- User info -->
  <div class="stat-card" style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
    <div style="width:56px;height:56px;border-radius:50%;background:#ff385c;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;flex-shrink:0;">
      <?= strtoupper(substr($user['name'],0,1)) ?>
    </div>
    <div>
      <p style="font-size:17px;font-weight:600;color:#222;margin:0;"><?= htmlspecialchars($user['name']) ?></p>
      <p style="font-size:14px;color:#6a6a6a;margin:2px 0 0;"><?= htmlspecialchars($user['email']) ?></p>
      <?php if ($user['is_admin']): ?>
      <span style="display:inline-block;margin-top:4px;font-size:11px;font-weight:600;color:#ff385c;border:1px solid #ffd1da;background:#fff7f8;border-radius:9999px;padding:2px 10px;">Admin</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Preferences form -->
  <div class="stat-card" style="margin-bottom:24px;">
    <h2 class="type-display-sm" style="margin:0 0 4px;">News Preferences</h2>
    <p style="font-size:14px;color:#6a6a6a;margin:0 0 24px;">Select topics and keywords to personalize your homepage feed.</p>

    <form method="POST" id="prefs-form" style="display:flex;flex-direction:column;gap:28px;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <!-- Categories -->
      <div>
        <p class="field-label" style="margin-bottom:12px;">Preferred categories</p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;" id="cat-grid">
          <?php foreach (CATEGORIES as $key => $label):
              $checked = in_array($key, $prefs['categories'] ?? [], true);
          ?>
          <label id="cat-label-<?= $key ?>"
                 style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:10px;cursor:pointer;border:1px solid <?= $checked ? '#222' : '#dddddd' ?>;background:<?= $checked ? '#f7f7f7' : '#fff' ?>;transition:all .15s;">
            <input type="checkbox" name="categories[]" value="<?= $key ?>"
                   <?= $checked ? 'checked' : '' ?> style="display:none;" class="cat-checkbox">
            <div class="cat-check-box" style="width:20px;height:20px;border-radius:5px;border:2px solid <?= $checked ? '#222' : '#dddddd' ?>;background:<?= $checked ? '#222' : '#fff' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;">
              <?php if ($checked): ?><svg width="11" height="11" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg><?php endif; ?>
            </div>
            <span style="font-size:14px;font-weight:500;color:#222;"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Keywords -->
      <div class="field-wrap">
        <label for="keywords-input" class="field-label">Custom keywords <span style="font-weight:400;color:#929292;">(comma-separated)</span></label>
        <input type="text" id="keywords-input" name="keywords" class="field-input"
               value="<?= htmlspecialchars(implode(', ', $prefs['keywords'] ?? [])) ?>"
               placeholder="e.g. Tesla, cricket, NASA, AI">
        <div id="keyword-tags" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;"></div>
      </div>

      <div style="display:flex;gap:12px;">
        <button type="submit" id="save-prefs" class="btn-primary" style="height:48px;padding:0 28px;font-size:15px;">Save preferences</button>
        <a href="<?= BASE_URL ?>/index.php" class="btn-secondary" style="height:48px;padding:0 20px;font-size:15px;">View my feed</a>
      </div>
    </form>
  </div>
</div>

<script>
// Category checkboxes
document.querySelectorAll('.cat-checkbox').forEach(cb => {
  cb.addEventListener('change', () => {
    const label = cb.closest('label');
    const box   = label.querySelector('.cat-check-box');
    label.style.borderColor = cb.checked ? '#222' : '#dddddd';
    label.style.background  = cb.checked ? '#f7f7f7' : '#fff';
    box.style.borderColor   = cb.checked ? '#222' : '#dddddd';
    box.style.background    = cb.checked ? '#222' : '#fff';
    box.innerHTML = cb.checked ? '<svg width="11" height="11" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>' : '';
  });
  cb.closest('label').addEventListener('click', e => {
    if (e.target !== cb) { e.preventDefault(); cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }
  });
});

// Keyword tags preview
const kwInput = document.getElementById('keywords-input');
const kwTags  = document.getElementById('keyword-tags');
function renderTags() {
  const vals = (kwInput.value||'').split(',').map(s=>s.trim()).filter(Boolean);
  kwTags.innerHTML = vals.map(v=>
    `<span style="font-size:12px;font-weight:500;padding:4px 12px;border-radius:9999px;background:#f7f7f7;border:1px solid #dddddd;color:#222;">${v}</span>`
  ).join('');
}
kwInput?.addEventListener('input', renderTags);
renderTags();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
