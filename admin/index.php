<?php
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_logged'])) {
    header('Location: ' . ADMIN_URL . '/dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (adminLogin(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: ' . ADMIN_URL . '/dashboard.php'); exit;
    }
    $error = 'Usuário ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — LearnQuest</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',system-ui,sans-serif;min-height:100vh;
  background:linear-gradient(135deg,#D1FAE5 0%,#EDE9FE 60%,#FEF3C7 100%);
  display:flex;align-items:center;justify-content:center;padding:24px}
.wrap{width:100%;max-width:380px}
.hero{text-align:center;margin-bottom:28px}
.logo{width:68px;height:68px;border-radius:20px;
  background:linear-gradient(135deg,#10B981,#8B5CF6);
  display:flex;align-items:center;justify-content:center;
  font-size:32px;margin:0 auto 14px;box-shadow:0 8px 24px rgba(16,185,129,.35)}
h1{font-size:26px;font-weight:900;color:#1A2E2A;margin-bottom:4px}
p{font-size:14px;color:#64748B}
.card{background:#fff;border-radius:22px;padding:26px;box-shadow:0 4px 28px rgba(0,0,0,.08)}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label{font-size:11px;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:.05em}
input{padding:12px 15px;border-radius:10px;border:1.5px solid #E2E8F0;font-size:14px;
  font-family:inherit;outline:none;transition:border-color .15s}
input:focus{border-color:#10B981}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#10B981,#059669);
  color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;
  cursor:pointer;font-family:inherit;box-shadow:0 4px 14px rgba(16,185,129,.35);
  transition:all .15s;margin-top:4px}
.btn:hover{transform:translateY(-1px);filter:brightness(1.05)}
.err{background:#FEE2E2;color:#EF4444;border-radius:10px;padding:10px 14px;
  font-size:13px;font-weight:600;margin-bottom:14px;border:1px solid rgba(239,68,68,.2)}
.hint{text-align:center;font-size:12px;color:#94A3B8;margin-top:14px}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <div class="logo">🛡️</div>
    <h1>Painel Admin</h1>
    <p>LearnQuest — Acesso restrito</p>
  </div>
  <div class="card">
    <?php if ($error): ?>
    <div class="err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Usuário</label>
        <input type="text" name="username" placeholder="admin" autocomplete="username" required>
      </div>
      <div class="field">
        <label>Senha</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <button class="btn" type="submit">Entrar no Painel →</button>
    </form>
    <p class="hint">Padrão: admin / admin123</p>
  </div>
</div>
</body>
</html>
