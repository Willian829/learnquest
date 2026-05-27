<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = currentUser();
$pdo  = db();

// Ranking geral por XP
$rankAll = $pdo->query("
    SELECT id, name, avatar, xp, level, streak
    FROM users
    ORDER BY xp DESC
    LIMIT 50
")->fetchAll();

// Posição do usuário atual
$myRank = 1;
foreach ($rankAll as $i => $u) {
    if ($u['id'] == $user['id']) { $myRank = $i + 1; break; }
}

// Ranking semanal (XP ganho nesta semana via lições)
$weekStart = date('Y-m-d', strtotime('monday this week'));
$rankWeekly = $pdo->prepare("
    SELECT u.id, u.name, u.avatar, u.level, u.streak,
           COALESCE(SUM(ul.best_score), 0) AS week_score,
           COUNT(ul.id) AS lessons_done
    FROM users u
    LEFT JOIN user_lessons ul ON ul.user_id = u.id AND ul.completed_at >= ?
    GROUP BY u.id
    ORDER BY week_score DESC, lessons_done DESC
    LIMIT 20
");
$rankWeekly->execute([$weekStart . ' 00:00:00']);
$weeklyData = $rankWeekly->fetchAll();

define('PAGE_TITLE', 'Ranking');
$activeTab = 'ranking';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="page">
  <h1 class="page-title">🏆 Ranking</h1>

  <!-- Minha posição -->
  <div class="card" style="background:linear-gradient(135deg,var(--p)15,var(--ac)08);
    border-color:var(--p)30;margin-bottom:16px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;border-radius:50%;
      background:linear-gradient(135deg,var(--p),var(--ac));
      display:flex;align-items:center;justify-content:center;
      font-size:22px;box-shadow:0 2px 8px rgba(16,185,129,.35)">
      <?= htmlspecialchars($user['avatar']) ?>
    </div>
    <div style="flex:1">
      <div style="font-weight:800;font-size:14px;color:var(--tx)">Sua posição</div>
      <div style="font-size:12px;color:var(--tx-s)"><?= htmlspecialchars($user['name']) ?></div>
    </div>
    <div style="text-align:right">
      <div style="font-size:24px;font-weight:900;color:var(--p)">#<?= $myRank ?></div>
      <div style="font-size:11px;color:var(--tx-m)"><?= number_format($user['xp']) ?> XP</div>
    </div>
  </div>

  <!-- Tabs -->
  <div style="display:flex;background:var(--bo-light);border-radius:12px;
    padding:4px;margin-bottom:16px" id="rank-tabs">
    <button class="auth-toggle-btn active" onclick="switchTab('all', this)" style="flex:1;padding:9px 0;border-radius:9px;border:none;background:var(--su);font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.06)">
      🌍 Geral
    </button>
    <button class="auth-toggle-btn" onclick="switchTab('week', this)" style="flex:1;padding:9px 0;border-radius:9px;border:none;background:transparent;font-weight:700;font-size:13px;cursor:pointer;color:var(--tx-m)">
      📅 Semanal
    </button>
  </div>

  <!-- Lista geral -->
  <div id="tab-all" class="rank-list">
    <?php foreach ($rankAll as $i => $u):
      $isMe = $u['id'] == $user['id'];
      $medals = ['🥇','🥈','🥉'];
    ?>
    <div class="rank-item <?= $isMe ? 'me' : '' ?>">
      <div class="rank-pos" style="color:<?= $i<3?'var(--wa)':'var(--tx-m)' ?>">
        <?= isset($medals[$i]) ? $medals[$i] : '#'.($i+1) ?>
      </div>
      <div class="rank-avatar"
        style="background:<?= $isMe?'linear-gradient(135deg,var(--p),var(--ac))':'var(--ac)22' ?>;
               color:<?= $isMe?'#fff':'var(--ac)' ?>">
        <?= htmlspecialchars($u['avatar']) ?>
      </div>
      <div class="rank-info">
        <div class="rank-name">
          <?= htmlspecialchars($u['name']) ?>
          <?php if ($isMe): ?><span style="font-size:10px;color:var(--p);font-weight:700"> (você)</span><?php endif; ?>
        </div>
        <div class="rank-sub">Nível <?= (int)$u['level'] ?> &nbsp;•&nbsp; 🔥 <?= (int)$u['streak'] ?> dias</div>
      </div>
      <div class="rank-xp"><?= number_format($u['xp']) ?> XP</div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rankAll)): ?>
    <div style="text-align:center;padding:32px;color:var(--tx-m)">Nenhum usuário ainda.</div>
    <?php endif; ?>
  </div>

  <!-- Lista semanal -->
  <div id="tab-week" class="rank-list" style="display:none">
    <?php foreach ($weeklyData as $i => $u):
      $isMe = $u['id'] == $user['id'];
      $medals = ['🥇','🥈','🥉'];
    ?>
    <div class="rank-item <?= $isMe ? 'me' : '' ?>">
      <div class="rank-pos" style="color:<?= $i<3?'var(--wa)':'var(--tx-m)' ?>">
        <?= isset($medals[$i]) ? $medals[$i] : '#'.($i+1) ?>
      </div>
      <div class="rank-avatar"
        style="background:<?= $isMe?'linear-gradient(135deg,var(--p),var(--ac))':'var(--wa)22' ?>;
               color:<?= $isMe?'#fff':'var(--wa)' ?>">
        <?= htmlspecialchars($u['avatar']) ?>
      </div>
      <div class="rank-info">
        <div class="rank-name">
          <?= htmlspecialchars($u['name']) ?>
          <?php if ($isMe): ?><span style="font-size:10px;color:var(--p);font-weight:700"> (você)</span><?php endif; ?>
        </div>
        <div class="rank-sub"><?= (int)$u['lessons_done'] ?> lições &nbsp;•&nbsp; 🔥 <?= (int)$u['streak'] ?> dias</div>
      </div>
      <div class="rank-xp" style="background:var(--wa)15;color:var(--wa)">
        <?= (int)$u['week_score'] ?>%
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($weeklyData)): ?>
    <div style="text-align:center;padding:32px;color:var(--tx-m)">Nenhuma atividade esta semana.</div>
    <?php endif; ?>
  </div>

</main>

<script>
function switchTab(tab, btn) {
  document.getElementById('tab-all').style.display  = tab === 'all'  ? '' : 'none';
  document.getElementById('tab-week').style.display = tab === 'week' ? '' : 'none';
  document.querySelectorAll('#rank-tabs button').forEach((b, i) => {
    const active = (tab === 'all' && i === 0) || (tab === 'week' && i === 1);
    b.classList.toggle('active', active);
    b.style.background = active ? 'var(--su)' : 'transparent';
    b.style.color      = active ? 'var(--tx)' : 'var(--tx-m)';
    b.style.boxShadow  = active ? '0 2px 8px rgba(0,0,0,.06)' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
