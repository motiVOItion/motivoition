<?php
header("Content-Type: application/json");

try {
    $dbPath = dirname(__DIR__) . '/database/videos.db';
    if (!file_exists($dbPath)) {
        echo json_encode([]);
        exit;
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM videos ORDER BY upload_date DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format tags if they are stored as strings
    foreach ($videos as &$video) {
        if (isset($video['tags']) && is_string($video['tags'])) {
            $video['tags'] = array_map('trim', explode(',', $video['tags']));
        } else {
            $video['tags'] = [];
        }

        // Ensure thumbnail path is relative to root
        if (isset($video['thumbnail_path'])) {
            $video['thumbnail_path'] = str_replace('../', '', $video['thumbnail_path']);
        }

        // Ensure path is relative to root
        $video['src'] = 'assets/uploads/' . $video['filename'];
    }

    echo json_encode($videos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>