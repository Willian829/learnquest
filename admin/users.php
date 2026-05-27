<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
define('ADMIN_PAGE','Usuários');

$pdo    = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── EDITAR XP/NÍVEL manual ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $xp     = max(0, (int)$_POST['xp']);
    $gems   = max(0, (int)$_POST['gems']);
    $hearts = max(0, (int)$_POST['hearts']);
    $streak = max(0, (int)$_POST['streak']);
    $level  = calcLevel($xp);
    $pdo->prepare("UPDATE users SET xp=?,gems=?,hearts=?,streak=?,level=? WHERE id=?")
        ->execute([$xp,$gems,$hearts,$streak,$level,$id]);
    setFlash('success','Usuário atualizado!');
    header('Location: users.php'); exit;
}

// ── EXCLUIR ───────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    setFlash('success','Usuário excluído.');
    header('Location: users.php'); exit;
}

// ── RESETAR PROGRESSO ─────────────────────────────────────────
if ($action === 'reset_progress' && $id) {
    $pdo->prepare("DELETE FROM user_lessons WHERE user_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM user_missions WHERE user_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM user_achievements WHERE user_id=?")->execute([$id]);
    $pdo->prepare("UPDATE users SET xp=0,level=1,gems=0,streak=0,hearts=5 WHERE id=?")->execute([$id]);
    setFlash('success','Progresso do usuário resetado.');
    header('Location: users.php'); exit;
}

// ── LISTAGEM COM BUSCA ────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$whereSQL = $search ? "WHERE name LIKE ? OR email LIKE ?" : '';
$params   = $search ? ["%$search%", "%$search%"] : [];

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$usersStmt = $pdo->prepare("
    SELECT u.*,
      (SELECT COUNT(*) FROM user_lessons WHERE user_id=u.id AND completed=1) AS lessons_done,
      (SELECT COUNT(*) FROM user_achievements WHERE user_id=u.id) AS medals
    FROM users u $whereSQL
    ORDER BY u.xp DESC
    LIMIT $perPage OFFSET $offset
");
$usersStmt->execute($params);
$users = $usersStmt->fetchAll();

// Usuário para edição
$editUser = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$id]);
    $editUser = $s->fetch();
}

include __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h2>Usuários</h2>
    <p><?= number_format($total) ?> usuários cadastrados</p>
  </div>
</div>

<!-- Busca -->
<div class="search-bar">
  <div class="search-input-wrap">
    <span class="search-icon">🔍</span>
    <form method="GET">
      <input type="text" name="q" class="form-input" placeholder="Buscar por nome ou e-mail..."
        value="<?= htmlspecialchars($search) ?>" style="padding-left:36px">
    </form>
  </div>
  <?php if ($search): ?>
  <a href="users.php" class="btn btn-secondary">✕ Limpar</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Usuário</th><th>E-mail</th><th>XP / Nível</th><th>Streak</th><th>Lições</th><th>Medalhas</th><th>Cadastro</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:34px;height:34px;border-radius:50%;
                background:linear-gradient(135deg,var(--p)30,var(--ac)30);
                display:flex;align-items:center;justify-content:center;font-size:18px">
                <?= htmlspecialchars($u['avatar']) ?>
              </div>
              <span style="font-weight:700"><?= htmlspecialchars($u['name']) ?></span>
            </div>
          </td>
          <td style="color:var(--ts);font-size:12px"><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <div style="font-weight:800;color:var(--wa)">⚡ <?= number_format($u['xp']) ?></div>
            <div style="font-size:11px;color:var(--tm)">Nível <?= $u['level'] ?></div>
          </td>
          <td><?= $u['streak'] > 0 ? '🔥 '.$u['streak'].' dias' : '—' ?></td>
          <td><span class="badge badge-green">✅ <?= $u['lessons_done'] ?></span></td>
          <td><span class="badge badge-purple">🏅 <?= $u['medals'] ?></span></td>
          <td style="font-size:12px;color:var(--tm)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <button onclick='openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)'
                class="btn btn-warning btn-sm">✏️ Editar</button>
              <button onclick="if(confirm('Resetar TODO o progresso de <?= htmlspecialchars(addslashes($u['name'])) ?>?')) location.href='?action=reset_progress&id=<?= $u['id'] ?>'"
                class="btn btn-secondary btn-sm">🔄 Reset</button>
              <button onclick="if(confirm('EXCLUIR o usuário <?= htmlspecialchars(addslashes($u['name'])) ?> permanentemente?')) location.href='?action=delete&id=<?= $u['id'] ?>'"
                class="btn btn-danger btn-sm">🗑️</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <div class="empty-state-title">Nenhum usuário encontrado</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Paginação -->
<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
  <a href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"
     class="page-btn <?= $p === $page ? 'active' : '' ?>">
    <?= $p ?>
  </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- MODAL EDITAR USUÁRIO -->
<div class="modal-overlay hidden" id="edit-modal" onclick="if(event.target===this)closeEditModal()">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">✏️ Editar Usuário</div>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form method="POST" id="edit-form">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;
        background:var(--bl);border-radius:12px;padding:12px">
        <div id="em-avatar" style="width:44px;height:44px;border-radius:50%;
          background:linear-gradient(135deg,var(--p),var(--ac));
          display:flex;align-items:center;justify-content:center;font-size:24px">🦁</div>
        <div>
          <div id="em-name" style="font-weight:800;font-size:15px;color:var(--tx)">—</div>
          <div id="em-email" style="font-size:12px;color:var(--tm)">—</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="form-group">
          <label class="form-label">XP Total</label>
          <input type="number" name="xp" id="em-xp" class="form-input" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Gemas 💎</label>
          <input type="number" name="gems" id="em-gems" class="form-input" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Vidas ❤️</label>
          <input type="number" name="hearts" id="em-hearts" class="form-input" min="0" max="5">
        </div>
        <div class="form-group">
          <label class="form-label">Streak 🔥</label>
          <input type="number" name="streak" id="em-streak" class="form-input" min="0">
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex:1">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="flex:2">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(u) {
  document.getElementById('edit-modal').classList.remove('hidden');
  document.getElementById('edit-form').action = '?action=edit&id=' + u.id;
  document.getElementById('em-avatar').textContent = u.avatar;
  document.getElementById('em-name').textContent   = u.name;
  document.getElementById('em-email').textContent  = u.email;
  document.getElementById('em-xp').value     = u.xp;
  document.getElementById('em-gems').value   = u.gems;
  document.getElementById('em-hearts').value = u.hearts;
  document.getElementById('em-streak').value = u.streak;
}
function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
