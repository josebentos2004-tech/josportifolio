SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS josportifolio
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE josportifolio;

-- =========================================================
-- 1. MÍDIA CENTRALIZADA
-- =========================================================
CREATE TABLE IF NOT EXISTS media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  width INT UNSIGNED DEFAULT NULL,
  height INT UNSIGNED DEFAULT NULL,
  alt_text VARCHAR(255) DEFAULT NULL,
  data MEDIUMBLOB NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_media_mime (mime_type)
) ENGINE=InnoDB;

-- =========================================================
-- 2. CONFIGURAÇÕES
-- =========================================================
CREATE TABLE IF NOT EXISTS site_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  site_name VARCHAR(150) NOT NULL,
  brand_name VARCHAR(150) NOT NULL,
  owner_name VARCHAR(150) NOT NULL,
  site_title VARCHAR(255) DEFAULT NULL,
  meta_description TEXT DEFAULT NULL,
  whatsapp VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3. SEÇÕES
-- =========================================================
CREATE TABLE IF NOT EXISTS site_sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(50) NOT NULL UNIQUE,
  menu_label VARCHAR(100) DEFAULT NULL,
  title VARCHAR(200) DEFAULT NULL,
  title_highlight VARCHAR(200) DEFAULT NULL,
  subtitle TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  item_limit INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 4. HERO
