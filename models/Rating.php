<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Rating
{
    public static function projectStats(int $projectId): array
    {
        $stmt = db()->prepare(
            'SELECT COALESCE(AVG(rating), 0) AS avg_rating,
                    COUNT(*) AS total
             FROM project_ratings
             WHERE project_id = :project_id'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetch() ?: ['avg_rating' => 0, 'total' => 0];
    }
}
