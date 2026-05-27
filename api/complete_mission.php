<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Não autenticado']); exit; }

$data      = json_decode(file_get_contents('php://input'), true);
$userId    = (int)$_SESSION['user_id'];
$missionId = (int)($data['mission_id'] ?? 0);

if (!$missionId) { echo json_encode(['error' => 'ID inválido']); exit; }

$pdo       = db();
$today     = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));

// Busca missão
$ms = $pdo->prepare("SELECT * FROM missions WHERE id=?");
$ms->execute([$missionId]);
$mission = $ms->fetch();
if (!$mission) { echo json_encode(['error' => 'Missão não encontrada']); exit; }

$period = $mission['type'] === 'daily' ? $today : $weekStart;

// Busca status no período
$um = $pdo->prepare("SELECT * FROM user_missions WHERE user_id=? AND mission_id=? AND period=?");
$um->execute([$userId, $missionId, $period]);
$rec = $um->fetch();

if (!$rec) {
    $pdo->prepare("INSERT INTO user_missions (user_id, mission_id, period) VALUES (?,?,?)")
        ->execute([$userId, $missionId, $period]);
    $um->execute([$userId, $missionId, $period]);
    $rec = $um->fetch();
}

if ($rec['completed']) {
    echo json_encode(['error' => 'Missão já concluída']); exit;
}

// Marca como concluída manualmente (para missões com botão direto)
$pdo->prepare("UPDATE user_missions SET progress=goal_value, completed=1
    FROM missions m WHERE m.id=mission_id AND user_missions.user_id=? AND user_missions.mission_id=? AND period=?")
    ->execute([$userId, $missionId, $period]);

// Fallback UPDATE simples
$pdo->prepare("UPDATE user_missions SET completed=1, progress=?
    WHERE user_id=? AND mission_id=? AND period=?")
    ->execute([$mission['goal_value'], $userId, $missionId, $period]);

// Dá XP da missão
$xp = (int)$mission['xp_reward'];
if ($xp > 0) {
    $pdo->prepare("UPDATE users SET xp=xp+? WHERE id=?")->execute([$xp, $userId]);
    $newXp = (int)$pdo->query("SELECT xp FROM users WHERE id=$userId")->fetchColumn();
    $pdo->prepare("UPDATE users SET level=? WHERE id=?")->execute([calcLevel($newXp), $userId]);
}

$earned = checkAchievements($userId);
$user   = currentUser();

echo json_encode([
    'success'      => true,
    'xp_reward'    => $xp,
    'achievements' => $earned,
    'user'         => [
        'xp'     => (int)$user['xp'],
        'level'  => (int)$user['level'],
        'gems'   => (int)$user['gems'],
        'streak' => (int)$user['streak'],
        'hearts' => (int)$user['hearts'],
    ]
]);
