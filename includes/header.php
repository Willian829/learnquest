<?php
// includes/header.php — incluído em todas as páginas
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', SITE_NAME);
$user = currentUser();
$activeTab = $activeTab ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(PAGE_TITLE) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<?php if ($user): ?>
<!-- ===== TOPBAR ===== -->
<header class="topbar">
  <a href="<?= SITE_URL ?>/pages/dashboard.php" class="topbar-logo">
    <div class="logo-icon">🚀</div>
    <span class="logo-text">LearnQuest</span>
  </a>
  <div class="topbar-stats">
    <div class="stat-chip fire">🔥 <span><?= (int)$user['streak'] ?></span></div>
    <div class="stat-chip gem">💎 <span><?= (int)$user['gems'] ?></span></div>
    <div class="stat-chip heart">❤️ <span><?= (int)$user['hearts'] ?></span></div>
  </div>
</header>

<!-- ===== NAV BOTTOM ===== -->
<nav class="bottom-nav">
  <a href="<?= SITE_URL ?>/pages/dashboard.php"  class="nav-item <?= $activeTab==='home'    ?'active':'' ?>">
    <span class="nav-icon">🏠</span><span class="nav-label">Início</span>
  </a>
  <a href="<?= SITE_URL ?>/pages/map.php"         class="nav-item <?= $activeTab==='map'     ?'active':'' ?>">
    <span class="nav-icon">🗺️</span><span class="nav-label">Mapa</span>
  </a>
  <a href="<?= SITE_URL ?>/pages/ranking.php"     class="nav-item <?= $activeTab==='ranking' ?'active':'' ?>">
    <span class="nav-icon">🏆</span><span class="nav-label">Ranking</span>
  </a>
  <a href="<?= SITE_URL ?>/pages/profile.php"     class="nav-item <?= $activeTab==='profile' ?'active':'' ?>">
    <span class="nav-icon">👤</span><span class="nav-label">Perfil</span>
  </a>
</nav>
<?php endif; ?>

<!-- ===== XP TOAST (global) ===== -->
<div id="xp-toast" class="xp-toast hidden"></div>
<div id="achievement-toast" class="achievement-toast hidden"></div>