-- =========================================================
CREATE TABLE IF NOT EXISTS hero (
  id TINYINT UNSIGNED PRIMARY KEY,
  badge VARCHAR(255) DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  subtitle TEXT DEFAULT NULL,
  description TEXT DEFAULT NULL,
  button_primary VARCHAR(100) DEFAULT NULL,
  button_primary_url VARCHAR(255) DEFAULT NULL,
  button_secondary VARCHAR(100) DEFAULT NULL,
  button_secondary_url VARCHAR(255) DEFAULT NULL,
  media_id BIGINT UNSIGNED DEFAULT NULL,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 5. SOBRE
-- =========================================================
CREATE TABLE IF NOT EXISTS about (
  id TINYINT UNSIGNED PRIMARY KEY,
  title VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  image_id BIGINT UNSIGNED DEFAULT NULL,
  FOREIGN KEY(image_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 6. SERVIÇOS
-- =========================================================
CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  icon_media_id BIGINT UNSIGNED DEFAULT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY(icon_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 7. TECNOLOGIAS
-- =========================================================
CREATE TABLE IF NOT EXISTS technologies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(100) NOT NULL,
  name VARCHAR(100) NOT NULL,
  icon_media_id BIGINT UNSIGNED DEFAULT NULL,
  level TINYINT UNSIGNED DEFAULT 80,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY(icon_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 8. PROCESSO
-- =========================================================
CREATE TABLE IF NOT EXISTS process_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  number INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  icon_media_id BIGINT UNSIGNED DEFAULT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY(icon_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 9. PROJETOS
-- =========================================================
CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  category VARCHAR(150) DEFAULT NULL,
  short_description TEXT DEFAULT NULL,
  description LONGTEXT DEFAULT NULL,
  project_url VARCHAR(500) DEFAULT NULL,
  github_url VARCHAR(500) DEFAULT NULL,
  status ENUM('draft','published','archived') DEFAULT 'draft',
  featured TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0,
  views BIGINT UNSIGNED DEFAULT 0,
  rating_average DECIMAL(3,2) DEFAULT 0,
  rating_count INT UNSIGNED DEFAULT 0,
  published_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_projects_status(status),
  INDEX idx_projects_featured(featured)
) ENGINE=InnoDB;

-- =========================================================
-- 10. GALERIA DOS PROJETOS — MÁXIMO 4 SLOTS
-- slot 1 = capa; slots 2-4 = imagens adicionais
-- =========================================================
CREATE TABLE IF NOT EXISTS project_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  media_id BIGINT UNSIGNED NOT NULL,
  slot TINYINT UNSIGNED NOT NULL,
  title VARCHAR(200) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE CASCADE,
  UNIQUE KEY uq_project_slot(project_id,slot),
  UNIQUE KEY uq_project_media(project_id,media_id),
  CHECK(slot BETWEEN 1 AND 4)
) ENGINE=InnoDB;

-- =========================================================
-- 11. TECNOLOGIAS POR PROJETO
-- =========================================================
CREATE TABLE IF NOT EXISTS project_technologies (
  project_id BIGINT UNSIGNED NOT NULL,
  technology_id INT UNSIGNED NOT NULL,
  sort_order INT DEFAULT 0,
  PRIMARY KEY(project_id,technology_id),
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY(technology_id) REFERENCES technologies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 12. VISUALIZAÇÕES
-- =========================================================
CREATE TABLE IF NOT EXISTS project_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  view_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  UNIQUE KEY uq_project_daily(project_id,visitor_hash,view_date)
) ENGINE=InnoDB;

-- =========================================================
-- 13. ESTRELAS
-- =========================================================
CREATE TABLE IF NOT EXISTS project_ratings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  email VARCHAR(254) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  UNIQUE KEY uq_project_email(project_id,email),
  CHECK(rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- =========================================================
-- 14. CONTACTOS
-- =========================================================
CREATE TABLE IF NOT EXISTS contact_channels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50) NOT NULL,
  label VARCHAR(100) NOT NULL,
  value VARCHAR(255) NOT NULL,
  icon_media_id BIGINT UNSIGNED DEFAULT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY(icon_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 15. REDES SOCIAIS
-- =========================================================
CREATE TABLE IF NOT EXISTS social_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  icon_media_id BIGINT UNSIGNED DEFAULT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY(icon_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 16. FOOTER
-- =========================================================
CREATE TABLE IF NOT EXISTS footer_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_title VARCHAR(100) NOT NULL,
  label VARCHAR(150) NOT NULL,
  url VARCHAR(500) NOT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 17. MENSAGENS
-- =========================================================
CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','replied','archived') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 18. ADMINISTRADORES
-- =========================================================
CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- DADOS INICIAIS
-- =========================================================
INSERT INTO site_settings
(id, site_name, brand_name, owner_name, site_title, meta_description, whatsapp, email)
VALUES
(1, 'JosPortifolio', 'JosDigital', 'José Silva Bentos',
 'José Silva Bentos | JosDigital',
 'Portfólio profissional de José Silva Bentos e JosDigital.',
 '+244', '')
ON DUPLICATE KEY UPDATE
    site_name = VALUES(site_name),
    brand_name = VALUES(brand_name),
    owner_name = VALUES(owner_name),
    site_title = VALUES(site_title),
    meta_description = VALUES(meta_description);

INSERT INTO site_sections
(section_key, menu_label, title, title_highlight, subtitle, sort_order, item_limit, is_active)
VALUES
('hero', 'Início', '', '', '', 1, NULL, 1),
('sobre', 'Sobre', 'Sobre', 'Mim', '', 2, NULL, 1),
('servicos', 'Serviços', 'Os Meus', 'Serviços', '', 3, NULL, 1),
('projetos', 'Projetos', 'Projetos', 'Recentes', '', 4, 3, 1),
('tecnologias', 'Tecnologias', 'Tecnologias', 'que domino', '', 5, NULL, 1),
('processo', 'Processo', 'Processo de', 'Trabalho', '', 6, NULL, 1),
('contacto', 'Contacto', 'Vamos conversar?', '', '', 7, NULL, 1)
ON DUPLICATE KEY UPDATE
    menu_label = VALUES(menu_label),
    title = VALUES(title),
    title_highlight = VALUES(title_highlight),
    subtitle = VALUES(subtitle),
    sort_order = VALUES(sort_order),
    item_limit = VALUES(item_limit),
    is_active = VALUES(is_active);

INSERT INTO hero
(id, badge, title, subtitle, description, button_primary, button_primary_url, button_secondary, button_secondary_url, media_id)
VALUES
(1,
 'Disponível para novos projetos',
 'José Silva Bentos',
 'Desenvolvedor de Sistemas & Especialista em Soluções Digitais',
 'Desenvolvimento de soluções web e desktop, design e ferramentas digitais para empresas.',
 'Ver Projetos', '#projetos',
 'Entrar em Contacto', '#contacto', NULL)
ON DUPLICATE KEY UPDATE
    badge = VALUES(badge),
    title = VALUES(title),
    subtitle = VALUES(subtitle),
    description = VALUES(description),
    button_primary = VALUES(button_primary),
    button_primary_url = VALUES(button_primary_url),
    button_secondary = VALUES(button_secondary),
    button_secondary_url = VALUES(button_secondary_url);

INSERT INTO about (id, title, description, image_id)
VALUES
(1,
 'Sou José Silva Bentos, fundador da JosDigital.',
 'Construo soluções digitais com foco em desenvolvimento de software, presença digital e design.',
 NULL)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description);

-- Conta inicial: email j@gmail.com / senha 123456.
-- A senha abaixo já está em formato password_hash() do PHP.
INSERT INTO admin_users (id, name, email, password, status)
VALUES
(1, 'José Silva', 'j@gmail.com',
 '$2y$12$XNGVQ6c7H84j7uvJfeV48OGQFO1NtQ/hTButSqFoGDHzivXZPxo/u',
 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    status = VALUES(status);

SET FOREIGN_KEY_CHECKS = 1;
-- JosPortifolio: permitir avaliações com e-mail obrigatório.
-- Executar uma única vez em instalações que já têm a tabela project_ratings.
-- Registos antigos mantêm email = NULL; novas avaliações enviam sempre e-mail.

ALTER TABLE project_ratings
    ADD COLUMN email VARCHAR(254) NULL AFTER visitor_hash,
    DROP INDEX uq_project_rating,
    ADD UNIQUE KEY uq_project_email (project_id, email);
