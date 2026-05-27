<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user      = currentUser();
$lessonId  = (int)($_GET['id'] ?? 0);
$pdo       = db();

if (!$lessonId) {
    header('Location: map.php'); exit;
}

// Carrega lição e capítulo
$stmt = $pdo->prepare("SELECT l.*, c.title AS chapter_title, c.color AS chapter_color, c.xp_required
    FROM lessons l JOIN chapters c ON c.id = l.chapter_id WHERE l.id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();
if (!$lesson) { header('Location: map.php'); exit; }

// Verifica se capítulo está desbloqueado
if ((int)$user['xp'] < (int)$lesson['xp_required']) {
    header('Location: map.php'); exit;
}

// Questões da lição
$qs = $pdo->prepare("SELECT * FROM questions WHERE lesson_id = ? ORDER BY position");
$qs->execute([$lessonId]);
$questions = $qs->fetchAll();
if (!$questions) { header('Location: map.php'); exit; }

// Progresso anterior
$prev = $pdo->prepare("SELECT * FROM user_lessons WHERE user_id=? AND lesson_id=?");
$prev->execute([$user['id'], $lessonId]);
$progress = $prev->fetch();

define('PAGE_TITLE', $lesson['title']);
$activeTab = 'map';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="lesson-wrap" id="lesson-app">

  <!-- === TELA INTRO === -->
  <div id="screen-intro">
    <div style="text-align:center;padding:20px 0 24px">
      <div style="width:80px;height:80px;border-radius:24px;
        background:<?= htmlspecialchars($lesson['chapter_color']) ?>22;
        display:flex;align-items:center;justify-content:center;
        font-size:40px;margin:0 auto 16px">
        <?= htmlspecialchars($lesson['icon']) ?>
      </div>
      <div style="font-size:12px;color:var(--tx-m);font-weight:700;margin-bottom:4px">
        <?= htmlspecialchars($lesson['chapter_title']) ?>
      </div>
      <h1 style="font-size:24px;font-weight:900;color:var(--tx);margin-bottom:8px">
        <?= htmlspecialchars($lesson['title']) ?>
      </h1>
      <p style="font-size:14px;color:var(--tx-s);margin-bottom:24px">
        <?= count($questions) ?> questões &nbsp;•&nbsp;
        +<?= (int)$lesson['xp_reward'] ?> XP &nbsp;•&nbsp;
        +<?= (int)$lesson['gem_reward'] ?> 💎
      </p>

      <?php if ($progress && $progress['completed']): ?>
      <div style="background:var(--p-light);border-radius:var(--radius);padding:12px 16px;
        margin-bottom:20px;display:flex;align-items:center;gap:10px;text-align:left">
        <span style="font-size:20px">⭐</span>
        <div>
          <div style="font-weight:700;font-size:13px;color:var(--p-dark)">Já concluída!</div>
          <div style="font-size:11px;color:var(--tx-s)">Melhor: <?= (int)$progress['best_score'] ?>% de acertos</div>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px">
        <a href="map.php" class="btn btn-secondary" style="flex:1">← Voltar</a>
        <button onclick="startLesson()" class="btn btn-primary" style="flex:2">
          <?= ($progress && $progress['completed']) ? '🔁 Jogar novamente' : '▶ Começar' ?>
        </button>
      </div>
    </div>
  </div>

  <!-- === TELA QUIZ === -->
  <div id="screen-quiz" style="display:none">
    <div class="lesson-topbar">
      <button class="lesson-close" onclick="confirmExit()">✕</button>
      <div class="lesson-progress">
        <div class="lesson-progress-fill" id="quiz-bar" style="width:0%"></div>
      </div>
      <div class="lesson-step" id="quiz-step">0/<?= count($questions) ?></div>
    </div>

    <div id="hearts-row" style="display:flex;gap:6px;margin-bottom:18px;justify-content:center">
      <?php for ($h=0;$h<5;$h++): ?>
      <span class="heart-icon" style="font-size:18px">❤️</span>
      <?php endfor; ?>
    </div>

    <p class="question-text" id="question-text"></p>

    <div class="options-list" id="options-list"></div>

    <button id="btn-next" class="btn btn-primary btn-full btn-lg"
      style="margin-top:18px;display:none" onclick="nextQuestion()">
      Próxima →
    </button>
  </div>

  <!-- === TELA RESULTADO === -->
  <div id="screen-result" style="display:none">
    <div class="lesson-result">
      <div class="result-emoji" id="result-emoji">🎉</div>
      <h2 class="result-title" id="result-title">Lição Completa!</h2>
      <p class="result-sub"  id="result-sub"></p>
      <div class="result-stats" id="result-stats"></div>
      <div style="display:flex;gap:10px;margin-top:8px;width:100%;max-width:320px">
        <a href="map.php" class="btn btn-secondary" style="flex:1">🗺️ Mapa</a>
        <button onclick="location.reload()" class="btn btn-primary" style="flex:2">
          🔁 Jogar de novo
        </button>
      </div>
    </div>
  </div>

</div><!-- /lesson-wrap -->

<script>
const QUESTIONS = <?= json_encode(array_map(fn($q) => [
  'id'      => (int)$q['id'],
  'text'    => $q['question'],
  'options' => [$q['option_a'], $q['option_b'], $q['option_c'], $q['option_d']],
  'correct' => (int)$q['correct'],
], $questions)) ?>;

const LESSON_ID  = <?= $lessonId ?>;
const XP_REWARD  = <?= (int)$lesson['xp_reward'] ?>;
const GEM_REWARD = <?= (int)$lesson['gem_reward'] ?>;
const TOTAL      = QUESTIONS.length;

let step    = 0;
let correct = 0;
let hearts  = 5;
let answered = false;

function startLesson() {
  document.getElementById('screen-intro').style.display = 'none';
  document.getElementById('screen-quiz').style.display  = '';
  step = 0; correct = 0; hearts = 5; answered = false;
  renderQuestion();
}

function renderQuestion() {
  const q = QUESTIONS[step];
  answered = false;

  // Barra de progresso
  document.getElementById('quiz-bar').style.width = (step / TOTAL * 100) + '%';
  document.getElementById('quiz-step').textContent = step + '/' + TOTAL;

  // Texto
  document.getElementById('question-text').textContent = q.text;

  // Opções
  const list = document.getElementById('options-list');
  list.innerHTML = '';
  const letters = ['A','B','C','D'];
  q.options.forEach((opt, i) => {
    const btn = document.createElement('button');
    btn.className = 'option-btn';
    btn.innerHTML = `
      <div class="option-letter">${letters[i]}</div>
      <span class="option-text">${opt}</span>`;
    btn.addEventListener('click', () => selectAnswer(i, btn));
    list.appendChild(btn);
  });

  document.getElementById('btn-next').style.display = 'none';
}

function selectAnswer(idx, btn) {
  if (answered) return;
  answered = true;

  const q = QUESTIONS[step];
  const buttons = document.querySelectorAll('.option-btn');

  buttons.forEach(b => b.disabled = true);

  if (idx === q.correct) {
    btn.classList.add('correct');
    btn.querySelector('.option-letter').textContent = '✓';
    correct++;
  } else {
    btn.classList.add('wrong');
    btn.querySelector('.option-letter').textContent = '✗';
    // Mostra a correta
    buttons[q.correct].classList.add('correct');
    buttons[q.correct].querySelector('.option-letter').textContent = '✓';
    loseHeart();
  }

  const nextBtn = document.getElementById('btn-next');
  nextBtn.style.display = '';
  nextBtn.textContent = (step + 1 >= TOTAL) ? 'Ver Resultado' : 'Próxima →';
}

function loseHeart() {
  if (hearts <= 0) return;
  hearts--;
  const heartIcons = document.querySelectorAll('.heart-icon');
  if (heartIcons[hearts]) heartIcons[hearts].textContent = '🖤';
  if (hearts === 0) {
    document.getElementById('btn-next').textContent = 'Ver Resultado';
  }
}

function nextQuestion() {
  step++;
  if (step >= TOTAL || hearts === 0) {
    finishLesson();
  } else {
    renderQuestion();
  }
}

function finishLesson() {
  const pct   = Math.round((correct / TOTAL) * 100);
  const stars  = pct === 100 ? 3 : pct >= 66 ? 2 : pct >= 33 ? 1 : 0;
  const xpEarn = Math.round(XP_REWARD * (pct / 100));
  const gemEarn= pct >= 50 ? GEM_REWARD : 0;

  // Salva no servidor
  fetch('/learnquest/api/complete_lesson.php', {
    method : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body   : JSON.stringify({
      lesson_id: LESSON_ID,
      correct,
      total   : TOTAL,
      stars,
      xp      : xpEarn,
      gems    : gemEarn,
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.achievements && data.achievements.length) {
      setTimeout(() => {
        data.achievements.forEach(a =>
          showAchievementToast(a.icon, a.title, a.description));
      }, 600);
    }
  });

  // Mostra tela resultado
  document.getElementById('screen-quiz').style.display   = 'none';
  document.getElementById('screen-result').style.display = '';

  const emojis  = ['😓','🙂','😊','🎉'];
  document.getElementById('result-emoji').textContent = emojis[stars] ?? '🎉';
  document.getElementById('result-title').textContent =
    pct === 100 ? 'Perfeito! 💎' : pct >= 66 ? 'Muito Bem! 🌟' : pct >= 33 ? 'Bom Trabalho!' : 'Continue Tentando!';
  document.getElementById('result-sub').textContent =
    `Você acertou ${correct} de ${TOTAL} questões`;

  const statsEl = document.getElementById('result-stats');
  statsEl.innerHTML = `
    <div class="stat-pill"><span class="sp-icon">⚡</span><span class="sp-val" style="color:var(--wa)">+${xpEarn}</span><span class="sp-lbl">XP</span></div>
    <div class="stat-pill"><span class="sp-icon">💎</span><span class="sp-val" style="color:var(--in)">+${gemEarn}</span><span class="sp-lbl">Gemas</span></div>
    <div class="stat-pill"><span class="sp-icon">⭐</span><span class="sp-val" style="color:var(--wa)">${'★'.repeat(stars)}${'☆'.repeat(3-stars)}</span><span class="sp-lbl">Estrelas</span></div>
    <div class="stat-pill"><span class="sp-icon">✅</span><span class="sp-val" style="color:var(--p)">${pct}%</span><span class="sp-lbl">Acertos</span></div>`;

  if (xpEarn > 0) setTimeout(() => showXPToast(xpEarn), 400);
}

function confirmExit() {
  if (confirm('Sair da lição? Seu progresso será perdido.')) {
    location.href = 'map.php';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
