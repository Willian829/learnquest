<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
define('ADMIN_PAGE','Lições');

$pdo        = db();
$action     = $_GET['action']     ?? 'list';
$id         = (int)($_GET['id']   ?? 0);
$chapterId  = (int)($_GET['chapter_id'] ?? 0);

// ── SALVAR ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter_id = (int)$_POST['chapter_id'];
    $title      = trim($_POST['title']    ?? '');
    $icon       = trim($_POST['icon']     ?? '📖');
    $position   = (int)($_POST['position'] ?? 1);
    $xp_reward  = (int)($_POST['xp_reward']  ?? 30);
    $gem_reward = (int)($_POST['gem_reward'] ?? 5);

    if (!$title || !$chapter_id) { setFlash('error','Título e capítulo obrigatórios.'); header('Location: lessons.php'); exit; }

    if ($id) {
        $pdo->prepare("UPDATE lessons SET chapter_id=?,title=?,icon=?,position=?,xp_reward=?,gem_reward=? WHERE id=?")
            ->execute([$chapter_id,$title,$icon,$position,$xp_reward,$gem_reward,$id]);
        setFlash('success','Lição atualizada!');
    } else {
        $pdo->prepare("INSERT INTO lessons (chapter_id,title,icon,position,xp_reward,gem_reward) VALUES (?,?,?,?,?,?)")
            ->execute([$chapter_id,$title,$icon,$position,$xp_reward,$gem_reward]);
        setFlash('success','Lição criada!');
    }
    header('Location: lessons.php' . ($chapterId ? "?chapter_id=$chapterId" : '')); exit;
}

// ── EXCLUIR ───────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM lessons WHERE id=?")->execute([$id]);
    setFlash('success','Lição excluída.');
    header('Location: lessons.php' . ($chapterId ? "?chapter_id=$chapterId" : '')); exit;
}

