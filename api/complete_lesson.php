<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Não autenticado']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['error' => 'Dados inválidos']); exit; }

$userId   = (int)$_SESSION['user_id'];
$lessonId = (int)($data['lesson_id'] ?? 0);
$correct  = (int)($data['correct']   ?? 0);
$total    = (int)($data['total']     ?? 1);
$stars    = (int)($data['stars']     ?? 0);
$xp       = (int)($data['xp']       ?? 0);
$gems     = (int)($data['gems']      ?? 0);

if (!$lessonId || $total <= 0) {
    echo json_encode(['error' => 'Parâmetros inválidos']); exit;
}

$pdo   = db();
$score = $total > 0 ? round(($correct / $total) * 100) : 0;

// Verifica se a lição existe
$ls = $pdo->prepare("SELECT id FROM lessons WHERE id=?");
$ls->execute([$lessonId]);
if (!$ls->fetch()) { echo json_encode(['error' => 'Lição não encontrada']); exit; }

// Busca progresso anterior
$prev = $pdo->prepare("SELECT * FROM user_lessons WHERE user_id=? AND lesson_id=?");
$prev->execute([$userId, $lessonId]);
$existing = $prev->fetch();

if (!$existing) {
    // Primeiro registro
    $pdo->prepare("INSERT INTO user_lessons (user_id, lesson_id, stars, best_score, completed, completed_at)
        VALUES (?,?,?,?,?,NOW())")
        ->execute([$userId, $lessonId, $stars, $score, $score > 0 ? 1 : 0]);
} else {
    // Atualiza apenas se melhorou
    $newStars = max((int)$existing['stars'], $stars);
    $newScore = max((int)$existing['best_score'], $score);
    $completed = $score > 0 ? 1 : (int)$existing['completed'];
    $completedAt = (!$existing['completed'] && $completed) ? date('Y-m-d H:i:s') : $existing['completed_at'];

    $pdo->prepare("UPDATE user_lessons SET stars=?, best_score=?, completed=?, completed_at=?
        WHERE user_id=? AND lesson_id=?")
        ->execute([$newStars, $newScore, $completed, $completedAt, $userId, $lessonId]);
}

// Adiciona XP e gemas
if ($xp > 0) {
    $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id=?")->execute([$xp, $userId]);
    // Recalcula nível
    $newXp = (int)$pdo->query("SELECT xp FROM users WHERE id=$userId")->fetchColumn();
    $newLevel = calcLevel($newXp);
    $pdo->prepare("UPDATE users SET level=? WHERE id=?")->execute([$newLevel, $userId]);
}
if ($gems > 0) {
    $pdo->prepare("UPDATE users SET gems = gems + ? WHERE id=?")->execute([$gems, $userId]);
}

// Atualiza streak
updateStreak($userId);

// Atualiza progresso de missões
$today     = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
// Missão: complete_lesson
$pdo->prepare("
    UPDATE user_missions um
    JOIN missions m ON m.id = um.mission_id
    SET um.progress = um.progress + 1,
        um.completed = IF(um.progress + 1 >= m.goal_value, 1, um.completed)
    WHERE um.user_id=?
    AND m.goal_type='complete_lesson'
    AND um.completed=0
    AND ((m.type='daily' AND um.period=?) OR (m.type='weekly' AND um.period=?))
")->execute([$userId, $today, $weekStart]);

// Missão: correct_answers
if ($correct > 0) {
    $pdo->prepare("
        UPDATE user_missions um
        JOIN missions m ON m.id = um.mission_id
        SET um.progress = um.progress + ?,
            um.completed = IF(um.progress + ? >= m.goal_value, 1, um.completed)
        WHERE um.user_id=?
        AND m.goal_type='correct_answers'
        AND um.completed=0
        AND ((m.type='daily' AND um.period=?) OR (m.type='weekly' AND um.period=?))
    ")->execute([$correct, $correct, $userId, $today, $weekStart]);
}

// Verifica conquistas
$earned = checkAchievements($userId);

// Conquista especial: lição perfeita
if ($score === 100) {
    $perf = $pdo->prepare("SELECT a.* FROM achievements a WHERE a.condition_type='perfect_lesson' LIMIT 1");
    $perf->execute();
    $pa = $perf->fetch();
    if ($pa) {
        $has = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
        $has->execute([$userId, $pa['id']]);
        if (!$has->fetch()) {
            $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)")
                ->execute([$userId, $pa['id']]);
            $earned[] = $pa;
        }
    }
}

$user = currentUser();
echo json_encode([
    'success'      => true,
    'xp_earned'    => $xp,
    'gems_earned'  => $gems,
    'stars'        => $stars,
    'score'        => $score,
    'achievements' => $earned,
    'user'         => [
        'xp'     => (int)$user['xp'],
        'level'  => (int)$user['level'],
        'gems'   => (int)$user['gems'],
        'streak' => (int)$user['streak'],
        'hearts' => (int)$user['hearts'],
    ]
]);
