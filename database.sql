-- ============================================
--  LearnQuest — Banco de Dados MySQL / XAMPP
-- ============================================
CREATE DATABASE IF NOT EXISTS learnquest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE learnquest;

-- Usuários
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    avatar       VARCHAR(10)  NOT NULL DEFAULT '🦁',
    xp           INT          NOT NULL DEFAULT 0,
    level        INT          NOT NULL DEFAULT 1,
    gems         INT          NOT NULL DEFAULT 0,
    hearts       INT          NOT NULL DEFAULT 5,
    streak       INT          NOT NULL DEFAULT 0,
    last_activity DATE         NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Capítulos (mundos)
CREATE TABLE IF NOT EXISTS chapters (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '📚',
    color       VARCHAR(10)  NOT NULL DEFAULT '#10B981',
    position    INT          NOT NULL DEFAULT 0,
    xp_required INT          NOT NULL DEFAULT 0,
    description TEXT         NULL
) ENGINE=InnoDB;

-- Lições
CREATE TABLE IF NOT EXISTS lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id  INT          NOT NULL,
    title       VARCHAR(100) NOT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '📖',
    position    INT          NOT NULL DEFAULT 0,
    xp_reward   INT          NOT NULL DEFAULT 30,
    gem_reward  INT          NOT NULL DEFAULT 5,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Questões
CREATE TABLE IF NOT EXISTS questions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id   INT          NOT NULL,
    question    TEXT         NOT NULL,
    option_a    VARCHAR(255) NOT NULL,
    option_b    VARCHAR(255) NOT NULL,
    option_c    VARCHAR(255) NOT NULL,
    option_d    VARCHAR(255) NOT NULL,
    correct     TINYINT      NOT NULL COMMENT '0=A,1=B,2=C,3=D',
    position    INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Progresso do usuário por lição
CREATE TABLE IF NOT EXISTS user_lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    lesson_id   INT          NOT NULL,
    stars       TINYINT      NOT NULL DEFAULT 0,
    best_score  INT          NOT NULL DEFAULT 0,
    completed   TINYINT(1)   NOT NULL DEFAULT 0,
    completed_at DATETIME    NULL,
    UNIQUE KEY uq_user_lesson (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Missões
CREATE TABLE IF NOT EXISTS missions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '🎯',
    xp_reward   INT          NOT NULL DEFAULT 20,
    type        ENUM('daily','weekly') NOT NULL DEFAULT 'daily',
    goal_value  INT          NOT NULL DEFAULT 1,
    goal_type   VARCHAR(50)  NOT NULL DEFAULT 'complete_lesson'
) ENGINE=InnoDB;

-- Missões completadas pelo usuário
CREATE TABLE IF NOT EXISTS user_missions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    mission_id  INT          NOT NULL,
    progress    INT          NOT NULL DEFAULT 0,
    completed   TINYINT(1)   NOT NULL DEFAULT 0,
    period      DATE         NOT NULL,
    UNIQUE KEY uq_user_mission_period (user_id, mission_id, period),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Conquistas/Medalhas
CREATE TABLE IF NOT EXISTS achievements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '🏅',
    condition_type  VARCHAR(50) NOT NULL,
    condition_value INT         NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Conquistas desbloqueadas por usuário
