<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = currentUser();
$pdo  = db();

// Salva avatar se POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avatar'])) {
    $allowed = ['🦁','🐺','🦊','🐼','🦋','🐸','🦅','🐬','🐯','🦄','🐲','🦉'];
    $av = $_POST['avatar'];
    if (in_array($av, $allowed)) {
        $pdo->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$av, $user['id']]);
        header('Location: profile.php'); exit;
    }
}

// Conquistas (todas + status do usuário)
$achStmt = $pdo->prepare("
    SELECT a.*, ua.earned_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
    ORDER BY ua.earned_at DESC, a.id ASC
");
$achStmt->execute([$user['id']]);
$achievements = $achStmt->fetchAll();

// Lições completadas
$lessonsStmt = $pdo->prepare("SELECT COUNT(*) FROM user_lessons WHERE user_id=? AND completed=1");
$lessonsStmt->execute([$user['id']]);
$lessonsCount = (int)$lessonsStmt->fetchColumn();

// Recalcula XP bar
$xpInLv = xpInLevel((int)$user['xp']);
$xpPct  = min(round(($xpInLv / XP_PER_LEVEL) * 100), 100);

$AVATARS = ['🦁','🐺','🦊','🐼','🦋','🐸','🦅','🐬','🐯','🦄','🐲','🦉'];

define('PAGE_TITLE', 'Perfil');
$activeTab = 'profile';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="page">

  <!-- Hero do perfil -->
  <div class="profile-hero">
    <div class="profile-avatar"><?= htmlspecialchars($user['avatar']) ?></div>
    <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
    <p class="profile-level">Nível <?= (int)$user['level'] ?> &nbsp;•&nbsp; Membro desde <?= date('M/Y', strtotime($user['created_at'])) ?></p>
    <div class="xp-bar-wrap" style="max-width:300px;margin:0 auto">
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

  <!-- Stats rápidos -->
  <div class="stat-pills" style="justify-content:center;margin-bottom:16px">
    <div class="stat-pill">
      <span class="sp-icon">🔥</span>
      <span class="sp-val" style="color:var(--wa)"><?= (int)$user['streak'] ?></span>
      <span class="sp-lbl">Sequência</span>
    </div>
    <div class="stat-pill">
      <span class="sp-icon">📚</span>
      <span class="sp-val" style="color:var(--p)"><?= $lessonsCount ?></span>
      <span class="sp-lbl">Lições</span>
    </div>
    <div class="stat-pill">
      <span class="sp-icon">💎</span>
      <span class="sp-val" style="color:var(--in)"><?= (int)$user['gems'] ?></span>
      <span class="sp-lbl">Gemas</span>
    </div>
    <div class="stat-pill">
      <span class="sp-icon">🏅</span>
      <span class="sp-val" style="color:var(--ac)"><?= count(array_filter($achievements, fn($a) => $a['earned_at'])) ?></span>
      <span class="sp-lbl">Medalhas</span>
    </div>
  </div>

  <!-- Escolher avatar -->
  <div class="section-title">🦁 Escolher Avatar</div>
  <form method="POST" style="margin-bottom:16px">
    <div class="avatar-grid">
      <?php foreach ($AVATARS as $av): ?>
      <button type="submit" name="avatar" value="<?= htmlspecialchars($av) ?>"
        class="avatar-btn <?= $user['avatar'] === $av ? 'selected' : '' ?>">
        <?= htmlspecialchars($av) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </form>

  <!-- Conquistas -->
  <div class="section-title">
    🏅 Conquistas
    <span><?= count(array_filter($achievements, fn($a)=>$a['earned_at'])) ?>/<?= count($achievements) ?></span>
  </div>
  <div class="medals-grid" style="margin-bottom:16px">
    <?php foreach ($achievements as $a):
      $earned = !empty($a['earned_at']);
    ?>
    <div class="medal-card <?= !$earned ? 'locked' : '' ?>">
      <span class="medal-icon"><?= $earned ? htmlspecialchars($a['icon']) : '🔒' ?></span>
      <div class="medal-title"><?= htmlspecialchars($a['title']) ?></div>
      <div class="medal-desc"><?= htmlspecialchars($a['description']) ?></div>
      <?php if ($earned): ?>
      <div style="font-size:10px;color:var(--p);font-weight:700;margin-top:4px">
        ✓ <?= date('d/m/Y', strtotime($a['earned_at'])) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Configurações -->
  <div class="section-title">⚙️ Conta</div>
  <div class="card" style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;
      padding:10px 0;border-bottom:1px solid var(--bo)">
      <div>
        <div style="font-weight:700;font-size:13px;color:var(--tx)">E-mail</div>
        <div style="font-size:12px;color:var(--tx-m)"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <span style="font-size:16px">📧</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0">
      <div>
        <div style="font-weight:700;font-size:13px;color:var(--tx)">Membro desde</div>
        <div style="font-size:12px;color:var(--tx-m)"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
      </div>
      <span style="font-size:16px">📅</span>
    </div>
  </div>

  <!-- Sair -->
  <a href="../api/logout.php" class="btn btn-danger btn-full"
    onclick="return confirm('Deseja sair da sua conta?')">
    Sair da conta
  </a>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
