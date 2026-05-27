<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
define('ADMIN_PAGE','Dashboard');

$pdo = db();
$stats = [
  'users'     => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
  'chapters'  => $pdo->query("SELECT COUNT(*) FROM chapters")->fetchColumn(),
  'lessons'   => $pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn(),
  'questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
  'completions'=> $pdo->query("SELECT COUNT(*) FROM user_lessons WHERE completed=1")->fetchColumn(),
  'xp_total'  => $pdo->query("SELECT COALESCE(SUM(xp),0) FROM users")->fetchColumn(),
];

// Últimos usuários cadastrados
$recentUsers = $pdo->query("SELECT id,name,email,xp,level,streak,created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Top usuários por XP
$topUsers = $pdo->query("SELECT name,avatar,xp,level FROM users ORDER BY xp DESC LIMIT 5")->fetchAll();

// Lições mais jogadas
$topLessons = $pdo->query("
  SELECT l.title, l.icon, c.title AS chapter,
         COUNT(ul.id) AS plays,
         ROUND(AVG(ul.best_score),1) AS avg_score
  FROM user_lessons ul
  JOIN lessons l ON l.id=ul.lesson_id
  JOIN chapters c ON c.id=l.chapter_id
  GROUP BY l.id ORDER BY plays DESC LIMIT 6
")->fetchAll();

include __DIR__ . '/layout.php';
?>

<!-- Stat cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-val"><?= number_format($stats['users']) ?></div><div class="stat-lbl">Usuários</div></div>
      <div class="stat-icon" style="background:#D1FAE5">👥</div>
    </div>
    <div class="stat-sub">cadastrados no total</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-val"><?= number_format($stats['lessons']) ?></div><div class="stat-lbl">Lições</div></div>
      <div class="stat-icon" style="background:#EDE9FE">📚</div>
    </div>
    <div class="stat-sub">em <?= $stats['chapters'] ?> capítulos</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-val"><?= number_format($stats['questions']) ?></div><div class="stat-lbl">Questões</div></div>
      <div class="stat-icon" style="background:#DBEAFE">❓</div>
    </div>
    <div class="stat-sub">no banco de dados</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-val"><?= number_format($stats['completions']) ?></div><div class="stat-lbl">Conclusões</div></div>
      <div class="stat-icon" style="background:#FEF3C7">🏆</div>
    </div>
    <div class="stat-sub">lições completadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-val"><?= number_format($stats['xp_total']) ?></div><div class="stat-lbl">XP Total</div></div>
      <div class="stat-icon" style="background:#FEE2E2">⚡</div>
    </div>
    <div class="stat-sub">acumulado por todos</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
  <!-- Top usuários -->
  <div class="card">
    <div style="font-weight:900;font-size:15px;color:var(--tx);margin-bottom:14px">🥇 Top Usuários</div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($topUsers as $i => $u): ?>
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:24px;font-weight:900;font-size:13px;color:var(--tm);text-align:center">
          <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'#'.($i+1))) ?>
        </div>
        <div style="width:34px;height:34px;border-radius:50%;background:var(--pl);
          display:flex;align-items:center;justify-content:center;font-size:18px">
          <?= htmlspecialchars($u['avatar']) ?>
        </div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:13px;color:var(--tx)"><?= htmlspecialchars($u['name']) ?></div>
          <div style="font-size:11px;color:var(--tm)">Nível <?= $u['level'] ?></div>
        </div>
        <div style="font-weight:800;font-size:13px;color:var(--wa)"><?= number_format($u['xp']) ?> XP</div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($topUsers)): ?><div style="color:var(--tm);font-size:13px">Nenhum usuário ainda.</div><?php endif; ?>
    </div>
  </div>

  <!-- Lições mais jogadas -->
  <div class="card">
    <div style="font-weight:900;font-size:15px;color:var(--tx);margin-bottom:14px">📈 Lições Populares</div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($topLessons as $l): ?>
      <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:20px"><?= htmlspecialchars($l['icon']) ?></div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:12px;color:var(--tx)"><?= htmlspecialchars($l['title']) ?></div>
          <div style="font-size:11px;color:var(--tm)"><?= htmlspecialchars($l['chapter']) ?></div>
        </div>
        <div style="text-align:right">
          <div style="font-size:11px;font-weight:800;color:var(--p)"><?= (int)$l['plays'] ?>×</div>
          <div style="font-size:10px;color:var(--tm)"><?= $l['avg_score'] ?>% avg</div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($topLessons)): ?><div style="color:var(--tm);font-size:13px">Nenhuma lição jogada ainda.</div><?php endif; ?>
    </div>
  </div>
</div>

<!-- Últimos usuários -->
<div class="card">
  <div class="page-header" style="margin-bottom:14px">
    <div class="page-header-left">
      <h2 style="font-size:15px">👤 Últimos Cadastros</h2>
    </div>
    <a href="users.php" class="btn btn-secondary btn-sm">Ver todos</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Usuário</th><th>E-mail</th><th>XP</th><th>Nível</th><th>Sequência</th><th>Cadastro</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:30px;height:30px;border-radius:50%;background:var(--pl);
                display:flex;align-items:center;justify-content:center;font-size:15px">
                <?= htmlspecialchars($u['avatar'] ?? '🦁') ?>
              </div>
              <span style="font-weight:700"><?= htmlspecialchars($u['name']) ?></span>
            </div>
          </td>
          <td style="color:var(--ts)"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge badge-yellow">⚡ <?= number_format($u['xp']) ?></span></td>
          <td><span class="badge badge-green">Nv. <?= $u['level'] ?></span></td>
          <td>🔥 <?= $u['streak'] ?> dias</td>
          <td style="color:var(--tm)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentUsers)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--tm);padding:24px">Nenhum usuário cadastrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
