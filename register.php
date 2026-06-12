<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name    = trim($_POST['name']     ?? '');
        $email   = trim($_POST['email']    ?? '');
        $pass    = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm']  ?? '');

        if (empty($name)||empty($email)||empty($pass)||empty($confirm)) $error = 'Please fill in all fields.';
        elseif (strlen($name) < 2)                $error = 'Name must be at least 2 characters.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
        elseif (strlen($pass) < 8)                $error = 'Password must be at least 8 characters.';
        elseif ($pass !== $confirm)               $error = 'Passwords do not match.';
        else {
            $result = registerUser($name, $email, $pass);
            if ($result === true) {
                loginUser($email, $pass);
                header('Location: ' . BASE_URL . '/profile.php?welcome=1'); exit;
            } else {
                $error = $result;
            }
        }
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign up — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css?v=<?= time() ?>">
</head>
<body style="background:var(--color-canvas);min-height:100vh;display:flex;flex-direction:column;">

<div style="flex:1;display:flex;align-items:center;justify-content:center;padding:40px 16px;">
  <div style="width:100%;max-width:440px;">

    <div style="text-align:center;margin-bottom:28px;">
      <a href="<?= BASE_URL ?>/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:10px;">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="logo-grad-reg" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="100%" stop-color="#a855f7"/>
            </linearGradient>
          </defs>
          <path d="M16 2C8.268 2 2 8.268 2 16s6.268 14 14 14 14-6.268 14-14S23.732 2 16 2zm0 6c2.209 0 4 1.791 4 4s-1.791 4-4 4-4-1.791-4-4 1.791-4 4-4zm0 18c-4.418 0-8-3.582-8-8 0-1.758.568-3.385 1.527-4.71L16 25.5l6.473-12.21C23.432 14.615 24 16.242 24 18c0 4.418-3.582 8-8 8z" fill="url(#logo-grad-reg)"/>
        </svg>
        <span style="font-family:'Outfit',sans-serif;font-size:24px;font-weight:800;background:linear-gradient(135deg, #818cf8 0%, #c084fc 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-0.5px;"><?= SITE_NAME ?></span>
      </a>
      <h1 style="font-family:'Outfit',sans-serif;font-size:24px;font-weight:700;color:var(--color-ink);margin:16px 0 4px;">Create your account</h1>
      <p style="font-size:15px;color:var(--color-muted);margin:0;font-weight:500;">Free — personalize your news in seconds</p>
    </div>

    <div class="auth-card">
      <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px;">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form id="register-form" method="POST" action="" style="display:flex;flex-direction:column;gap:14px;" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="field-wrap">
          <label for="name" class="field-label">Full name</label>
          <input type="text" id="name" name="name" class="field-input"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="Jane Doe" required autocomplete="name">
        </div>

        <div class="field-wrap">
          <label for="email" class="field-label">Email address</label>
          <input type="email" id="email" name="email" class="field-input"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="field-wrap">
          <label for="password" class="field-label">Password <span style="color:var(--color-muted-soft);font-weight:400;">(min. 8 chars)</span></label>
          <div style="position:relative;">
            <input type="password" id="password" name="password" class="field-input"
                   placeholder="••••••••" required autocomplete="new-password" style="padding-right:44px;">
            <button type="button" id="toggle-pw" aria-label="Toggle password"
                    style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-muted);padding:0;">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <!-- Strength bar -->
          <div style="height:3px;background:var(--color-hairline);border-radius:2px;margin-top:6px;overflow:hidden;">
            <div id="pw-strength" style="height:100%;border-radius:2px;transition:width .3s,background .3s;width:0;"></div>
          </div>
        </div>

        <div class="field-wrap">
          <label for="confirm" class="field-label">Confirm password</label>
          <input type="password" id="confirm" name="confirm" class="field-input"
                 placeholder="••••••••" required autocomplete="new-password">
        </div>

        <button type="submit" id="register-submit" class="btn-primary" style="width:100%;height:46px;font-size:15px;margin-top:6px;">
          Create account
        </button>
      </form>
    </div>

    <p style="text-align:center;font-size:14px;color:var(--color-muted);margin-top:20px;">
      Already have an account?
      <a href="<?= BASE_URL ?>/login.php" style="color:#818cf8;font-weight:600;text-decoration:none;">Log in</a>
    </p>
  </div>
</div>

<script>
document.getElementById('toggle-pw')?.addEventListener('click', () => {
  const f = document.getElementById('password');
  f.type = f.type === 'password' ? 'text' : 'password';
});
document.getElementById('password')?.addEventListener('input', function () {
  const bar = document.getElementById('pw-strength');
  const v = this.value; let s = 0;
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  const colors = ['#f87171','#fb923c','#facc15','#4ade80'];
  bar.style.width  = (s * 25) + '%';
  bar.style.background = colors[s-1] || 'transparent';
});
</script>
</body>
</html>
