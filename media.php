<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit;
}

try {
    $stmt = db()->prepare(
        'SELECT mime_type, file_size, data
         FROM media
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $media = $stmt->fetch();

    if (!$media || $media['data'] === null) {
        http_response_code(404);
        exit;
    }

    $mime = (string) $media['mime_type'];
    $size = (int) $media['file_size'];
    $data = $media['data'];

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Cache-Control: public, max-age=86400, immutable');
    header('X-Content-Type-Options: nosniff');
    echo $data;
} catch (Throwable $e) {
    error_log('JosPortifolio media error: ' . $e->getMessage());
    http_response_code(404);
}
