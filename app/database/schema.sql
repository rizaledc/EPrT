-- PerumahanInggris · skema database
-- MySQL 8 / MariaDB 10.6+, InnoDB, utf8mb4_unicode_ci
-- Impor lewat phpMyAdmin (hPanel) atau: mysql -u USER -p DBNAME < schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(20)  NOT NULL,
  username_lower  VARCHAR(20)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('member','admin') NOT NULL DEFAULT 'member',
  lang            ENUM('id','en') NOT NULL DEFAULT 'id',
  plan            VARCHAR(20) NOT NULL DEFAULT 'free',
  target_score    SMALLINT UNSIGNED NOT NULL DEFAULT 500,
  recovery_hash   VARCHAR(255) NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  status          ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_active_at  DATETIME NULL,
  UNIQUE KEY uq_users_username (username_lower)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  UNIQUE KEY uq_token (token_hash),
  CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(40) NOT NULL,
  ip_hash     CHAR(64) NOT NULL,
  success     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_la_user (username, created_at),
  KEY idx_la_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kolom teknis tambahan: batas laju register & API
CREATE TABLE IF NOT EXISTS rate_limits (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket      VARCHAR(80) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rl (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bloks (
  id       TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code     CHAR(1) NOT NULL,
  name_id  VARCHAR(60) NOT NULL,
  name_en  VARCHAR(60) NOT NULL,
  sort     TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_blok_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rumahs (
  id           SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blok_id      TINYINT UNSIGNED NULL,
  number       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  slug         VARCHAR(20) NOT NULL,
  kind         ENUM('practice','adaptive','simulation','sample') NOT NULL DEFAULT 'practice',
  title_id     VARCHAR(160) NOT NULL,
  title_en     VARCHAR(160) NOT NULL,
  material_id  MEDIUMTEXT NULL,
  material_en  MEDIUMTEXT NULL,
  sort         SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_rumah_slug (slug),
  CONSTRAINT fk_rumah_blok FOREIGN KEY (blok_id) REFERENCES bloks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audio_files (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path         VARCHAR(190) NOT NULL,
  duration_ms  INT UNSIGNED NULL,
  hash         CHAR(64) NULL,
  uploaded_by  INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_audio_path (path),
  CONSTRAINT fk_audio_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS passages (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rumah_id       SMALLINT UNSIGNED NOT NULL,
  section        ENUM('listening','reading') NOT NULL,
  part           ENUM('A','B','C') NULL,
  title          VARCHAR(190) NULL,
  body           MEDIUMTEXT NULL,
  script         JSON NULL,
  audio_talk_id  INT UNSIGNED NULL,
  sort           SMALLINT NOT NULL DEFAULT 0,
  KEY idx_passage_rumah (rumah_id, section, sort),
  CONSTRAINT fk_passage_rumah FOREIGN KEY (rumah_id) REFERENCES rumahs(id) ON DELETE CASCADE,
  CONSTRAINT fk_passage_audio FOREIGN KEY (audio_talk_id) REFERENCES audio_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rule_tags (
  code           VARCHAR(30) NOT NULL PRIMARY KEY,
  section        ENUM('listening','structure','reading') NOT NULL,
  name_id        VARCHAR(120) NOT NULL,
  name_en        VARCHAR(120) NOT NULL,
  glossary_slug  VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rumah_id           SMALLINT UNSIGNED NOT NULL,
  passage_id         INT UNSIGNED NULL,
  section            ENUM('listening','structure','reading') NOT NULL,
  type               VARCHAR(30) NOT NULL,
  subtype            VARCHAR(30) NULL,
  number             SMALLINT UNSIGNED NOT NULL,
  prompt             TEXT NULL,
  options            JSON NOT NULL,
  underlines         JSON NULL,
  correct_index      TINYINT UNSIGNED NOT NULL,
  why_correct_id     TEXT NULL,
  why_correct_en     TEXT NULL,
  why_wrong_id       JSON NULL,
  why_wrong_en       JSON NULL,
  analogy_id         TEXT NULL,
  analogy_en         TEXT NULL,
  tip_id             TEXT NULL,
  tip_en             TEXT NULL,
  key_sentence       TEXT NULL,
  key_translation_id TEXT NULL,
  liaison_note       VARCHAR(255) NULL,
  evidence_quote     TEXT NULL,
  rule_tags          JSON NULL,
  difficulty         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  audio_question_id  INT UNSIGNED NULL,
  status             ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_q_number (rumah_id, section, number),
  KEY idx_q_status (status),
  CONSTRAINT fk_q_rumah FOREIGN KEY (rumah_id) REFERENCES rumahs(id) ON DELETE CASCADE,
  CONSTRAINT fk_q_passage FOREIGN KEY (passage_id) REFERENCES passages(id) ON DELETE SET NULL,
  CONSTRAINT fk_q_audio FOREIGN KEY (audio_question_id) REFERENCES audio_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  rumah_id      SMALLINT UNSIGNED NOT NULL,
  section       ENUM('listening','structure','reading') NOT NULL,
  mode          ENUM('practice','simulation','review') NOT NULL DEFAULT 'practice',
  question_ids  JSON NULL,
  started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at   DATETIME NULL,
  time_spent_s  INT UNSIGNED NOT NULL DEFAULT 0,
  correct       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  total         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  remaining_s   INT NULL,
  tick_at       DATETIME NULL,
  KEY idx_att_user (user_id, rumah_id, section),
  CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_rumah FOREIGN KEY (rumah_id) REFERENCES rumahs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS answers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id    INT UNSIGNED NOT NULL,
  question_id   INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  chosen_index  TINYINT UNSIGNED NULL,
  is_correct    TINYINT(1) NOT NULL DEFAULT 0,
  time_ms       INT UNSIGNED NOT NULL DEFAULT 0,
  flagged       TINYINT(1) NOT NULL DEFAULT 0,
  audio_missing TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_answer (attempt_id, question_id),
  KEY idx_ans_q (question_id),
  KEY idx_ans_user (user_id),
  CONSTRAINT fk_ans_att FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ans_q FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  CONSTRAINT fk_ans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audio Listening yang sudah diputar per soal per percobaan (audio di tes asli hanya sekali)
CREATE TABLE IF NOT EXISTS audio_plays (
  attempt_id    INT UNSIGNED NOT NULL,
  question_id   INT UNSIGNED NOT NULL,
  played_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attempt_id, question_id),
  CONSTRAINT fk_ap_att FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ap_q FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS progress (
  user_id            INT UNSIGNED NOT NULL,
  rumah_id           SMALLINT UNSIGNED NOT NULL,
  answered_distinct  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  best_pct           DECIMAL(5,2) NULL,
  last_pct           DECIMAL(5,2) NULL,
  completed_at       DATETIME NULL,
  PRIMARY KEY (user_id, rumah_id),
  CONSTRAINT fk_prog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_prog_rumah FOREIGN KEY (rumah_id) REFERENCES rumahs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mistakes (
  user_id         INT UNSIGNED NOT NULL,
  question_id     INT UNSIGNED NOT NULL,
  wrong_count     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  correct_streak  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  last_seen_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, question_id),
  CONSTRAINT fk_mis_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mis_q FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS glossary (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(60) NOT NULL,
  grp         VARCHAR(30) NOT NULL,
  term_en     VARCHAR(80) NOT NULL,
  meaning_id  VARCHAR(120) NOT NULL,
  def_id      TEXT NULL,
  def_en      TEXT NULL,
  example     TEXT NULL,
  analogy_id  TEXT NULL,
  analogy_en  TEXT NULL,
  sort        SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_glossary_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsors (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL,
  url         VARCHAR(255) NOT NULL,
  desc_id     VARCHAR(255) NULL,
  desc_en     VARCHAR(255) NULL,
  logo_path   VARCHAR(190) NULL,
  is_pinned   TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key`   VARCHAR(60) NOT NULL PRIMARY KEY,
  value   JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NULL,
  action      VARCHAR(60) NOT NULL,
  target      VARCHAR(190) NULL,
  meta        JSON NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_time (created_at),
  CONSTRAINT fk_log_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Data dasar (aman dijalankan ulang)
INSERT IGNORE INTO bloks (code, name_id, name_en, sort) VALUES
 ('A','Fondasi','Foundation',1),
 ('B','Menengah','Intermediate',2),
 ('C','Lanjut','Advanced',3),
 ('D','Pemantapan','Mastery',4);

INSERT IGNORE INTO settings (`key`, value) VALUES
 ('score_table', '{"points":[0,5,10,15,20,25,30,35,40,45,50],"listening":[31,32,37,41,44,47,50,53,57,61,68],"structure":[31,31,36,41,46,50,55,60,68,null,null],"reading":[31,31,34,39,43,46,49,52,56,60,67]}'),
 ('registration_open', 'true'),
 ('default_target', '500'),
 ('announcement', '{"id":"","en":""}');

-- Sponsor bawaan: hanya sekali (tabel kosong dan belum pernah di-seed), agar edit/hapus admin tidak dikembalikan
INSERT INTO sponsors (name, url, desc_id, desc_en, logo_path, is_pinned, sort_order)
SELECT s.* FROM (
  SELECT 'orbitani.com' AS name, 'https://orbitani.com' AS url,
    'Sponsor utama PerumahanInggris. (Deskripsi sementara, ubah di Admin → Sponsor.)' AS desc_id,
    'Main sponsor of PerumahanInggris. (Placeholder, edit in Admin → Sponsors.)' AS desc_en,
    NULL AS logo_path, 1 AS is_pinned, 1 AS sort_order
  UNION ALL
  SELECT 'kodinginaja.biz.id', 'https://kodinginaja.biz.id',
    'Mendukung PerumahanInggris. (Deskripsi sementara, ubah di Admin → Sponsor.)',
    'Supporting PerumahanInggris. (Placeholder, edit in Admin → Sponsors.)',
    NULL, 0, 2
) s
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'sponsors_seeded')
  AND NOT EXISTS (SELECT 1 FROM sponsors);

INSERT IGNORE INTO settings (`key`, value) VALUES ('sponsors_seeded', 'true');
