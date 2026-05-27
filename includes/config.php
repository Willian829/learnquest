<?php
// ============================================
//  LearnQuest — Configuração de Conexão
//  Edite as credenciais conforme seu XAMPP
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // padrão XAMPP é sem senha
define('DB_NAME', 'learnquest');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'http://localhost/learnquest');
define('SITE_NAME', 'LearnQuest');
define('XP_PER_LEVEL', 200);   // XP necessário por nível

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Falha na conexão com o banco: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function currentUser(): array|null {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Calcula nível a partir do XP total
function calcLevel(int $xp): int {
    return max(1, (int) floor($xp / XP_PER_LEVEL) + 1);
}

// XP dentro do nível atual
function xpInLevel(int $xp): int {
    return $xp % XP_PER_LEVEL;
}

// Adiciona XP e atualiza nível
function addXP(int $userId, int $amount): void {
    $pdo = db();
    $pdo->prepare("UPDATE users SET xp = xp + ?, level = ? WHERE id = ?")
        ->execute([$amount, calcLevel((int)($pdo->query("SELECT xp+$amount FROM users WHERE id=$userId")->fetchColumn())), $userId]);
    // Recalcula corretamente
    $user = $pdo->prepare("SELECT xp FROM users WHERE id=?");
    $user->execute([$userId]);
    $xp = (int) $user->fetchColumn();
    $pdo->prepare("UPDATE users SET level=? WHERE id=?")->execute([calcLevel($xp), $userId]);
}

// Verifica e atualiza streak
function updateStreak(int $userId): void {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT last_activity, streak FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($row['last_activity'] === $today) return; // já contou hoje
    $newStreak = ($row['last_activity'] === $yesterday) ? $row['streak'] + 1 : 1;
    $pdo->prepare("UPDATE users SET streak=?, last_activity=? WHERE id=?")
        ->execute([$newStreak, $today, $userId]);
}

// Verifica e desbloqueia conquistas automaticamente
function checkAchievements(int $userId): array {
    $pdo = db();
    $user = $pdo->prepare("SELECT xp, streak FROM users WHERE id=?");
    $user->execute([$userId]);
    $u = $user->fetch();

    $lessonsCount = (int) $pdo->prepare("SELECT COUNT(*) FROM user_lessons WHERE user_id=? AND completed=1")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM user_lessons WHERE user_id=$userId AND completed=1")->fetchColumn() : 0;

    $s1 = $pdo->prepare("SELECT COUNT(*) FROM user_lessons WHERE user_id=? AND completed=1");
    $s1->execute([$userId]);
    $lessonsCount = (int)$s1->fetchColumn();

    $s2 = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM chapters c
        JOIN lessons l ON l.chapter_id=c.id
        JOIN user_lessons ul ON ul.lesson_id=l.id AND ul.user_id=? AND ul.completed=1
        GROUP BY c.id HAVING COUNT(l.id) = COUNT(ul.id)");
    $s2->execute([$userId]);
    $chaptersDone = (int)$s2->rowCount();

    $earned = [];
    $achievements = $pdo->query("SELECT * FROM achievements")->fetchAll();
    foreach ($achievements as $a) {
        // já tem?
        $has = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
        $has->execute([$userId, $a['id']]);
        if ($has->fetch()) continue;

        $unlock = false;
        switch ($a['condition_type']) {
            case 'total_xp':           $unlock = $u['xp'] >= $a['condition_value']; break;
            case 'streak':             $unlock = $u['streak'] >= $a['condition_value']; break;
            case 'lessons_completed':  $unlock = $lessonsCount >= $a['condition_value']; break;
            case 'chapters_done':      $unlock = $chaptersDone >= $a['condition_value']; break;
        }
        if ($unlock) {
            $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)")
                ->execute([$userId, $a['id']]);
            $earned[] = $a;
        }
    }
    return $earned;
}

// Missões do dia/semana do usuário
function getUserMissions(int $userId): array {
    $pdo = db();
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));

    $missions = $pdo->query("SELECT * FROM missions")->fetchAll();
    $result = [];
    foreach ($missions as $m) {
        $period = $m['type'] === 'daily' ? $today : $weekStart;
        // garante registro
        $pdo->prepare("INSERT IGNORE INTO user_missions (user_id, mission_id, period) VALUES (?,?,?)")
            ->execute([$userId, $m['id'], $period]);
        $um = $pdo->prepare("SELECT * FROM user_missions WHERE user_id=? AND mission_id=? AND period=?");
        $um->execute([$userId, $m['id'], $period]);
        $row = $um->fetch();
        $result[] = array_merge($m, ['progress' => $row['progress'], 'done' => (bool)$row['completed']]);
    }
    return $result;
}
