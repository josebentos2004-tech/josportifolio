<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Project
{
    public static function featured(int $limit = 3): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = db()->prepare(
            'SELECT p.*, pi.media_id AS cover_media_id, m.mime_type AS cover_mime_type
             FROM projects p
             LEFT JOIN project_images pi
                ON pi.project_id = p.id AND pi.slot = 1
             LEFT JOIN media m ON m.id = pi.media_id
             WHERE p.status = \'published\'
             ORDER BY p.featured DESC, p.sort_order ASC, p.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countPublished(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn();
    }

    public static function all(bool $includeDraft = false): array
    {
        $where = $includeDraft ? '' : "WHERE p.status = 'published'";
        $sql = "SELECT p.*, pi.media_id AS cover_media_id, m.mime_type AS cover_mime_type
                FROM projects p
                LEFT JOIN project_images pi
                    ON pi.project_id = p.id AND pi.slot = 1
                LEFT JOIN media m ON m.id = pi.media_id
                {$where}
                ORDER BY p.sort_order ASC, p.id DESC";
        return db()->query($sql)->fetchAll();
    }

    public static function findById(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT p.*, pi.media_id AS cover_media_id, m.mime_type AS cover_mime_type
             FROM projects p
             LEFT JOIN project_images pi
                ON pi.project_id = p.id AND pi.slot = 1
             LEFT JOIN media m ON m.id = pi.media_id
             WHERE p.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public static function findBySlug(string $slug): array
    {
        $stmt = db()->prepare(
            "SELECT p.*, pi.media_id AS cover_media_id, m.mime_type AS cover_mime_type
             FROM projects p
             LEFT JOIN project_images pi
                ON pi.project_id = p.id AND pi.slot = 1
             LEFT JOIN media m ON m.id = pi.media_id
             WHERE p.slug = :slug AND p.status = 'published'
             LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: [];
    }

    public static function images(int $projectId): array
    {
        $stmt = db()->prepare(
            'SELECT pi.*, m.id AS media_id, m.filename, m.mime_type, m.file_size,
                    m.width, m.height, m.alt_text
             FROM project_images pi
             LEFT JOIN media m ON m.id = pi.media_id
             WHERE pi.project_id = :project_id
             ORDER BY pi.slot ASC'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public static function technologies(int $projectId): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
             FROM project_technologies pt
             INNER JOIN technologies t ON t.id = pt.technology_id
             LEFT JOIN media m ON m.id = t.icon_media_id
             WHERE pt.project_id = :project_id
             ORDER BY pt.sort_order ASC, t.name ASC'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public static function views(int $projectId): int
    {
        $stmt = db()->prepare('SELECT views FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $projectId]);
        return (int) $stmt->fetchColumn();
    }

    public static function rating(int $projectId): array
    {
        $stmt = db()->prepare(
            'SELECT COALESCE(AVG(rating), 0) AS avg_rating,
                    COUNT(*) AS total
             FROM project_ratings
             WHERE project_id = :project_id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $row = $stmt->fetch();
        return $row ?: ['avg_rating' => 0, 'total' => 0];
    }

    public static function addView(int $projectId, string $visitorHash): void
    {
        $pdo = db();

        $insert = $pdo->prepare(
            'INSERT IGNORE INTO project_views
                (project_id, visitor_hash, view_date)
             VALUES
                (:project_id, :visitor_hash, CURDATE())'
        );
        $insert->execute([
            'project_id' => $projectId,
            'visitor_hash' => $visitorHash,
        ]);

        if ($insert->rowCount() === 1) {
            $update = $pdo->prepare(
                'UPDATE projects
                 SET views = (
                    SELECT COUNT(*) FROM project_views
                    WHERE project_id = :project_id_views
                 )
                 WHERE id = :project_id'
            );
            $update->execute([
                'project_id_views' => $projectId,
                'project_id' => $projectId,
            ]);
        }
    }

    /**
     * Regista uma avaliação por endereço de e-mail e projecto.
     * Retorna false quando o e-mail já avaliou este projecto.
     */
    public static function rate(int $projectId, string $email, int $rating): bool
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Projecto inválido.');
        }
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Avaliação inválida.');
        }

        $email = strtolower(trim($email));
        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Introduza um endereço de e-mail válido.');
        }

        // O controlo de uma avaliação é feito pelo e-mail normalizado, não pelo IP.
        // Usar um hash estável do e-mail no campo visitor_hash evita que um índice
        // antigo baseado em (project_id, visitor_hash) bloqueie pessoas diferentes
        // que utilizam a mesma rede/IP. Mantém compatibilidade com o esquema actual.
        $visitorHash = hash('sha256', 'rating-email:' . $email);

        $pdo = db();

        // Resposta explícita para avaliações repetidas, sem depender apenas da mensagem do MySQL.
        $duplicate = $pdo->prepare(
            'SELECT id FROM project_ratings
             WHERE project_id = :project_id AND LOWER(email) = :email
             LIMIT 1'
        );
        $duplicate->execute([
            'project_id' => $projectId,
            'email' => $email,
        ]);
        if ($duplicate->fetchColumn()) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO project_ratings
                    (project_id, visitor_hash, email, rating)
                 VALUES
                    (:project_id, :visitor_hash, :email, :rating)'
            );
            $stmt->execute([
                'project_id' => $projectId,
                'visitor_hash' => $visitorHash,
                'email' => $email,
                'rating' => $rating,
            ]);

            // Recalcular a média e a contagem depois do INSERT, para que o painel
            // e a página pública apresentem exactamente os mesmos totais.
            $aggregate = $pdo->prepare(
                'SELECT COALESCE(AVG(rating), 0) AS average_rating, COUNT(*) AS total_ratings
                 FROM project_ratings
                 WHERE project_id = :project_id'
            );
            $aggregate->execute(['project_id' => $projectId]);
            $totals = $aggregate->fetch() ?: ['average_rating' => 0, 'total_ratings' => 0];

            $update = $pdo->prepare(
                'UPDATE projects
                 SET rating_average = :average_rating,
                     rating_count = :total_ratings
                 WHERE id = :project_id'
            );
            $update->execute([
                'average_rating' => (float) $totals['average_rating'],
                'total_ratings' => (int) $totals['total_ratings'],
                'project_id' => $projectId,
            ]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            if ($driverCode === 1062) {
                // Pode ser o índice único por e-mail ou uma instalação que ainda
                // conserva o índice histórico sobre visitor_hash. Como visitorHash
                // é agora derivado do e-mail, em ambos os casos trata-se de voto
                // repetido para esta identidade; não é necessário alterar o SQL.
                return false;
            }
            throw $e;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

}
