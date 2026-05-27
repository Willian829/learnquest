<?php
// ============================================
//  LearnQuest Admin — Autenticação e helpers
// ============================================
require_once __DIR__ . '/../includes/config.php';

// Credenciais do admin — altere aqui
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');   // troque para uma senha segura
define('ADMIN_URL',  SITE_URL . '/admin');

// Garante sessão separada para admin
if (session_status() === PHP_SESSION_NONE) session_start();

function adminLogin(string $user, string $pass): bool {
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        return true;
    }
    return false;
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_logged'])) {
        header('Location: ' . ADMIN_URL . '/index.php');
        exit;
    }
}

function adminLogout(): void {
    unset($_SESSION['admin_logged']);
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Flash messages
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Paginação simples
function paginate(int $total, int $perPage, int $current): array {
    $pages = max(1, (int)ceil($total / $perPage));
    return ['total' => $total, 'pages' => $pages, 'current' => $current, 'per_page' => $perPage];
}
