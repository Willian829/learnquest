<?php
// admin/layout.php — incluído em todas as páginas admin
if (!defined('ADMIN_PAGE')) define('ADMIN_PAGE', 'Dashboard');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(ADMIN_PAGE) ?> — Admin LearnQuest</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ================================================
   LearnQuest Admin — Design System
================================================ */
:root {
  --p:#10B981;--pd:#059669;--pl:#D1FAE5;
  --ac:#8B5CF6;--al:#EDE9FE;
  --wa:#F59E0B;--wl:#FEF3C7;
  --da:#EF4444;--dl:#FEE2E2;
  --in:#3B82F6;--il:#DBEAFE;
  --bg:#F0F4F2;--su:#FFFFFF;
  --bo:#E2E8F0;--bl:#F1F5F9;
  --tx:#1A2E2A;--ts:#64748B;--tm:#94A3B8;
  --sidebar:240px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',system-ui,sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:flex}
a{text-decoration:none;color:inherit}
input,button,select,textarea{font-family:inherit}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-thumb{background:var(--bo);border-radius:99px}

/* SIDEBAR */
.sidebar{
  width:var(--sidebar);min-height:100vh;background:var(--su);
  border-right:1px solid var(--bo);display:flex;flex-direction:column;
  position:fixed;top:0;left:0;bottom:0;z-index:100;
  box-shadow:2px 0 12px rgba(0,0,0,.04);
}
.sidebar-logo{
  display:flex;align-items:center;gap:10px;padding:20px 18px;
  border-bottom:1px solid var(--bo);
}
.logo-icon{
  width:36px;height:36px;border-radius:10px;
  background:linear-gradient(135deg,var(--p),var(--ac));
  display:flex;align-items:center;justify-content:center;font-size:18px;
  box-shadow:0 2px 8px rgba(16,185,129,.3);
}
.logo-text{font-weight:900;font-size:16px;color:var(--tx)}
.logo-sub{font-size:10px;color:var(--tm);font-weight:600;margin-top:1px}

.sidebar-nav{flex:1;padding:12px 10px;display:flex;flex-direction:column;gap:2px}
.nav-section{font-size:10px;font-weight:800;color:var(--tm);
  padding:10px 8px 4px;letter-spacing:.06em;text-transform:uppercase}
.nav-link{
  display:flex;align-items:center;gap:10px;padding:10px 12px;
  border-radius:10px;font-weight:700;font-size:13px;color:var(--ts);
  transition:all .15s;position:relative;
}
.nav-link:hover{background:var(--bl);color:var(--tx)}
.nav-link.active{background:var(--pl);color:var(--pd)}
.nav-link.active::before{
  content:'';position:absolute;left:0;top:20%;bottom:20%;
  width:3px;border-radius:99px;background:var(--p);
}
.nav-icon{font-size:16px;width:20px;text-align:center}
.nav-badge{
  margin-left:auto;background:var(--p);color:#fff;
  border-radius:99px;padding:2px 7px;font-size:10px;font-weight:800;
}

.sidebar-footer{
  padding:14px;border-top:1px solid var(--bo);
}
.sidebar-user{
  display:flex;align-items:center;gap:10px;
  background:var(--bl);border-radius:10px;padding:10px 12px;
}
.sidebar-user-info{flex:1}
.sidebar-user-name{font-weight:800;font-size:13px;color:var(--tx)}
.sidebar-user-role{font-size:11px;color:var(--tm)}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* TOPBAR */
.admin-topbar{
  background:var(--su);border-bottom:1px solid var(--bo);
  padding:0 24px;height:60px;display:flex;align-items:center;
  justify-content:space-between;position:sticky;top:0;z-index:50;
}
.topbar-title{font-size:18px;font-weight:900;color:var(--tx)}
.topbar-breadcrumb{font-size:12px;color:var(--tm);margin-top:2px}
.topbar-actions{display:flex;gap:10px;align-items:center}

/* CONTENT */
.content{padding:24px;flex:1}

/* CARDS */
.card{background:var(--su);border-radius:16px;border:1px solid var(--bo);padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.card+.card{margin-top:14px}

/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:22px}
.stat-card{
  background:var(--su);border-radius:16px;border:1px solid var(--bo);
  padding:18px;display:flex;flex-direction:column;gap:8px;
  box-shadow:0 2px 8px rgba(0,0,0,.04);transition:transform .15s;
}
.stat-card:hover{transform:translateY(-2px)}
.stat-top{display:flex;justify-content:space-between;align-items:flex-start}
.stat-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-val{font-size:28px;font-weight:900;color:var(--tx)}
.stat-lbl{font-size:12px;color:var(--ts);font-weight:600}
.stat-sub{font-size:11px;color:var(--tm)}

/* TABLES */
.table-wrap{overflow-x:auto;border-radius:14px;border:1px solid var(--bo)}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{
  background:var(--bl);padding:12px 16px;text-align:left;
  font-weight:800;color:var(--ts);font-size:11px;text-transform:uppercase;
  letter-spacing:.04em;border-bottom:1px solid var(--bo);
}
tbody td{padding:12px 16px;border-bottom:1px solid var(--bl);color:var(--tx);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--bl)}

