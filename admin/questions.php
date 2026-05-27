<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
define('ADMIN_PAGE','Questões');

$pdo      = db();
$action   = $_GET['action']   ?? 'list';
$id       = (int)($_GET['id'] ?? 0);
$lessonId = (int)($_GET['lesson_id'] ?? 0);

// ── SALVAR ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = (int)$_POST['lesson_id'];
    $question  = trim($_POST['question']  ?? '');
    $opt_a     = trim($_POST['option_a']  ?? '');
    $opt_b     = trim($_POST['option_b']  ?? '');
    $opt_c     = trim($_POST['option_c']  ?? '');
    $opt_d     = trim($_POST['option_d']  ?? '');
    $correct   = (int)($_POST['correct']  ?? 0);
    $position  = (int)($_POST['position'] ?? 1);

    if (!$question || !$opt_a || !$opt_b || !$opt_c || !$opt_d || !$lesson_id) {
        setFlash('error','Preencha todos os campos obrigatórios.');
        header('Location: questions.php' . ($lessonId ? "?lesson_id=$lessonId" : '')); exit;
    }

    if ($id) {
        $pdo->prepare("UPDATE questions SET lesson_id=?,question=?,option_a=?,option_b=?,option_c=?,option_d=?,correct=?,position=? WHERE id=?")
            ->execute([$lesson_id,$question,$opt_a,$opt_b,$opt_c,$opt_d,$correct,$position,$id]);
        setFlash('success','Questão atualizada!');
    } else {
        $pdo->prepare("INSERT INTO questions (lesson_id,question,option_a,option_b,option_c,option_d,correct,position) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$lesson_id,$question,$opt_a,$opt_b,$opt_c,$opt_d,$correct,$position]);
        setFlash('success','Questão criada!');
    }
    header('Location: questions.php' . ($lessonId ? "?lesson_id=$lessonId" : '')); exit;
}

// ── EXCLUIR ───────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
    setFlash('success','Questão excluída.');
    header('Location: questions.php' . ($lessonId ? "?lesson_id=$lessonId" : '')); exit;
}

