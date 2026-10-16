<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Admin
{
    private const MAX_MEDIA_BYTES = 15728640; // margem de segurança para MEDIUMBLOB

    public static function userByEmail(string $email): array
    {
        $stmt = db()->prepare(
            'SELECT id, name, email, password, status
             FROM admin_users
             WHERE email = :email AND status = 1
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: [];
    }

    public static function counts(): array
    {
        $pdo = db();

        return [
            'projects'  => (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
            'published' => (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn(),
            'views'     => (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM projects')->fetchColumn(),
            'ratings'   => (int) $pdo->query('SELECT COUNT(*) FROM project_ratings')->fetchColumn(),
            'messages'  => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE status = \'new\'')->fetchColumn(),
        ];
    }

    /** Lista as mensagens recentes enviadas pelo formulário público de contacto. */
    public static function messages(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        return db()->query(
            "SELECT id, name, email, phone, message, status, created_at
             FROM contact_messages
             ORDER BY FIELD(status, 'new', 'read', 'replied', 'archived'), created_at DESC
             LIMIT " . $limit
        )->fetchAll();
    }

    /** Lista as avaliações para consulta exclusiva do painel administrativo. */
    public static function ratings(int $limit = 500): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = db()->query(
            "SELECT r.id, r.project_id, r.email, r.rating, r.created_at,
                    p.title AS project_title, p.slug AS project_slug
             FROM project_ratings r
             LEFT JOIN projects p ON p.id = r.project_id
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT " . $limit
        );
        return $stmt->fetchAll();
    }

    /**
     * Guarda uma imagem na tabela media e devolve o novo ID.
     */
    public static function storeMedia(
        array $file,
        string $altText = '',
        int $maxBytes = self::MAX_MEDIA_BYTES,
        int $maxDimension = 6000,
        int $maxPixels = 25000000
    ): int {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'A imagem excede o limite de upload configurado no servidor. Reduza o tamanho ou peça para aumentar upload_max_filesize no PHP.',
                UPLOAD_ERR_FORM_SIZE => 'A imagem excede o tamanho máximo permitido neste formulário.',
                UPLOAD_ERR_PARTIAL => 'A imagem foi enviada apenas parcialmente. Tente novamente.',
                UPLOAD_ERR_NO_FILE => 'Seleccione uma imagem antes de guardar.',
                UPLOAD_ERR_NO_TMP_DIR => 'O servidor não tem uma pasta temporária para uploads configurada.',
                UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o ficheiro enviado.',
                UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP bloqueou o upload da imagem.',
            ];
            throw new RuntimeException($messages[$error] ?? 'Falha no upload da imagem.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Arquivo de upload inválido.');
        }

        $actualSize = @filesize($tmpName);
        $size = $actualSize !== false ? (int) $actualSize : (int) ($file['size'] ?? 0);
        $maxBytes = max(1, min($maxBytes, self::MAX_MEDIA_BYTES));
        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = number_format($maxBytes / 1048576, 1, ',', '.');
            throw new RuntimeException('A imagem deve ter entre 1 byte e ' . $maxMb . ' MB. Escolha uma imagem menor.');
        }

        $mime = self::detectMime($tmpName);
        $allowed = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];

        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Formato de imagem não permitido. Use JPG, PNG, GIF, WEBP ou SVG.');
        }

        $data = file_get_contents($tmpName);
        if ($data === false || $data === '') {
            throw new RuntimeException('Não foi possível ler a imagem enviada.');
        }

        $width = null;
        $height = null;
        $imageInfo = @getimagesize($tmpName);
        if ($mime !== 'image/svg+xml' && $imageInfo === false) {
            throw new RuntimeException('O ficheiro seleccionado não parece ser uma imagem válida ou está danificado.');
        }
        if ($imageInfo !== false) {
            $width = isset($imageInfo[0]) ? (int) $imageInfo[0] : null;
            $height = isset($imageInfo[1]) ? (int) $imageInfo[1] : null;
            if ($width > $maxDimension || $height > $maxDimension || ($width * $height) > $maxPixels) {
                throw new RuntimeException(
                    'As dimensões da imagem são demasiado grandes. Use uma imagem até ' . $maxDimension . ' × ' . $maxDimension . ' px.'
                );
            }
        }

        $filename = trim(basename((string) ($file['name'] ?? 'imagem')));
        if ($filename === '') {
            $filename = 'imagem';
        }
        $filename = self::truncateUtf8($filename, 255);
        $altText = self::truncateUtf8(trim($altText), 255);

        $stmt = db()->prepare(
            'INSERT INTO media
                (filename, mime_type, file_size, width, height, alt_text, data)
             VALUES
                (:filename, :mime_type, :file_size, :width, :height, :alt_text, :data)'
        );

        $stmt->bindValue(':filename', $filename);
        $stmt->bindValue(':mime_type', $mime);
        $stmt->bindValue(':file_size', $size, PDO::PARAM_INT);
        if ($width === null) {
            $stmt->bindValue(':width', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':width', $width, PDO::PARAM_INT);
        }
        if ($height === null) {
            $stmt->bindValue(':height', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':height', $height, PDO::PARAM_INT);
        }
        $stmt->bindValue(':alt_text', trim($altText));
        $stmt->bindValue(':data', $data, PDO::PARAM_LOB);
        $stmt->execute();

        return (int) db()->lastInsertId();
    }

    /**
     * Remove uma mídia antiga apenas se ela não estiver mais referenciada.
     */
    public static function deleteMediaIfUnused(int $mediaId): void
    {
        if ($mediaId < 1) {
            return;
        }

        $pdo = db();
        $checks = [
            ['table' => 'hero', 'column' => 'media_id'],
            ['table' => 'about', 'column' => 'image_id'],
            ['table' => 'services', 'column' => 'icon_media_id'],
            ['table' => 'technologies', 'column' => 'icon_media_id'],
            ['table' => 'process_steps', 'column' => 'icon_media_id'],
            ['table' => 'contact_channels', 'column' => 'icon_media_id'],
            ['table' => 'social_links', 'column' => 'icon_media_id'],
            ['table' => 'project_images', 'column' => 'media_id'],
        ];

        foreach ($checks as $check) {
            $sql = sprintf(
                'SELECT 1 FROM %s WHERE %s = :media_id LIMIT 1',
                $check['table'],
                $check['column']
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['media_id' => $mediaId]);
            if ($stmt->fetchColumn()) {
                return;
            }
        }

        $stmt = $pdo->prepare('DELETE FROM media WHERE id = :id');
        $stmt->execute(['id' => $mediaId]);
    }

    private static function truncateUtf8(string $value, int $maxBytes): string
    {
        if (function_exists('mb_strcut')) {
            return mb_strcut($value, 0, $maxBytes, 'UTF-8');
        }
        $value = substr($value, 0, $maxBytes);
        while ($value !== '' && preg_match('//u', $value) !== 1) {
            $value = substr($value, 0, -1);
        }
        return $value;
    }

    private static function detectMime(string $file): string
    {
        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $file);
                finfo_close($finfo);
                $mime = is_string($detected) ? $detected : '';
            }
        }

        if ($mime === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($file);
            $mime = is_string($detected) ? $detected : '';
        }

        // SVG pode ser identificado como text/xml por algumas instalações.
        if ($mime !== 'image/svg+xml') {
            $head = @file_get_contents($file, false, null, 0, 4096);
            if (is_string($head) && stripos($head, '<svg') !== false) {
                return 'image/svg+xml';
            }
        }

        return $mime;
    }
}
