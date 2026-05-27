<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Não autenticado']); exit; }

$user = currentUser();
echo json_encode([
    'id'     => (int)$user['id'],
    'name'   => $user['name'],
    'avatar' => $user['avatar'],
    'xp'     => (int)$user['xp'],
    'level'  => (int)$user['level'],
    'gems'   => (int)$user['gems'],
    'hearts' => (int)$user['hearts'],
    'streak' => (int)$user['streak'],
]);
