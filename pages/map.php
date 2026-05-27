<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = currentUser();
$pdo  = db();

// Capítulos com lições e progresso do usuário
$chapters = $pdo->query("SELECT * FROM chapters ORDER BY position")->fetchAll();

foreach ($chapters as &$ch) {
    $lessons = $pdo->prepare("SELECT l.*, ul.completed, ul.stars FROM lessons l
        LEFT JOIN user_lessons ul ON ul.lesson_id = l.id AND ul.user_id = ?
        WHERE l.chapter_id = ? ORDER BY l.position");
    $lessons->execute([$user['id'], $ch['id']]);
    $ch['lessons'] = $lessons->fetchAll();

    $ch['unlocked']   = (int)$user['xp'] >= (int)$ch['xp_required'];
    $ch['completed']  = !empty($ch['lessons']) && !in_array(false, array_map(fn($l) => (bool)$l['completed'], $ch['lessons']));
    $ch['total']      = count($ch['lessons']);
    $ch['done_count'] = count(array_filter($ch['lessons'], fn($l) => $l['completed']));
}
unset($ch);

// Próxima lição ativa
$activeLesson = null;
foreach ($chapters as $ch) {
    if (!$ch['unlocked']) continue;
    foreach ($ch['lessons'] as $l) {
        if (!$l['completed']) { $activeLesson = $l['id']; break 2; }
    }
}

define('PAGE_TITLE', 'Mapa');
$activeTab = 'map';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="page">
  <h1 class="page-title">🗺️ Mapa de Aprendizado</h1>

  <?php foreach ($chapters as $ch): ?>
  <div class="chapter-card <?= !$ch['unlocked'] ? 'locked' : '' ?>">

    <!-- Cabeçalho do capítulo -->
    <div class="chapter-head">
      <div class="chapter-icon" style="background:<?= htmlspecialchars($ch['color']) ?>22">
        <?= htmlspecialchars($ch['icon']) ?>
      </div>
      <div style="flex:1">
        <div class="chapter-title"><?= htmlspecialchars($ch['title']) ?></div>
        <div class="chapter-status">
          <?php if (!$ch['unlocked']): ?>
            🔒 Requer <?= (int)$ch['xp_required'] ?> XP
          <?php elseif ($ch['completed']): ?>
            ✅ Completo
          <?php else: ?>
            <?= $ch['done_count'] ?>/<?= $ch['total'] ?> lições
          <?php endif; ?>
        </div>
      </div>
      <?php if ($ch['unlocked']): ?>
      <!-- Mini barra de progresso do capítulo -->
      <div style="min-width:80px">
        <div style="font-size:10px;color:var(--tx-m);text-align:right;margin-bottom:4px">
          <?= round(($ch['done_count']/$ch['total'])*100) ?>%
        </div>
        <div style="height:5px;border-radius:99px;background:var(--bo-light);overflow:hidden">
          <div style="height:100%;width:<?= round(($ch['done_count']/$ch['total'])*100) ?>%;
            background:<?= htmlspecialchars($ch['color']) ?>;border-radius:99px;transition:width .8s ease">
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Nós das lições -->
    <div class="lessons-row">
      <?php foreach ($ch['lessons'] as $idx => $lesson):
        $isCompleted = (bool)$lesson['completed'];
        $isActive    = $ch['unlocked'] && !$isCompleted && ($activeLesson == $lesson['id']);
        $isLocked    = !$ch['unlocked'] || (!$isCompleted && !$isActive && $activeLesson != $lesson['id'] && $idx > 0 && !$ch['lessons'][$idx-1]['completed']);
        $stars        = (int)($lesson['stars'] ?? 0);
        $href = ($ch['unlocked'] && ($isCompleted || $isActive)) ? "lesson.php?id={$lesson['id']}" : '#';
      ?>

      <?php if ($idx > 0): ?>
      <div class="lesson-connector"
        style="background:<?= $isCompleted ? htmlspecialchars($ch['color']).'60' : 'var(--bo-light)' ?>">
      </div>
      <?php endif; ?>

      <div class="lesson-node">
        <a href="<?= $href ?>" style="text-decoration:none">
          <div class="lesson-bubble <?= $isCompleted?'completed':($isActive?'active':'locked') ?>"
            style="
              background: <?= $isCompleted ? htmlspecialchars($ch['color']) : ($isActive ? '#fff' : 'var(--bo-light)') ?>;
              border-color: <?= ($isCompleted||$isActive) ? htmlspecialchars($ch['color']) : 'var(--bo)' ?>;
              box-shadow: <?= $isActive ? "0 0 0 6px {$ch['color']}22, 0 4px 14px {$ch['color']}30" : ($isCompleted ? "0 2px 8px {$ch['color']}40" : 'none') ?>;
            ">
            <span style="filter:<?= ($isCompleted||$isActive)?'none':'grayscale(1) opacity(.35)' ?>">
              <?= htmlspecialchars($lesson['icon']) ?>
            </span>
            <?php if ($isActive): ?>
            <div class="lesson-pulse" style="background:<?= htmlspecialchars($ch['color']) ?>"></div>
            <?php endif; ?>
          </div>
        </a>

        <?php if ($isCompleted): ?>
        <div class="lesson-stars">
          <?php for ($s=0;$s<3;$s++): ?>
          <span style="color:<?= $s < $stars ? 'var(--wa)' : 'var(--bo)' ?>">★</span>
          <?php endfor; ?>
        </div>
        <?php endif; ?>

        <div class="lesson-label"
          style="color:<?= $isCompleted?'var(--tx-s)':($isActive?'var(--tx)':'var(--tx-m)') ?>">
          <?= htmlspecialchars($lesson['title']) ?>
        </div>
      </div>

      <?php endforeach; ?>
    </div>

    <!-- Botão de ação do capítulo -->
    <?php if ($ch['unlocked'] && !$ch['completed']): ?>
    <?php
      // Próxima lição não completa deste capítulo
      $nextInChapter = null;
      foreach ($ch['lessons'] as $l) {
          if (!$l['completed']) { $nextInChapter = $l; break; }
      }
    ?>
    <?php if ($nextInChapter): ?>
    <a href="lesson.php?id=<?= (int)$nextInChapter['id'] ?>"
      class="btn btn-primary btn-full"
      style="margin-top:16px">
      ▶ Continuar — <?= htmlspecialchars($nextInChapter['title']) ?>
    </a>
    <?php endif; ?>
    <?php elseif ($ch['completed']): ?>
    <div style="margin-top:14px;text-align:center">
      <a href="lesson.php?id=<?= (int)$ch['lessons'][0]['id'] ?>"
        class="btn btn-secondary" style="font-size:12px;padding:8px 16px">
        🔁 Praticar novamente
      </a>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
