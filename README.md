# LearnQuest 🚀
Plataforma educacional gamificada — PHP + MySQL + XAMPP

---

## 📁 Estrutura de Arquivos

```
learnquest/
├── index.php                  ← Login / Cadastro
├── database.sql               ← Banco de dados completo
├── includes/
│   ├── config.php             ← Conexão, funções globais
│   ├── header.php             ← Topbar + nav bottom
│   └── footer.php             ← Fecha HTML
├── pages/
│   ├── dashboard.php          ← Tela inicial (home)
│   ├── map.php                ← Mapa de aprendizado
│   ├── lesson.php             ← Lição / Quiz interativo
│   ├── ranking.php            ← Ranking geral e semanal
│   └── profile.php            ← Perfil, avatares, medalhas
├── api/
│   ├── complete_lesson.php    ← Salva resultado de lição
│   ├── complete_mission.php   ← Conclui missão manual
│   ├── user_stats.php         ← Retorna stats em JSON
│   └── logout.php             ← Destroi sessão
└── assets/
    ├── css/style.css          ← Design system completo
    └── js/main.js             ← Toasts, animações, fetch
```

---

## ⚙️ Instalação no XAMPP

### 1. Copiar o projeto
Coloque a pasta `learnquest` dentro de:
```
C:\xampp\htdocs\learnquest\      (Windows)
/opt/lampp/htdocs/learnquest/    (Linux)
```

### 2. Iniciar o XAMPP
- Abra o **XAMPP Control Panel**
- Inicie os módulos **Apache** e **MySQL**

### 3. Criar o banco de dados
- Acesse: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Clique em **SQL** (aba no topo)
- Cole o conteúdo de `database.sql` e clique em **Executar**

  > Isso cria o banco `learnquest`, todas as tabelas e os dados iniciais.

### 4. Verificar credenciais
Abra `includes/config.php` e confirme:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // padrão XAMPP = sem senha
define('DB_NAME', 'learnquest');
define('SITE_URL', 'http://localhost/learnquest');
```

Se você definiu senha no MySQL, atualize `DB_PASS`.

### 5. Acessar
Abra no navegador:
```
http://localhost/learnquest/
```

---

## 🎮 Funcionalidades

| Recurso                  | Descrição |
|--------------------------|-----------|
| Login / Cadastro         | Sessão PHP com senha hash (bcrypt) |
| Dashboard                | XP, streak, missões diárias/semanais |
| Mapa de Aprendizado      | 3 capítulos, 9 lições, nós visuais animados |
| Quiz Interativo          | Questões de múltipla escolha, vidas, feedback |
| Sistema de XP e Nível    | XP acumulado, nível calculado automaticamente |
| Gemas e Vidas            | Recompensas por lição, vidas perdidas por erro |
| Missões                  | Diárias e semanais com progresso automático |
| Ranking                  | Geral e semanal, posição destacada |
| Perfil                   | Avatar selecionável, conquistas, estatísticas |
| Conquistas / Medalhas    | 8 conquistas com desbloqueio automático |
| Toasts animados          | Feedback de XP e conquistas em tempo real |
| Design responsivo        | Mobile-first, funciona em qualquer tela |

---

## 🗄️ Tabelas do Banco

| Tabela              | Conteúdo |
|---------------------|----------|
| `users`             | Usuários, XP, nível, streak, gemas, vidas |
| `chapters`          | Capítulos (mundos) com cor e ícone |
| `lessons`           | Lições por capítulo |
| `questions`         | Questões de múltipla escolha |
| `user_lessons`      | Progresso do usuário por lição |
| `missions`          | Missões diárias e semanais |
| `user_missions`     | Progresso das missões por período |
| `achievements`      | Conquistas disponíveis |
| `user_achievements` | Conquistas desbloqueadas por usuário |

---

## ➕ Adicionar Conteúdo

### Nova lição
```sql
INSERT INTO lessons (chapter_id, title, icon, position, xp_reward, gem_reward)
VALUES (1, 'Minha Lição', '🎯', 4, 40, 8);
```

### Nova questão
```sql
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position)
VALUES (1, 'Pergunta aqui?', 'A', 'B', 'C', 'D', 0, 4);
-- correct: 0=A, 1=B, 2=C, 3=D
```

### Novo capítulo
```sql
INSERT INTO chapters (title, icon, color, position, xp_required, description)
VALUES ('Especialista', '🧠', '#EC4899', 4, 500, 'Nível especialista');
```

---

## 🔒 Segurança
- Senhas armazenadas com `password_hash()` (bcrypt)
- Queries parametrizadas (PDO prepared statements)
- Verificação de sessão em todas as páginas protegidas
- Dados sanitizados com `htmlspecialchars()` no output

---

## 📄 Requisitos
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- XAMPP 8.x recomendado
