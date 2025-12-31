<?php
header("Content-Type: application/json");

$blogFile = 'blogs.json';
$blogImageDir = '../assets/images/blog/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $blogs = file_exists($blogFile) ? json_decode(file_get_contents($blogFile), true) : [];
    if (!$blogs)
        $blogs = [];

    if ($action === 'save') {
        $blogData = $_POST['blog'] ?? null;
        if (!$blogData) {
            echo json_encode(['success' => false, 'error' => 'No blog data']);
            exit;
        }

        $id = $blogData['id'] ?? null;
        if (!$id) {
            // New blog - generate ID from title
            $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $blogData['title'])));
            $blogData['id'] = $id;
        }

        // Handle Image Upload
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($blogImageDir))
                mkdir($blogImageDir, 0755, true);

            $file = $_FILES['blog_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $id . '.' . $ext;
            $targetPath = $blogImageDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Resize image to keep it portable (max 800x600)
                createThumbnail($targetPath, $targetPath, 800, 600);
                $blogData['image_url'] = 'assets/images/blog/' . $fileName;
            }
        }

        // Add or Update
        $found = false;
        foreach ($blogs as &$b) {
            if ($b['id'] === $id) {
                $b = array_merge($b, $blogData);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $blogs[] = $blogData;
        }

        file_put_contents($blogFile, json_encode($blogs, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'id' => $id]);

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $blogs = array_filter($blogs, function ($b) use ($id) {
            return $b['id'] !== $id;
        });
        file_put_contents($blogFile, json_encode(array_values($blogs), JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $blogs = file_exists($blogFile) ? json_decode(file_get_contents($blogFile), true) : [];
    echo json_encode($blogs);
}

/**
 * Resize image to be more portable
 */
function createThumbnail($source, $destination, $maxWidth, $maxHeight)
{
    $imageInfo = getimagesize($source);
    if (!$imageInfo)
        return false;

    list($width, $height, $type) = $imageInfo;

    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio >= 1.0)
        return true; // Don't upscale

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

    // Preserve transparency
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }

    // Resize
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dstImage, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($dstImage, $destination, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dstImage, $destination, 85);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return true;
}
?>