<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user     = currentUser();
$missions = getUserMissions($user['id']);
$xpInLv   = xpInLevel((int)$user['xp']);
$xpPct    = min(round(($xpInLv / XP_PER_LEVEL) * 100), 100);

define('PAGE_TITLE', 'Início');
$activeTab = 'home';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="page">

  <!-- Hero Card -->
  <div class="hero-card">
    <div class="hero-top">
      <div>
        <p class="hero-greeting">Bom dia,</p>
        <h1 class="hero-name">Olá, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
      </div>
      <div class="streak-badge">
        🔥 <span><?= (int)$user['streak'] ?></span>
      </div>
    </div>
    <div class="xp-bar-wrap">
      <div class="level-badge"><?= (int)$user['level'] ?></div>
      <div class="xp-info">
        <div class="xp-labels">
          <span class="lv">Nível <?= (int)$user['level'] ?></span>
          <span class="pts"><?= (int)$user['xp'] ?> / <?= (int)$user['level'] * XP_PER_LEVEL ?> XP</span>
        </div>
        <div class="xp-track">
          <div class="xp-fill" style="width:<?= $xpPct ?>%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stat Pills -->
  <div class="stat-pills">
    <div class="stat-pill">
      <span class="sp-icon">⚡</span>
      <span class="sp-val" style="color:var(--wa)"><?= (int)$user['xp'] ?></span>
      <span class="sp-lbl">XP Total</span>
    </div>
    <div class="stat-pill">
      <span class="sp-icon">💎</span>
      <span class="sp-val" style="color:var(--in)"><?= (int)$user['gems'] ?></span>
      <span class="sp-lbl">Gemas</span>
    </div>
    <div class="stat-pill">
      <span class="sp-icon">❤️</span>
      <span class="sp-val" style="color:var(--da)"><?= (int)$user['hearts'] ?></span>
      <span class="sp-lbl">Vidas</span>
    </div>
    <?php
      $totalMedals = db()->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id=?");
      $totalMedals->execute([$user['id']]);
      $medCount = (int)$totalMedals->fetchColumn();
    ?>
    <div class="stat-pill">
      <span class="sp-icon">🏅</span>
      <span class="sp-val" style="color:var(--p)"><?= $medCount ?></span>
      <span class="sp-lbl">Medalhas</span>
    </div>
  </div>

  <!-- Missões -->
  <div class="section-title">
    🎯 Missões
    <span><?= count(array_filter($missions, fn($m)=>$m['done'])) ?>/<?= count($missions) ?> hoje</span>
  </div>
  <div class="mission-list">
    <?php foreach ($missions as $m): ?>
    <div class="mission-card <?= $m['done'] ? 'done' : '' ?>"
      <?php if (!$m['done']): ?>
        onclick="completeMission(<?= (int)$m['id'] ?>, this)"
      <?php endif; ?>
    >
      <div class="mission-icon"><?= htmlspecialchars($m['icon']) ?></div>
      <div class="mission-body">
        <div class="mission-title"><?= htmlspecialchars($m['title']) ?></div>
        <div class="mission-desc"><?= htmlspecialchars($m['description']) ?></div>
      </div>
      <div class="mission-xp">
        <?= $m['done'] ? '✓ Feito' : '+' . (int)$m['xp_reward'] . ' XP' ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Próxima Lição -->
  <?php
    $nextLesson = db()->prepare("
      SELECT l.*, c.title AS chapter_title, c.color AS chapter_color
      FROM lessons l
      JOIN chapters c ON c.id = l.chapter_id
      WHERE l.id NOT IN (
        SELECT lesson_id FROM user_lessons WHERE user_id=? AND completed=1
      )
      AND c.xp_required <= ?
      ORDER BY c.position, l.position
      LIMIT 1
    ");
    $nextLesson->execute([$user['id'], $user['xp']]);
    $next = $nextLesson->fetch();
  ?>
  <?php if ($next): ?>
  <div style="margin-top:6px">
    <div class="section-title">📚 Continue Aprendendo</div>
    <div class="card" style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
      <div style="width:50px;height:50px;border-radius:14px;background:<?= htmlspecialchars($next['chapter_color']) ?>22;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">
        <?= htmlspecialchars($next['icon']) ?>
      </div>
      <div style="flex:1">
        <div style="font-size:11px;color:var(--tx-m);font-weight:700;margin-bottom:2px">
          <?= htmlspecialchars($next['chapter_title']) ?>
        </div>
        <div style="font-size:15px;font-weight:800;color:var(--tx)">
          <?= htmlspecialchars($next['title']) ?>
        </div>
        <div style="font-size:11px;color:var(--tx-s);margin-top:2px">
          +<?= (int)$next['xp_reward'] ?> XP • +<?= (int)$next['gem_reward'] ?> 💎
        </div>
      </div>
      <a href="lesson.php?id=<?= (int)$next['id'] ?>" class="btn btn-primary" style="padding:10px 16px;font-size:13px;flex-shrink:0">
        Jogar ▶
      </a>
    </div>
  </div>
  <?php else: ?>
  <div class="card" style="text-align:center;padding:24px;margin-top:6px">
    <div style="font-size:48px;margin-bottom:10px">🎉</div>
    <div style="font-weight:900;font-size:16px;color:var(--tx);margin-bottom:4px">Parabéns!</div>
    <div style="font-size:13px;color:var(--tx-s)">Você completou todas as lições disponíveis.</div>
  </div>
  <?php endif; ?>

  <!-- Botão ir ao mapa -->
  <a href="map.php" class="btn btn-accent btn-full btn-lg" style="margin-top:4px">
    🗺️ Ver Mapa de Aprendizado
  </a>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
