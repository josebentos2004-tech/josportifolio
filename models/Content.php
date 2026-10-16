<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Content
{
    public static function settings(): array
    {
        $stmt = db()->prepare('SELECT * FROM site_settings WHERE id = 1 LIMIT 1');
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }

    public static function sections(): array
    {
        $stmt = db()->query(
            'SELECT * FROM site_sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }

    public static function allSections(): array
    {
        return db()->query(
            'SELECT * FROM site_sections ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    }

    public static function section(string $key): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM site_sections WHERE section_key = :section_key LIMIT 1'
        );
        $stmt->execute(['section_key' => $key]);
        return $stmt->fetch() ?: [];
    }

    public static function hero(): array
    {
        $stmt = db()->query(
            'SELECT h.*, m.id AS media_id, m.mime_type AS media_mime_type
             FROM hero h
             LEFT JOIN media m ON m.id = h.media_id
             WHERE h.id = 1
             LIMIT 1'
        );
        return $stmt->fetch() ?: [];
    }

    public static function about(): array
    {
        $stmt = db()->query(
            'SELECT a.*, m.id AS image_id, m.mime_type AS image_mime_type
             FROM about a
             LEFT JOIN media m ON m.id = a.image_id
             WHERE a.id = 1
             LIMIT 1'
        );
        return $stmt->fetch() ?: [];
    }

    public static function services(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE s.is_active = 1';
        $sql = "SELECT s.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
                FROM services s
                LEFT JOIN media m ON m.id = s.icon_media_id
                {$where}
                ORDER BY s.sort_order ASC, s.id ASC";
        return db()->query($sql)->fetchAll();
    }


    public static function getService(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT s.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
             FROM services s
             LEFT JOIN media m ON m.id = s.icon_media_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public static function technologies(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE t.is_active = 1';
        $sql = "SELECT t.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
                FROM technologies t
                LEFT JOIN media m ON m.id = t.icon_media_id
                {$where}
                ORDER BY t.category ASC, t.sort_order ASC, t.id ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function process(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE p.is_active = 1';
        $sql = "SELECT p.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
                FROM process_steps p
                LEFT JOIN media m ON m.id = p.icon_media_id
                {$where}
                ORDER BY p.sort_order ASC, p.id ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function contacts(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE c.is_active = 1';
        $sql = "SELECT c.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
                FROM contact_channels c
                LEFT JOIN media m ON m.id = c.icon_media_id
                {$where}
                ORDER BY c.sort_order ASC, c.id ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function socials(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE s.is_active = 1';
        $sql = "SELECT s.*, m.id AS icon_media_id, m.mime_type AS icon_mime_type
                FROM social_links s
                LEFT JOIN media m ON m.id = s.icon_media_id
                {$where}
                ORDER BY s.sort_order ASC, s.id ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function footerLinks(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE is_active = 1';
        $sql = "SELECT * FROM footer_links {$where}
                ORDER BY group_title ASC, sort_order ASC, id ASC";
        return db()->query($sql)->fetchAll();
    }
}