// ── LISTAGEM ──────────────────────────────────────────────────
$where  = $chapterId ? "WHERE l.chapter_id = $chapterId" : '';
$lessons = $pdo->query("
    SELECT l.*, c.title AS chapter_name, c.color AS chapter_color,
           COUNT(q.id) AS q_count,
           COUNT(ul.id) AS play_count
    FROM lessons l
    JOIN chapters c ON c.id = l.chapter_id
    LEFT JOIN questions q ON q.lesson_id = l.id
    LEFT JOIN user_lessons ul ON ul.lesson_id = l.id
    $where
    GROUP BY l.id ORDER BY c.position, l.position
")->fetchAll();

$chapters = $pdo->query("SELECT * FROM chapters ORDER BY position")->fetchAll();
$filterChapter = $chapterId ? $pdo->query("SELECT * FROM chapters WHERE id=$chapterId")->fetch() : null;

$ICONS = ['📖','💡','👋','👟','🌏','🔍','🧱','⚔️','🛡️','👑','🎯','🔭','🚀','🌟','🧠','🔬','🎨','📝'];

include __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h2>Lições <?= $filterChapter ? '— ' . htmlspecialchars($filterChapter['title']) : '' ?></h2>
    <p><?= count($lessons) ?> lições <?= $chapterId ? 'neste capítulo' : 'no total' ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <?php if ($chapterId): ?>
    <a href="lessons.php" class="btn btn-secondary btn-sm">Ver todas</a>
    <?php endif; ?>
    <button onclick="openModal()" class="btn btn-primary">+ Nova Lição</button>
  </div>
</div>

<!-- Filtro por capítulo -->
<div class="search-bar" style="margin-bottom:16px">
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="lessons.php" class="btn btn-sm <?= !$chapterId?'btn-primary':'btn-secondary' ?>">Todos</a>
    <?php foreach ($chapters as $ch): ?>
    <a href="lessons.php?chapter_id=<?= $ch['id'] ?>"
       class="btn btn-sm <?= $chapterId==$ch['id']?'btn-primary':'btn-secondary' ?>">
      <?= htmlspecialchars($ch['icon']) ?> <?= htmlspecialchars($ch['title']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Lição</th><th>Capítulo</th><th>Pos.</th><th>XP</th><th>Gemas</th><th>Questões</th><th>Jogadas</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($lessons as $l): ?>
        <tr>
          <td style="color:var(--tm)"><?= $l['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:34px;height:34px;border-radius:9px;
                background:<?= htmlspecialchars($l['chapter_color']) ?>22;
                display:flex;align-items:center;justify-content:center;font-size:17px">
                <?= htmlspecialchars($l['icon']) ?>
              </div>
              <span style="font-weight:700"><?= htmlspecialchars($l['title']) ?></span>
            </div>
          </td>
          <td>
            <span class="badge badge-purple"><?= htmlspecialchars($l['chapter_name']) ?></span>
          </td>
          <td><?= $l['position'] ?>º</td>
          <td><span class="badge badge-yellow">⚡ <?= $l['xp_reward'] ?></span></td>
          <td><span class="badge badge-blue">💎 <?= $l['gem_reward'] ?></span></td>
          <td>
            <a href="questions.php?lesson_id=<?= $l['id'] ?>" style="font-weight:700;color:var(--ac)">
              <?= $l['q_count'] ?> questões
            </a>
          </td>
          <td style="color:var(--ts)"><?= (int)$l['play_count'] ?>×</td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="questions.php?lesson_id=<?= $l['id'] ?>" class="btn btn-secondary btn-sm">❓ Q&A</a>
              <button onclick='openModal(<?= htmlspecialchars(json_encode($l)) ?>)' class="btn btn-warning btn-sm">✏️</button>
              <form method="POST" action="?action=delete&id=<?= $l['id'] ?>&chapter_id=<?= $chapterId ?>"
                onsubmit="return confirm('Excluir lição e todas as questões?')">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($lessons)): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-title">Nenhuma lição criada</div>
            <button onclick="openModal()" class="btn btn-primary" style="margin-top:12px">Criar primeira lição</button>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay hidden" id="modal-overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Nova Lição</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" id="lesson-form">
      <div class="form-grid" style="gap:14px">
        <div class="form-group">
          <label class="form-label">Capítulo *</label>
          <select name="chapter_id" id="f-chapter" class="form-select" required>
            <?php foreach ($chapters as $ch): ?>
            <option value="<?= $ch['id'] ?>"
              <?= ($chapterId == $ch['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ch['icon']) ?> <?= htmlspecialchars($ch['title']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Título *</label>
          <input type="text" name="title" id="f-title" class="form-input" placeholder="Ex: Introdução" required>
        </div>
        <div class="form-group">
          <label class="form-label">Ícone</label>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
            <?php foreach ($ICONS as $ic): ?>
            <button type="button" onclick="selectIcon('<?= $ic ?>')"
              class="icon-btn"
              style="width:34px;height:34px;border-radius:8px;border:2px solid var(--bo);
                background:var(--bl);font-size:18px;cursor:pointer;transition:all .15s">
              <?= $ic ?>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="icon" id="f-icon" value="📖">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Posição</label>
            <input type="number" name="position" id="f-position" class="form-input" value="1" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">XP</label>
            <input type="number" name="xp_reward" id="f-xp" class="form-input" value="30" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Gemas</label>
            <input type="number" name="gem_reward" id="f-gem" class="form-input" value="5" min="0">
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px">
          <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex:1">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="flex:2" id="f-submit">Criar Lição</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(data) {
  document.getElementById('modal-overlay').classList.remove('hidden');
  if (data) {
    document.getElementById('modal-title').textContent = 'Editar Lição';
    document.getElementById('f-submit').textContent    = 'Salvar Alterações';
    document.getElementById('lesson-form').action      = '?action=edit&id=' + data.id;
    document.getElementById('f-chapter').value  = data.chapter_id;
    document.getElementById('f-title').value    = data.title;
    document.getElementById('f-icon').value     = data.icon;
    document.getElementById('f-position').value = data.position;
    document.getElementById('f-xp').value       = data.xp_reward;
    document.getElementById('f-gem').value      = data.gem_reward;
  } else {
    document.getElementById('modal-title').textContent = 'Nova Lição';
    document.getElementById('f-submit').textContent    = 'Criar Lição';
    document.getElementById('lesson-form').action = '';
    document.getElementById('lesson-form').reset();
  }
}
function closeModal() { document.getElementById('modal-overlay').classList.add('hidden'); }
function selectIcon(ic) {
  document.getElementById('f-icon').value = ic;
  document.querySelectorAll('.icon-btn').forEach(b => {
    const match = b.textContent.trim() === ic;
    b.style.borderColor = match ? 'var(--p)' : 'var(--bo)';
    b.style.background  = match ? 'var(--pl)' : 'var(--bl)';
  });
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
