<?php
/**
 * MotiVOItion Video Portfolio Handler
 * Handles video portfolio CRUD operations and file uploads
 */

header("Content-Type: application/json");

// Define paths
$videosFile = 'videos.json';
$videoDir = '../assets/videos/';
$thumbDir = '../assets/videos/thumbnails/';

// Ensure directories exist
if (!is_dir($videoDir))
    mkdir($videoDir, 0755, true);
if (!is_dir($thumbDir))
    mkdir($thumbDir, 0755, true);

// Handle GET request - Return all videos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($videosFile)) {
        $videos = json_decode(file_get_contents($videosFile), true);
        echo json_encode($videos ?: []);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for post_max_size overflow
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $maxPost = ini_get('post_max_size');
        echo json_encode(['success' => false, 'error' => "File too large. Exceeds server limit (post_max_size = $maxPost)."]);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Load existing videos
    $videos = file_exists($videosFile) ? json_decode(file_get_contents($videosFile), true) : [];
    if (!$videos)
        $videos = [];

    if ($action === 'save') {
        $videoData = $_POST['video'] ?? null;
        if (!$videoData) {
            echo json_encode(['success' => false, 'error' => 'No video data']);
            exit;
        }

        $id = $videoData['id'] ?? null;
        if (!$id) {
            // New video - generate ID from title
            $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $videoData['title']))) . '-' . time();
            $videoData['id'] = $id;
        }

        // Handle video file upload
        if (isset($_FILES['video_file'])) {
            if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                $errorCode = $_FILES['video_file']['error'];

                // If new video and no file, error
                if (!$id && $errorCode == UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'error' => 'No video file uploaded for new item.']);
                    exit;
                }

                if ($errorCode != UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'error' => $errorMessages[$errorCode] ?? 'Unknown upload error']);
                    exit;
                }
            } elseif ($_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['video_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                // Validate file type
                $allowedTypes = ['mp4', 'mov', 'm4v'];
                if (!in_array($ext, $allowedTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only MP4 and MOV allowed.']);
                    exit;
                }

                // Validate file size (200MB max)
                $maxSize = 200 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'error' => 'File too large. Maximum 200MB.']);
                    exit;
                }

                $fileName = $id . '.' . $ext;
                $targetPath = $videoDir . $fileName;

                // Delete old video file if exists
                if (isset($videoData['src'])) {
                    $oldFile = '../' . $videoData['src'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $videoData['src'] = 'assets/videos/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to upload video file.']);
                    exit;
                }
            }
        }

        // Handle thumbnail upload
        if (isset($_FILES['video_thumbnail']) && $_FILES['video_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['video_thumbnail'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Validate image type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Invalid thumbnail type.']);
                exit;
            }

            $thumbName = $id . '.' . $ext;
            $thumbPath = $thumbDir . $thumbName;

            // Delete old thumbnail if exists
            if (isset($videoData['thumbnail'])) {
                $oldThumb = '../' . $videoData['thumbnail'];
                if (file_exists($oldThumb)) {
                    unlink($oldThumb);
                }
            }

            if (move_uploaded_file($file['tmp_name'], $thumbPath)) {
                $videoData['thumbnail'] = 'assets/videos/thumbnails/' . $thumbName;
            }
        }

        // Parse tags if string
        if (isset($videoData['tags']) && is_string($videoData['tags'])) {
            $videoData['tags'] = array_map('trim', explode(',', $videoData['tags']));
        }

        // Add or update video
        $found = false;
        foreach ($videos as &$v) {
            if ($v['id'] === $id) {
                $v = array_merge($v, $videoData);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $videos[] = $videoData;
        }

        file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'id' => $id]);

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        // Find and delete video files
        foreach ($videos as $video) {
            if ($video['id'] === $id) {
                // Delete video file
                if (isset($video['src'])) {
                    $videoFile = '../' . $video['src'];
                    if (file_exists($videoFile)) {
                        unlink($videoFile);
                    }
                }
                // Delete thumbnail
                if (isset($video['thumbnail'])) {
                    $thumbFile = '../' . $video['thumbnail'];
                    if (file_exists($thumbFile)) {
                        unlink($thumbFile);
                    }
                }
                break;
            }
        }

        // Remove from array
        $videos = array_filter($videos, function ($v) use ($id) {
            return $v['id'] !== $id;
        });

        file_put_contents($videosFile, json_encode(array_values($videos), JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>