<?php
require_once __DIR__ . '/includes/config.php';

// Se já logado, redireciona
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$error   = '';
$success = '';
$mode    = $_POST['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';

    if ($mode === 'register') {
        // Cadastro
        if (!$name || !$email || !$pass) {
            $error = 'Preencha todos os campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido.';
        } elseif (strlen($pass) < 6) {
            $error = 'A senha deve ter no mínimo 6 caracteres.';
        } else {
            $check = db()->prepare("SELECT id FROM users WHERE email=?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'E-mail já cadastrado.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                db()->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)")
                     ->execute([$name, $email, $hash]);
                $success = 'Conta criada! Faça login para começar.';
                $mode = 'login';
            }
        }
    } else {
        // Login
        if (!$email || !$pass) {
            $error = 'Preencha e-mail e senha.';
        } else {
            $stmt = db()->prepare("SELECT * FROM users WHERE email=?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($pass, $user['password'])) {
                $error = 'E-mail ou senha incorretos.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                updateStreak($user['id']);
                header('Location: ' . SITE_URL . '/pages/dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest — Entrar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-wrap">
  <div class="auth-hero">
    <div class="auth-logo">🚀</div>
    <h1 class="auth-title">LearnQuest</h1>
    <p class="auth-subtitle">Aprenda. Evolua. Conquiste.</p>
  </div>

  <div class="auth-card">
    <!-- Toggle -->
    <div class="auth-toggle">
      <button type="button" class="auth-toggle-btn <?= $mode==='login'    ?'active':'' ?>"
        onclick="switchMode('login')">Entrar</button>
      <button type="button" class="auth-toggle-btn <?= $mode==='register' ?'active':'' ?>"
        onclick="switchMode('register')">Criar conta</button>
    </div>

    <?php if ($error):   ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" id="auth-form">
      <input type="hidden" name="mode" id="mode-input" value="<?= htmlspecialchars($mode) ?>">
      <div class="form-group">
        <div id="name-field" style="<?= $mode==='register'?'':'display:none' ?>">
          <input type="text" name="name" class="form-input" placeholder="Seu nome"
            value="<?= htmlspecialchars($name ?? '') ?>">
        </div>
        <input type="email" name="email" class="form-input" placeholder="E-mail"
          value="<?= htmlspecialchars($email ?? '') ?>" required>
        <input type="password" name="password" class="form-input" placeholder="Senha" required>
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submit-btn">
          <?= $mode==='login' ? 'Entrar na Jornada →' : 'Começar Agora →' ?>
        </button>
      </div>
    </form>

    <?php if ($mode === 'login'): ?>
    <div style="text-align:center;margin-top:12px">
      <a href="#" style="font-size:12px;color:var(--tx-m)">Esqueci minha senha</a>
    </div>
    <?php endif; ?>
  </div>

  <div class="auth-features">
    <div class="auth-feat">🎮 Gamificado</div>
    <div class="auth-feat">📈 Progresso Real</div>
    <div class="auth-feat">🏆 Conquistas</div>
  </div>
</div>

<script>
function switchMode(m) {
  document.getElementById('mode-input').value = m;
  document.getElementById('name-field').style.display = m === 'register' ? '' : 'none';
  document.getElementById('submit-btn').textContent = m === 'login' ? 'Entrar na Jornada →' : 'Começar Agora →';
  document.querySelectorAll('.auth-toggle-btn').forEach((b,i) => {
    b.classList.toggle('active', (i===0 && m==='login') || (i===1 && m==='register'));
  });
}
</script>
</body>
</html>
