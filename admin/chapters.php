<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
define('ADMIN_PAGE','Capítulos');

$pdo    = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── SALVAR (criar ou editar) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $icon     = trim($_POST['icon']     ?? '📚');
    $color    = trim($_POST['color']    ?? '#10B981');
    $position = (int)($_POST['position'] ?? 1);
    $xp_req   = (int)($_POST['xp_required'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if (!$title) { setFlash('error','Título obrigatório.'); header('Location: chapters.php'); exit; }

    if ($id) {
        $pdo->prepare("UPDATE chapters SET title=?,icon=?,color=?,position=?,xp_required=?,description=? WHERE id=?")
            ->execute([$title,$icon,$color,$position,$xp_req,$desc,$id]);
        setFlash('success','Capítulo atualizado com sucesso!');
    } else {
        $pdo->prepare("INSERT INTO chapters (title,icon,color,position,xp_required,description) VALUES (?,?,?,?,?,?)")
            ->execute([$title,$icon,$color,$position,$xp_req,$desc]);
        setFlash('success','Capítulo criado com sucesso!');
    }
    header('Location: chapters.php'); exit;
}

// ── EXCLUIR ───────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM chapters WHERE id=?")->execute([$id]);
    setFlash('success','Capítulo excluído.');
    header('Location: chapters.php'); exit;
}

// ── CARREGAR PARA EDIÇÃO ──────────────────────────────────────
$editing = null;
if (($action === 'edit') && $id) {
    $s = $pdo->prepare("SELECT * FROM chapters WHERE id=?");
    $s->execute([$id]);
    $editing = $s->fetch();
    if (!$editing) { header('Location: chapters.php'); exit; }
}