/* FORMS */
.form-grid{display:grid;gap:16px}
.form-grid-2{grid-template-columns:1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:12px;font-weight:800;color:var(--ts);text-transform:uppercase;letter-spacing:.04em}
.form-input,.form-select,.form-textarea{
  padding:11px 14px;border-radius:10px;border:1.5px solid var(--bo);
  font-size:13px;color:var(--tx);background:var(--su);outline:none;
  transition:border-color .15s;width:100%;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--p)}
.form-textarea{min-height:90px;resize:vertical}
.form-hint{font-size:11px;color:var(--tm)}

/* BUTTONS */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:10px 18px;border-radius:10px;border:none;font-size:13px;
  font-weight:800;cursor:pointer;transition:all .15s;text-decoration:none;
}
.btn:hover{transform:translateY(-1px);filter:brightness(1.05)}
.btn:active{transform:scale(.97)}
.btn-sm{padding:7px 12px;font-size:12px;border-radius:8px}
.btn-primary{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff;box-shadow:0 3px 10px rgba(16,185,129,.3)}
.btn-accent{background:linear-gradient(135deg,var(--ac),#7C3AED);color:#fff;box-shadow:0 3px 10px rgba(139,92,246,.25)}
.btn-secondary{background:var(--bl);color:var(--ts);border:1px solid var(--bo)}
.btn-danger{background:var(--dl);color:var(--da);border:1px solid rgba(239,68,68,.2)}
.btn-warning{background:var(--wl);color:var(--wa);border:1px solid rgba(245,158,11,.2)}
.btn-full{width:100%}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:800}
.badge-green{background:var(--pl);color:var(--pd)}
.badge-purple{background:var(--al);color:var(--ac)}
.badge-yellow{background:var(--wl);color:var(--wa)}
.badge-red{background:var(--dl);color:var(--da)}
.badge-blue{background:var(--il);color:var(--in)}

/* ALERTS */
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-success{background:var(--pl);color:var(--pd);border:1px solid rgba(16,185,129,.2)}
.alert-error{background:var(--dl);color:var(--da);border:1px solid rgba(239,68,68,.2)}
.alert-info{background:var(--il);color:var(--in);border:1px solid rgba(59,130,246,.2)}

/* PAGE HEADER */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.page-header-left h2{font-size:20px;font-weight:900;color:var(--tx)}
.page-header-left p{font-size:13px;color:var(--tm);margin-top:2px}

/* SEARCH BAR */
.search-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.search-input-wrap{position:relative;flex:1;min-width:200px}
.search-input-wrap input{padding-left:36px}
.search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:14px;color:var(--tm)}

