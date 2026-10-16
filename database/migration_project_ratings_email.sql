-- JosPortifolio: permitir avaliações com e-mail obrigatório.
-- Executar uma única vez em instalações que já têm a tabela project_ratings.
-- Registos antigos mantêm email = NULL; novas avaliações enviam sempre e-mail.

ALTER TABLE project_ratings
    ADD COLUMN email VARCHAR(254) NULL AFTER visitor_hash,
    DROP INDEX uq_project_rating,
    ADD UNIQUE KEY uq_project_email (project_id, email);