// ── LISTAGEM ──────────────────────────────────────────────────
$chapters = $pdo->query("
    SELECT c.*, COUNT(l.id) AS lesson_count
    FROM chapters c LEFT JOIN lessons l ON l.chapter_id=c.id
    GROUP BY c.id ORDER BY c.position
")->fetchAll();

$COLORS = ['#10B981','#8B5CF6','#F59E0B','#EF4444','#3B82F6','#EC4899','#14B8A6','#F97316'];
$ICONS  = ['🌱','🔭','🚀','🎯','💡','🔥','🧠','🌟','⚔️','🏆','📖','🎮','🌍','🔬','🎨'];

include __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h2>Capítulos</h2>
    <p>Organize os mundos de aprendizado</p>
  </div>
  <button onclick="openModal()" class="btn btn-primary">+ Novo Capítulo</button>
</div>

<!-- TABELA -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Capítulo</th><th>Cor</th><th>Posição</th><th>XP Mín.</th><th>Lições</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($chapters as $ch): ?>
        <tr>
          <td style="color:var(--tm)"><?= $ch['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:36px;height:36px;border-radius:10px;
                background:<?= htmlspecialchars($ch['color']) ?>22;
                display:flex;align-items:center;justify-content:center;font-size:18px">
                <?= htmlspecialchars($ch['icon']) ?>
              </div>
              <div>
                <div style="font-weight:700"><?= htmlspecialchars($ch['title']) ?></div>
                <div style="font-size:11px;color:var(--tm)"><?= htmlspecialchars(mb_substr($ch['description']??'',0,40)) ?></div>
              </div>
            </div>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:6px">
              <div style="width:18px;height:18px;border-radius:5px;background:<?= htmlspecialchars($ch['color']) ?>"></div>
              <code style="font-size:11px;color:var(--tm)"><?= htmlspecialchars($ch['color']) ?></code>
            </div>
          </td>
          <td><span class="badge badge-blue"><?= $ch['position'] ?>º</span></td>
          <td><?= number_format($ch['xp_required']) ?> XP</td>
          <td><span class="badge badge-green"><?= $ch['lesson_count'] ?> lições</span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="lessons.php?chapter_id=<?= $ch['id'] ?>" class="btn btn-secondary btn-sm">📚 Lições</a>
              <button onclick="openModal(<?= htmlspecialchars(json_encode($ch)) ?>)" class="btn btn-warning btn-sm">✏️</button>
              <form method="POST" action="?action=delete&id=<?= $ch['id'] ?>" style="display:inline"
                onsubmit="return confirm('Excluir capítulo e TODAS as lições e questões vinculadas?')">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($chapters)): ?>
        <tr><td colspan="7">
          <div class="empty-state"><div class="empty-state-icon">📂</div>
            <div class="empty-state-title">Nenhum capítulo criado</div>
            <button onclick="openModal()" class="btn btn-primary" style="margin-top:12px">Criar primeiro capítulo</button>
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
      <div class="modal-title" id="modal-title">Novo Capítulo</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" id="chapter-form">
      <input type="hidden" name="_id" id="f-id" value="">
      <div class="form-grid" style="gap:14px">
        <div class="form-group">
          <label class="form-label">Título *</label>
          <input type="text" name="title" id="f-title" class="form-input" placeholder="Ex: Fundamentos" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Ícone</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
              <?php foreach ($ICONS as $ic): ?>
              <button type="button" class="icon-btn" onclick="selectIcon('<?= $ic ?>')"
                style="width:34px;height:34px;border-radius:8px;border:2px solid var(--bo);background:var(--bl);
                  font-size:18px;cursor:pointer;transition:all .15s">
                <?= $ic ?>
              </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="icon" id="f-icon" value="📚">
          </div>
          <div class="form-group">
            <label class="form-label">Cor</label>
            <div class="color-options" style="margin-bottom:6px">
              <?php foreach ($COLORS as $cl): ?>
              <div class="color-swatch" style="background:<?= $cl ?>"
                onclick="selectColor('<?= $cl ?>')"></div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="color" id="f-color" value="#10B981">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Posição</label>
            <input type="number" name="position" id="f-position" class="form-input" value="1" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">XP mínimo</label>
            <input type="number" name="xp_required" id="f-xp" class="form-input" value="0" min="0">
            <span class="form-hint">XP para desbloquear</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Descrição</label>
          <textarea name="description" id="f-desc" class="form-textarea" placeholder="Descrição opcional..."></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px">
          <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex:1">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="flex:2" id="f-submit">Criar Capítulo</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(data) {
  const overlay = document.getElementById('modal-overlay');
  overlay.classList.remove('hidden');
  if (data) {
    document.getElementById('modal-title').textContent = 'Editar Capítulo';
    document.getElementById('f-submit').textContent    = 'Salvar Alterações';
    document.getElementById('chapter-form').action     = '?action=edit&id=' + data.id;
    document.getElementById('f-id').value       = data.id;
    document.getElementById('f-title').value    = data.title;
    document.getElementById('f-icon').value     = data.icon;
    document.getElementById('f-color').value    = data.color;
    document.getElementById('f-position').value = data.position;
    document.getElementById('f-xp').value       = data.xp_required;
    document.getElementById('f-desc').value     = data.description || '';
    selectColor(data.color);
  } else {
    document.getElementById('modal-title').textContent = 'Novo Capítulo';
    document.getElementById('f-submit').textContent    = 'Criar Capítulo';
    document.getElementById('chapter-form').reset();
    document.getElementById('chapter-form').action = '';
  }
}
function closeModal() { document.getElementById('modal-overlay').classList.add('hidden'); }
function selectColor(c) {
  document.getElementById('f-color').value = c;
  document.querySelectorAll('.color-swatch').forEach(s => {
    s.classList.toggle('selected', s.style.background === c);
  });
}
function selectIcon(ic) {
  document.getElementById('f-icon').value = ic;
  document.querySelectorAll('.icon-btn').forEach(b => {
    b.style.borderColor = b.textContent.trim() === ic ? 'var(--p)' : 'var(--bo)';
    b.style.background  = b.textContent.trim() === ic ? 'var(--pl)' : 'var(--bl)';
  });
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