/* EMPTY STATE */
.empty-state{text-align:center;padding:48px 24px;color:var(--tm)}
.empty-state-icon{font-size:48px;margin-bottom:12px}
.empty-state-title{font-weight:800;font-size:16px;color:var(--ts);margin-bottom:4px}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:500;display:flex;align-items:center;justify-content:center;padding:16px}
.modal{background:var(--su);border-radius:20px;padding:24px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.15)}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.modal-title{font-size:17px;font-weight:900;color:var(--tx)}
.modal-close{background:none;border:none;cursor:pointer;font-size:18px;color:var(--tm);padding:4px}
.hidden{display:none!important}

/* COLOR SWATCH */
.color-options{display:flex;gap:8px;flex-wrap:wrap}
.color-swatch{width:32px;height:32px;border-radius:8px;cursor:pointer;border:3px solid transparent;transition:all .15s}
.color-swatch.selected{border-color:var(--tx);transform:scale(1.1)}

/* PAGINATION */
.pagination{display:flex;gap:6px;align-items:center;margin-top:16px;justify-content:center}
.page-btn{padding:7px 12px;border-radius:8px;border:1px solid var(--bo);background:var(--su);font-size:12px;font-weight:700;cursor:pointer;color:var(--ts);transition:all .15s}
.page-btn:hover{background:var(--bl)}
.page-btn.active{background:var(--p);color:#fff;border-color:var(--p)}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);transition:transform .3s}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .form-grid-2{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🚀</div>
    <div>
      <div class="logo-text">LearnQuest</div>
      <div class="logo-sub">Painel Admin</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Geral</div>
    <a href="<?= ADMIN_URL ?>/dashboard.php"
       class="nav-link <?= ADMIN_PAGE==='Dashboard'?'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <div class="nav-section">Conteúdo</div>
    <a href="<?= ADMIN_URL ?>/chapters.php"
       class="nav-link <?= ADMIN_PAGE==='Capítulos'?'active':'' ?>">
      <span class="nav-icon">📂</span> Capítulos
    </a>
    <a href="<?= ADMIN_URL ?>/lessons.php"
       class="nav-link <?= ADMIN_PAGE==='Lições'?'active':'' ?>">
      <span class="nav-icon">📚</span> Lições
    </a>
    <a href="<?= ADMIN_URL ?>/questions.php"
       class="nav-link <?= ADMIN_PAGE==='Questões'?'active':'' ?>">
      <span class="nav-icon">❓</span> Questões
    </a>

    <div class="nav-section">Usuários</div>
    <a href="<?= ADMIN_URL ?>/users.php"
       class="nav-link <?= ADMIN_PAGE==='Usuários'?'active':'' ?>">
      <span class="nav-icon">👥</span> Usuários
    </a>

    <div class="nav-section">Sistema</div>
    <a href="<?= SITE_URL ?>/pages/dashboard.php" target="_blank" class="nav-link">
      <span class="nav-icon">🌐</span> Ver Site
    </a>
    <a href="<?= ADMIN_URL ?>/logout.php" class="nav-link" style="color:var(--da)">
      <span class="nav-icon">🚪</span> Sair
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <span style="font-size:22px">🛡️</span>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">Administrador</div>
        <div class="sidebar-user-role">Super Admin</div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <!-- Topbar -->
  <div class="admin-topbar">
    <div>
      <div class="topbar-title"><?= htmlspecialchars(ADMIN_PAGE) ?></div>
      <div class="topbar-breadcrumb">Admin &rsaquo; <?= htmlspecialchars(ADMIN_PAGE) ?></div>
    </div>
    <div class="topbar-actions">
      <a href="<?= SITE_URL ?>/pages/dashboard.php" target="_blank"
         class="btn btn-secondary btn-sm">🌐 Ver Site</a>
      <a href="<?= ADMIN_URL ?>/logout.php" class="btn btn-sm" style="background:var(--dl);color:var(--da)">Sair</a>
    </div>
  </div>

  <!-- Content -->
  <div class="content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>">
      <?= $flash['type']==='success'?'✅':'⚠️' ?>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>
