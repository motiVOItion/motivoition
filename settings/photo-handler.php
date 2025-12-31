<?php
/**
 * MotiVOItion Photo Portfolio Handler
 * Handles photo portfolio CRUD operations and file uploads
 */

header("Content-Type: application/json");

// Define paths
$photosFile = 'photos.json';
$photoDir = '../assets/images/portfolio/';
$thumbDir = '../assets/images/portfolio/thumbs/';

// Ensure directories exist
if (!is_dir($photoDir))
    mkdir($photoDir, 0755, true);
if (!is_dir($thumbDir))
    mkdir($thumbDir, 0755, true);

// Handle GET request - Return all photos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($photosFile)) {
        $photos = json_decode(file_get_contents($photosFile), true);
        echo json_encode($photos ?: []);
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

    // Load existing photos
    $photos = file_exists($photosFile) ? json_decode(file_get_contents($photosFile), true) : [];
    if (!$photos)
        $photos = [];

    if ($action === 'save') {
        $photoData = $_POST['photo'] ?? null;
        if (!$photoData) {
            echo json_encode(['success' => false, 'error' => 'No photo data']);
            exit;
        }

        $id = $photoData['id'] ?? null;
        if (!$id) {
            // New photo - generate ID from title
            $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $photoData['title']))) . '-' . time();
            $photoData['id'] = $id;
        }

        // Handle photo file upload
        if (isset($_FILES['photo_file'])) {
            if ($_FILES['photo_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                $errorCode = $_FILES['photo_file']['error'];
                // If it's a new item and no file, strictly error out.
                if (!$id && $errorCode == UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'error' => 'No file uploaded for new item.']);
                    exit;
                }
                // If it's an existing item and no file, it's fine (skips upload).
                // But for other errors, report them.
                if ($errorCode != UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'error' => $errorMessages[$errorCode] ?? 'Unknown upload error']);
                    exit;
                }
            } elseif ($_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowedTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP allowed.']);
                    exit;
                }

                // Validate file size (5MB max)
                $maxSize = 5 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'error' => 'File too large. Maximum 5MB.']);
                    exit;
                }

                $fileName = $id . '.' . $ext;
                $targetPath = $photoDir . $fileName;

                // Delete old photo file if exists
                if (isset($photoData['src'])) {
                    $oldFile = '../' . $photoData['src'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $photoData['src'] = 'assets/images/portfolio/' . $fileName;

                    // Get image dimensions
                    $imageInfo = getimagesize($targetPath);
                    if ($imageInfo) {
                        $photoData['dimensions'] = $imageInfo[0] . 'x' . $imageInfo[1];
                    }

                    // Create thumbnail
                    createThumbnail($targetPath, $thumbDir . $fileName, 400, 300);
                    $photoData['thumbnail'] = 'assets/images/portfolio/thumbs/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to upload photo file.']);
                    exit;
                }
            }
        }

        // Parse tags if string
        if (isset($photoData['tags']) && is_string($photoData['tags'])) {
            $photoData['tags'] = array_map('trim', explode(',', $photoData['tags']));
        }

        // Add or update photo
        $found = false;
        foreach ($photos as &$p) {
            if ($p['id'] === $id) {
                $p = array_merge($p, $photoData);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $photos[] = $photoData;
        }

        file_put_contents($photosFile, json_encode($photos, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'id' => $id]);

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        // Find and delete photo files
        foreach ($photos as $photo) {
            if ($photo['id'] === $id) {
                // Delete photo file
                if (isset($photo['src'])) {
                    $photoFile = '../' . $photo['src'];
                    if (file_exists($photoFile)) {
                        unlink($photoFile);
                    }
                }
                // Delete thumbnail
                if (isset($photo['thumbnail'])) {
                    $thumbFile = '../' . $photo['thumbnail'];
                    if (file_exists($thumbFile)) {
                        unlink($thumbFile);
                    }
                }
                break;
            }
        }

        // Remove from array
        $photos = array_filter($photos, function ($p) use ($id) {
            return $p['id'] !== $id;
        });

        file_put_contents($photosFile, json_encode(array_values($photos), JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

/**
 * Create thumbnail from image
 */
function createThumbnail($source, $destination, $maxWidth, $maxHeight)
{
    $imageInfo = getimagesize($source);
    if (!$imageInfo)
        return false;

    list($width, $height, $type) = $imageInfo;

    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);

    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    // Create new image
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }

    // Resize
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save thumbnail
    $ext = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($dstImage, $destination, 85);
            break;
        case 'png':
            imagepng($dstImage, $destination, 8);
            break;
        case 'webp':
            imagewebp($dstImage, $destination, 85);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return true;
}
?>