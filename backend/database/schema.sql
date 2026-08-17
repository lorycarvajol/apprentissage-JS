-- ================================================================
-- Base de données : apprentissage_js
-- Description : Schéma complet pour la plateforme d'apprentissage JavaScript
-- Ce fichier consolide directement ce qui, dans le projet PHP jumeau
-- (apprentissage-POO-PHP), a été ajouté après coup par add_password_reset.sql
-- et add_auth_hardening.sql : il n'y a pas d'historique de migrations à
-- rejouer ici puisque ce projet démarre d'un schéma déjà « final ».
-- ================================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS apprentissage_js
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE apprentissage_js;

-- ================================================================
-- Table : users
-- Description : Gestion des utilisateurs (inclut directement les colonnes
-- de durcissement auth : vérification email, verrouillage après échecs,
-- réinitialisation de mot de passe)
-- ================================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('student', 'admin') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expires_at TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_reset_token (reset_token),
    INDEX idx_email_verification_token (email_verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : modules
-- Description : Modules de formation (vide au départ — le contenu du
-- cours JavaScript est créé via /admin, aucune donnée de démo insérée ici)
-- ================================================================
CREATE TABLE modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_index INT NOT NULL,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : chapitres
-- Description : Chapitres de chaque module
-- ================================================================
CREATE TABLE chapitres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_index INT NOT NULL,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : theories
-- Description : Contenu théorique de chaque chapitre
-- ================================================================
CREATE TABLE theories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapitre_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    order_index INT NOT NULL,
    estimated_time INT DEFAULT 10 COMMENT 'Temps estimé en minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapitre_id) REFERENCES chapitres(id) ON DELETE CASCADE,
    INDEX idx_chapitre (chapitre_id),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : exercices
-- Description : Exercices pratiques pour chaque chapitre.
-- expected_output est propre à ce projet JS : contrairement au backend PHP
-- qui ré-exécutait solution_code à chaque soumission via CodeExecutionService
-- (proc_open côté serveur), il n'y a ici aucun moteur JS serveur — la
-- sortie de référence est calculée une seule fois côté client (sandbox
-- Web Worker, voir frontend/src/utils/jsSandbox.js) au moment où l'admin
-- enregistre l'exercice, puis stockée pour comparaison à chaque soumission.
-- ================================================================
CREATE TABLE exercices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapitre_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    instructions TEXT,
    starter_code TEXT COMMENT 'Code de départ fourni',
    html_fixture TEXT COMMENT 'HTML de départ pour un exercice avec DOM réel (runJsWithDom), NULL sinon',
    solution_code TEXT COMMENT 'Solution de référence',
    expected_output TEXT COMMENT 'Sortie de référence, précalculée côté client',
    test_cases JSON COMMENT 'Tests unitaires pour validation',
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    points INT DEFAULT 10 COMMENT 'Points attribués à la réussite',
    order_index INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapitre_id) REFERENCES chapitres(id) ON DELETE CASCADE,
    INDEX idx_chapitre (chapitre_id),
    INDEX idx_difficulty (difficulty),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : progression