// ── LISTAGEM ──────────────────────────────────────────────────
$where = $lessonId ? "WHERE q.lesson_id = $lessonId" : '';
$questions = $pdo->query("
    SELECT q.*, l.title AS lesson_name, l.icon AS lesson_icon,
           c.title AS chapter_name, c.color AS chapter_color
    FROM questions q
    JOIN lessons l ON l.id = q.lesson_id
    JOIN chapters c ON c.id = l.chapter_id
    $where
    ORDER BY c.position, l.position, q.position
")->fetchAll();

// Todas as lições para o select
$allLessons = $pdo->query("
    SELECT l.id, l.title, l.icon, c.title AS chapter
    FROM lessons l JOIN chapters c ON c.id=l.chapter_id
    ORDER BY c.position, l.position
")->fetchAll();

$filterLesson = $lessonId ? $pdo->query("
    SELECT l.*, c.title AS chapter_name FROM lessons l
    JOIN chapters c ON c.id=l.chapter_id WHERE l.id=$lessonId")->fetch() : null;

$LETTERS = ['A','B','C','D'];

include __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h2>Questões <?= $filterLesson ? '— ' . htmlspecialchars($filterLesson['title']) : '' ?></h2>
    <p>
      <?= count($questions) ?> questões
      <?= $filterLesson ? 'nesta lição • ' . htmlspecialchars($filterLesson['chapter_name']) : 'no total' ?>
    </p>
  </div>
  <div style="display:flex;gap:8px">
    <?php if ($lessonId): ?>
    <a href="questions.php" class="btn btn-secondary btn-sm">Ver todas</a>
    <?php endif; ?>
    <button onclick="openModal()" class="btn btn-primary">+ Nova Questão</button>
  </div>
</div>

<!-- Filtro por lição -->
<?php if (!$lessonId): ?>
<div style="margin-bottom:16px">
  <select onchange="if(this.value) location.href='questions.php?lesson_id='+this.value"
    class="form-select" style="max-width:320px">
    <option value="">Filtrar por lição...</option>
    <?php foreach ($allLessons as $l): ?>
    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['icon'].' '.$l['title'].' — '.$l['chapter']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Questão</th><th>Lição</th><th>Opções</th><th>Correta</th><th>Pos.</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($questions as $q): ?>
        <tr>
          <td style="color:var(--tm)"><?= $q['id'] ?></td>
          <td style="max-width:240px">
            <div style="font-weight:600;font-size:13px;line-height:1.4">
              <?= htmlspecialchars(mb_substr($q['question'], 0, 80)) . (mb_strlen($q['question']) > 80 ? '…' : '') ?>
            </div>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:15px"><?= htmlspecialchars($q['lesson_icon']) ?></span>
              <div>
                <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($q['lesson_name']) ?></div>
                <div style="font-size:10px;color:var(--tm)"><?= htmlspecialchars($q['chapter_name']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-size:11px;color:var(--ts)">
              <?php foreach (['option_a','option_b','option_c','option_d'] as $i => $opt): ?>
              <div style="<?= $i==$q['correct']?'color:var(--pd);font-weight:700':'' ?>">
                <?= $LETTERS[$i] ?>: <?= htmlspecialchars(mb_substr($q[$opt], 0, 22)) ?>
              </div>
              <?php endforeach; ?>
            </div>
          </td>
          <td>
            <span class="badge badge-green">
              <?= $LETTERS[$q['correct']] ?>: <?= htmlspecialchars(mb_substr($q['option_' . ['a','b','c','d'][$q['correct']]], 0, 14)) ?>
            </span>
          </td>
          <td><?= $q['position'] ?>º</td>
          <td>
            <div style="display:flex;gap:6px">
              <button onclick='openModal(<?= htmlspecialchars(json_encode($q)) ?>)' class="btn btn-warning btn-sm">✏️ Editar</button>
              <form method="POST" action="?action=delete&id=<?= $q['id'] ?>&lesson_id=<?= $lessonId ?>"
                onsubmit="return confirm('Excluir esta questão?')">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">❓</div>
            <div class="empty-state-title">Nenhuma questão cadastrada</div>
            <button onclick="openModal()" class="btn btn-primary" style="margin-top:12px">Criar primeira questão</button>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay hidden" id="modal-overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Nova Questão</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" id="question-form">
      <div class="form-grid" style="gap:14px">

        <div class="form-group">
          <label class="form-label">Lição *</label>
          <select name="lesson_id" id="f-lesson" class="form-select" required>
            <option value="">Selecione uma lição</option>
            <?php foreach ($allLessons as $l): ?>
            <option value="<?= $l['id'] ?>"
              <?= ($lessonId == $l['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($l['icon'] . ' ' . $l['title'] . ' — ' . $l['chapter']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Pergunta *</label>
          <textarea name="question" id="f-question" class="form-textarea"
            placeholder="Escreva a pergunta aqui..." required style="min-height:70px"></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <?php foreach (['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k => $label): ?>
          <div class="form-group">
            <label class="form-label">Opção <?= $label ?> *</label>
            <input type="text" name="option_<?= $k ?>" id="f-opt-<?= $k ?>"
              class="form-input" placeholder="Opção <?= $label ?>" required>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Resposta Correta *</label>
          <div style="display:flex;gap:10px">
            <?php foreach (['0'=>'A','1'=>'B','2'=>'C','3'=>'D'] as $v => $lb): ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;
              background:var(--bl);border-radius:8px;padding:8px 14px;
              border:2px solid var(--bo);transition:all .15s;font-weight:700;font-size:13px"
              id="correct-label-<?= $v ?>">
              <input type="radio" name="correct" value="<?= $v ?>"
                onchange="highlightCorrect(<?= $v ?>)"
                <?= $v==0?'checked':'' ?> style="display:none">
              <?= $lb ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Posição</label>
          <input type="number" name="position" id="f-position" class="form-input" value="1" min="1">
        </div>

        <div style="display:flex;gap:10px;margin-top:4px">
          <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex:1">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="flex:2" id="f-submit">Criar Questão</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function highlightCorrect(v) {
  [0,1,2,3].forEach(i => {
    const el = document.getElementById('correct-label-'+i);
    el.style.background   = i==v ? 'var(--pl)' : 'var(--bl)';
    el.style.borderColor  = i==v ? 'var(--p)'  : 'var(--bo)';
    el.style.color        = i==v ? 'var(--pd)' : 'var(--tx)';
  });
}
highlightCorrect(0);

function openModal(data) {
  document.getElementById('modal-overlay').classList.remove('hidden');
  if (data) {
    document.getElementById('modal-title').textContent  = 'Editar Questão';
    document.getElementById('f-submit').textContent     = 'Salvar Alterações';
    document.getElementById('question-form').action     = '?action=edit&id=' + data.id + '&lesson_id=<?= $lessonId ?>';
    document.getElementById('f-lesson').value    = data.lesson_id;
    document.getElementById('f-question').value  = data.question;
    document.getElementById('f-opt-a').value     = data.option_a;
    document.getElementById('f-opt-b').value     = data.option_b;
    document.getElementById('f-opt-c').value     = data.option_c;
    document.getElementById('f-opt-d').value     = data.option_d;
    document.getElementById('f-position').value  = data.position;
    // Set correct radio
    document.querySelectorAll('[name="correct"]').forEach(r => r.checked = r.value == data.correct);
    highlightCorrect(parseInt(data.correct));
  } else {
    document.getElementById('modal-title').textContent = 'Nova Questão';
    document.getElementById('f-submit').textContent    = 'Criar Questão';
    document.getElementById('question-form').action    = '?lesson_id=<?= $lessonId ?>';
    document.getElementById('question-form').reset();
    document.querySelectorAll('[name="correct"]')[0].checked = true;
    highlightCorrect(0);
  }
}
function closeModal() { document.getElementById('modal-overlay').classList.add('hidden'); }
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