CREATE TABLE IF NOT EXISTS user_achievements (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT      NOT NULL,
    achievement_id INT      NOT NULL,
    earned_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
--  DADOS INICIAIS
-- ============================================

-- Capítulos
INSERT INTO chapters (title, icon, color, position, xp_required, description) VALUES
('Fundamentos',  '🌱', '#10B981', 1,   0, 'Dê os primeiros passos no aprendizado'),
('Explorando',   '🔭', '#8B5CF6', 2, 100, 'Aprofunde seu conhecimento'),
('Avançado',     '🚀', '#F59E0B', 3, 300, 'Domine os conceitos avançados');

-- Lições do Cap 1
INSERT INTO lessons (chapter_id, title, icon, position, xp_reward, gem_reward) VALUES
(1, 'Introdução',      '👋', 1, 30, 5),
(1, 'Conceitos Básicos','💡', 2, 30, 5),
(1, 'Primeiros Passos','👟', 3, 40, 8);

-- Lições do Cap 2
INSERT INTO lessons (chapter_id, title, icon, position, xp_reward, gem_reward) VALUES
(2, 'Novas Fronteiras', '🌏', 1, 40, 8),
(2, 'Aprofundando',     '🔍', 2, 50, 10),
(2, 'Consolidando',     '🧱', 3, 50, 10);

-- Lições do Cap 3
INSERT INTO lessons (chapter_id, title, icon, position, xp_reward, gem_reward) VALUES
(3, 'Desafio I',  '⚔️', 1, 60, 15),
(3, 'Desafio II', '🛡️', 2, 60, 15),
(3, 'Maestria',   '👑', 3, 80, 20);

-- Questões — Lição 1
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position) VALUES
(1,'Qual é a capital do Brasil?','São Paulo','Brasília','Rio de Janeiro','Salvador', 1, 1),
(1,'Quanto é 5 + 3?','6','7','8','9', 2, 2),
(1,'Qual é o maior planeta do Sistema Solar?','Terra','Marte','Júpiter','Saturno', 2, 3);

-- Questões — Lição 2
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position) VALUES
(2,'Qual elemento tem símbolo "O"?','Ouro','Osmio','Oxigênio','Óxido', 2, 1),
(2,'Quanto é 7 × 8?','54','56','48','64', 1, 2),
(2,'Quem escreveu Dom Casmurro?','José de Alencar','Machado de Assis','Clarice Lispector','Drummond', 1, 3);

-- Questões — Lição 3
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position) VALUES
(3,'Qual é a fórmula da água?','CO2','H2O','O2','NaCl', 1, 1),
(3,'Em que ano o Brasil foi descoberto?','1400','1492','1500','1522', 2, 2),
(3,'Qual é a raiz quadrada de 144?','10','11','12','13', 2, 3);

-- Questões — Lição 4
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position) VALUES
(4,'Quantos continentes tem o mundo?','5','6','7','8', 2, 1),
(4,'Qual é o maior oceano?','Atlântico','Índico','Ártico','Pacífico', 3, 2),
(4,'O que significa "HTML"?','HyperText Markup Language','High Transfer Markup Language','HyperText Modern Language','High Text Markup Language', 0, 3);

-- Questões — Lição 5
INSERT INTO questions (lesson_id, question, option_a, option_b, option_c, option_d, correct, position) VALUES
(5,'Qual é o símbolo do ouro?','Or','Go','Au','Ag', 2, 1),
(5,'Quantos bits tem 1 byte?','4','6','8','16', 2, 2),
(5,'Quem pintou a Mona Lisa?','Van Gogh','Picasso','Da Vinci','Rembrandt', 2, 3);

-- Missões
INSERT INTO missions (title, description, icon, xp_reward, type, goal_value, goal_type) VALUES
('Lição Diária',      'Complete 1 lição hoje',           '📚', 20, 'daily',  1, 'complete_lesson'),
('Treino Rápido',     'Acerte 5 questões hoje',          '⚡', 15, 'daily',  5, 'correct_answers'),
('Desafio da Semana', 'Complete 5 lições essa semana',   '🏆', 50, 'weekly', 5, 'complete_lesson'),
('Sequência Quente',  'Mantenha 3 dias seguidos',        '🔥', 30, 'weekly', 3, 'streak'),
('Coletor de Gemas',  'Ganhe 20 gemas',                  '💎', 25, 'weekly',20, 'earn_gems');

-- Conquistas
INSERT INTO achievements (title, description, icon, condition_type, condition_value) VALUES
('Primeiro Passo', 'Complete sua 1ª lição',    '🌱', 'lessons_completed', 1),
('Em Chamas',      '3 dias seguidos',           '🔥', 'streak',            3),
('Energizado',     'Acumule 100 XP',            '⚡', 'total_xp',         100),
('Perfeito',       'Lição sem erros',            '💎', 'perfect_lesson',    1),
('Dedicado',       '7 dias seguidos',            '🌟', 'streak',            7),
('Explorador',     'Complete 2 capítulos',       '🗺️', 'chapters_done',     2),
('Centurião',      'Acumule 500 XP',             '🏆', 'total_xp',        500),
('Mestre',         'Complete todas as lições',   '👑', 'lessons_completed', 9);