-- Description : Suivi de la progression globale par utilisateur et chapitre
-- ================================================================
CREATE TABLE progression (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    chapitre_id INT UNSIGNED NOT NULL,
    total_theories INT DEFAULT 0,
    theories_completed INT DEFAULT 0,
    total_exercices INT DEFAULT 0,
    exercices_completed INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    time_spent INT DEFAULT 0 COMMENT 'Temps passé en secondes',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapitre_id) REFERENCES chapitres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chapitre (user_id, chapitre_id),
    INDEX idx_user (user_id),
    INDEX idx_chapitre (chapitre_id),
    INDEX idx_completion (completion_percentage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : theorie_lue
-- Description : Suivi des théories lues par utilisateur
-- ================================================================
CREATE TABLE theorie_lue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    theorie_id INT UNSIGNED NOT NULL,
    time_spent INT DEFAULT 0 COMMENT 'Temps passé en secondes',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (theorie_id) REFERENCES theories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_theorie (user_id, theorie_id),
    INDEX idx_user (user_id),
    INDEX idx_theorie (theorie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : exercice_soumis
-- Description : Historique des soumissions d'exercices
-- ================================================================
CREATE TABLE exercice_soumis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exercice_id INT UNSIGNED NOT NULL,
    submitted_code TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    execution_result JSON COMMENT 'Résultat de l\'exécution (capturé côté client) et tests',
    points_earned INT DEFAULT 0,
    attempt_number INT DEFAULT 1,
    time_spent INT DEFAULT 0 COMMENT 'Temps passé en secondes',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_id) REFERENCES exercices(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_exercice (exercice_id),
    INDEX idx_correct (is_correct),
    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : badges
-- Description : Badges disponibles dans le système
-- ================================================================
CREATE TABLE badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100) COMMENT 'URL ou nom du fichier icône',
    category ENUM('progression', 'streak', 'achievement', 'special', 'grade') DEFAULT 'achievement',
    condition_type VARCHAR(50) COMMENT 'Type de condition (ex: complete_module, streak_days)',
    condition_value JSON COMMENT 'Valeur de la condition',
    points_reward INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Le nom identifie le badge : c'est lui qui rend le seed rejouable.
    -- Sans cette contrainte, l'INSERT ci-dessous ne lève jamais « Duplicate
    -- entry », migrate.php n'a donc rien à ignorer et réinsère les 19 badges
    -- à chaque démarrage du conteneur. Constaté en production le 17/08/2026 :
    -- 95 badges en base après cinq déploiements, titres obtenus en double,
    -- et une salle des trophées annonçant « 7 / 95 ».
    UNIQUE KEY uq_badges_name (name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : user_badges
-- Description : Badges obtenus par les utilisateurs
-- ================================================================
CREATE TABLE user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user (user_id),
    INDEX idx_badge (badge_id),
    INDEX idx_earned (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : points
-- Description : Historique des points gagnés
-- ================================================================
CREATE TABLE points (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(200) NOT NULL,
    reference_type VARCHAR(50) COMMENT 'Type de référence (exercice, badge, etc.)',
    reference_id INT UNSIGNED COMMENT 'ID de la référence',
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_earned (earned_at),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : streaks
-- Description : Suivi des séries quotidiennes d'activité
-- ================================================================
CREATE TABLE streaks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_streak (user_id),
    INDEX idx_current_streak (current_streak),
    INDEX idx_longest_streak (longest_streak)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : quiz (optionnel pour extension future — non branchée à ce jour)
-- Description : Quiz d'évaluation
-- ================================================================
CREATE TABLE quiz (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapitre_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    time_limit INT COMMENT 'Temps limite en minutes',
    passing_score INT DEFAULT 70 COMMENT 'Score minimum pour réussir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapitre_id) REFERENCES chapitres(id) ON DELETE CASCADE,
    INDEX idx_chapitre (chapitre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : questions (optionnel pour extension future — non branchée à ce jour)
-- Description : Questions des quiz
-- ================================================================
CREATE TABLE questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq', 'multiple', 'code', 'true_false') DEFAULT 'mcq',
    options JSON COMMENT 'Options de réponse',
    correct_answer JSON COMMENT 'Réponse(s) correcte(s)',
    explanation TEXT COMMENT 'Explication de la réponse',
    points INT DEFAULT 1,
    order_index INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : login_history
-- Description : Journal des tentatives de connexion (succès/échec), sert
-- de base au rate-limiting IP + par-compte (RateLimitService). user_id
-- reste NULL pour un identifiant inconnu (permet quand même le suivi par IP).
-- ================================================================
CREATE TABLE login_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ip_status_date (ip_address, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table : refresh_tokens
-- Description : Refresh tokens rotatifs (hash SHA-256 uniquement, jamais le
-- token en clair), portés par un cookie httpOnly côté client. remember
-- contrôle la durée de vie (1 jour vs 30 jours, voir RefreshTokenService).
-- ================================================================
CREATE TABLE refresh_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    remember TINYINT(1) DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Insertion des données initiales
-- Aucun module/chapitre/théorie/exercice n'est pré-rempli : le contenu du
-- cours JavaScript est entièrement à créer via /admin, comme demandé.
-- Seuls les badges (chrome de gamification, indépendant du contenu) sont
-- repris du projet PHP jumeau, avec les mentions PHP/POO reformulées.
-- ================================================================

-- INSERT IGNORE, et non INSERT : migrate.php est rejoué à chaque démarrage du
-- conteneur backend. Combiné à la contrainte d'unicité sur `name` ci-dessus,
-- c'est ce qui rend ce seed réellement idempotent plutôt que seulement réputé
-- tel. Ne pas remettre un INSERT nu ici.
INSERT IGNORE INTO badges (name, description, icon, category, condition_type, condition_value, points_reward) VALUES
('Premier Pas', 'Complétez votre premier chapitre', 'first-step.png', 'progression', 'complete_chapter', '{"count": 1}', 50),
('Débutant', 'Complétez le Module 1', 'beginner.png', 'progression', 'complete_module', '{"module_id": 1}', 100),
('Marathonien', 'Série de 7 jours consécutifs', 'streak-7.png', 'streak', 'streak_days', '{"days": 7}', 150),
('Perfectionniste', 'Réussissez 10 exercices du premier coup', 'perfectionist.png', 'achievement', 'first_try_success', '{"count": 10}', 200),
('Expert', 'Complétez tous les modules', 'expert.png', 'progression', 'complete_all_modules', '{}', 500),
('Sur la lancée', 'Complétez 5 chapitres', 'streak-5-chapters.png', 'progression', 'complete_chapter', '{"count": 5}', 75),
('Encyclopédiste', 'Complétez 10 chapitres', 'encyclopedist.png', 'progression', 'complete_chapter', '{"count": 10}', 150),
('Assidu', 'Série de 3 jours consécutifs', 'streak-3.png', 'streak', 'streak_days', '{"days": 3}', 50),
('Increvable', 'Série de 30 jours consécutifs', 'streak-30.png', 'streak', 'streak_days', '{"days": 30}', 300),
('Tireur d''élite', 'Réussissez 25 exercices du premier coup', 'sharpshooter.png', 'achievement', 'first_try_success', '{"count": 25}', 300),
('Mille Points', 'Cumulez 1000 points', 'thousand-points.png', 'achievement', 'points_total', '{"count": 1000}', 100),
('Oiseau de nuit', 'Lisez une théorie ou réussissez un exercice entre minuit et 5h du matin', 'night-owl.png', 'special', 'time_of_day', '{"start_hour": 0, "end_hour": 5}', 50),
('Lève-tôt', 'Lisez une théorie ou réussissez un exercice entre 5h et 7h du matin', 'early-bird.png', 'special', 'time_of_day', '{"start_hour": 5, "end_hour": 7}', 50),
('Manant', 'Un manant est un paysan libre du royaume, taillable et corvéable à merci. Vous venez de faire vos premiers pas dans la hiérarchie (100 points cumulés).', 'grade-manant.png', 'grade', 'points_total', '{"count": 100}', 25),
('Écuyer', 'Vous servez désormais un chevalier et apprenez le métier des armes... ou du code (400 points cumulés).', 'grade-ecuyer.png', 'grade', 'points_total', '{"count": 400}', 50),
('Chevalier', 'Adoubé ! Vous portez fièrement les couleurs du royaume JavaScript (1000 points cumulés).', 'grade-chevalier.png', 'grade', 'points_total', '{"count": 1000}', 100),
('Baron', 'Un fief vous est confié : la noblesse du code vous reconnaît comme l''un des siens (2000 points cumulés).', 'grade-baron.png', 'grade', 'points_total', '{"count": 2000}', 200),
('Roi', 'Vous régnez sans partage sur le royaume du développement JavaScript. Longue vie au Roi ! (3200 points cumulés)', 'grade-roi.png', 'grade', 'points_total', '{"count": 3200}', 400),
('Tenace', 'Cinq échecs, six tentatives, une victoire. La persévérance a fini par payer.', 'stubborn.png', 'special', 'late_success', '{"min_attempt": 6}', 75);
