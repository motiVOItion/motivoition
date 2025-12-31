<?php
/**
 * MotiVOItion Video Upload Handler
 * Handles file uploads from the portfolio website
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// CORS headers (adjust domains as needed)
header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
class UploadConfig
{
    const MAX_FILE_SIZE = 200 * 1024 * 1024; // 200MB
    const ALLOWED_TYPES = [
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/x-m4v' => 'm4v'
    ];
    const UPLOAD_DIR = '../assets/videos/';
    const THUMBNAIL_DIR = '../assets/videos/thumbnails/';
    const LOG_FILE = '../logs/upload.log';
    const JSON_FILE = 'videos.json'; // Path relative to this script

    // GitHub API Configuration
    const GITHUB_TOKEN = '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';
    const GITHUB_REPO = 'motiVOItion/motivoition';
    const GITHUB_BRANCH = 'main';
}

// Response class
class Response
{
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    public static function error($message, $status = 400)
    {
        self::json([
            'success' => false,
            'error' => $message
        ], $status);
    }

    public static function success($data = [])
    {
        self::json(array_merge(['success' => true], $data));
    }
}

// Logging class
class Logger
{
    public static function log($message, $type = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

        // Create log directory if it doesn't exist
        $logDir = dirname(UploadConfig::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(UploadConfig::LOG_FILE, $logMessage, FILE_APPEND);
    }
}

// File validation class
class FileValidator
{
    public static function validate($file)
    {
        // Check if file was uploaded
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'No file uploaded';
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload'
            ];
            return $errors[$file['error']] ?? 'Unknown upload error';
        }

        // Check file size
        if ($file['size'] > UploadConfig::MAX_FILE_SIZE) {
            return 'File size must be less than ' .
                (UploadConfig::MAX_FILE_SIZE / (1024 * 1024)) . 'MB';
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($mimeType, UploadConfig::ALLOWED_TYPES)) {
            return 'Invalid file type. Allowed types: ' .
                implode(', ', array_keys(UploadConfig::ALLOWED_TYPES));
        }

        // Check file extension
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExtensions = array_values(UploadConfig::ALLOWED_TYPES);

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return 'Invalid file extension. Allowed: ' .
                implode(', ', $allowedExtensions);
        }

        // Check for malicious files
        if (self::isMalicious($file['tmp_name'])) {
            return 'File rejected for security reasons';
        }

        return null; // No errors
    }

    private static function isMalicious($filePath)
    {
        // Simple check for PHP tags
        $content = file_get_contents($filePath, false, null, 0, 100);
        return strpos($content, '<?php') !== false ||
            strpos($content, '<script') !== false;
    }
}

// Video processor class
class VideoProcessor
{
    public static function generateThumbnail($videoPath, $thumbnailPath)
    {
        // FFmpeg is not available, so we cannot automatically generate thumbnails.
        // The main handler should check for a manually uploaded thumbnail.
        return false;
    }

    public static function getVideoInfo($videoPath)
    {
        // Without FFmpeg, we can only get file size.
        // Duration and Resolution will be unknown or set by client if possible.
        return [
            'duration' => 0,
            'resolution' => 'Unknown',
            'size' => filesize($videoPath),
            'bitrate' => 0
        ];
    }

    public static function compressVideo($sourcePath, $destinationPath, $level = 'medium')
    {
        // Check if FFmpeg is available
        $ffmpegPath = self::getFFmpegPath();
        if (!$ffmpegPath) {
            Logger::log("FFmpeg not found. Skipping compression.");
            return copy($sourcePath, $destinationPath);
        }

        // Define compression settings based on level
        $crf = 23; // Default (Medium)
        $preset = 'medium';

        switch ($level) {
            case 'high':
                $crf = 18; // visually lossless
                $preset = 'slow';
                break;
            case 'low':
                $crf = 28; // lower quality, smaller size
                $preset = 'faster';
                break;
            case 'original':
                return copy($sourcePath, $destinationPath);
            case 'medium':
            default:
                $crf = 23;
                $preset = 'medium';
                break;
        }

        // Build command
        // -vcodec libx264: Use H.264 codec
        // -crf: Constant Rate Factor (lower is better quality)
        // -preset: Compression speed vs ratio
        // -acodec aac: Audio codec
        // -movflags +faststart: Optimize for web streaming
        // -y: Overwrite output file
        $command = "\"$ffmpegPath\" -i \"$sourcePath\" -vcodec libx264 -crf $crf -preset $preset -acodec aac -movflags +faststart -y \"$destinationPath\" 2>&1";

        Logger::log("Starting compression: Level=$level, Command=$command");

        // Execute command
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($destinationPath) && filesize($destinationPath) > 0) {
            Logger::log("Compression successful. New size: " . filesize($destinationPath));
            return true;
        } else {
            Logger::log("Compression failed. Return code: $returnCode. Output: " . implode("\n", $output));
            // Fallback to original
            return copy($sourcePath, $destinationPath);
        }
    }

    private static function getFFmpegPath()
    {
        // Attempt to find FFmpeg in common locations or PATH
        // Windows
        $commonPaths = [
            'ffmpeg', // PATH
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
            'D:\\ffmpeg\\bin\\ffmpeg.exe',
            'i:\\ffmpeg\\bin\\ffmpeg.exe'
        ];

        foreach ($commonPaths as $path) {
            $cmd = "\"$path\" -version 2>&1";
            exec($cmd, $o, $r);
            if ($r === 0) {
                return $path;
            }
        }
        return false;
    }
}

// GitHub integration class
class GitHubUploader
{
    public static function uploadToGitHub($filePath, $filename)
    {
        if (!UploadConfig::GITHUB_TOKEN) {
            return false;
        }

        try {
            $apiUrl = 'https://api.github.com/repos/' . UploadConfig::GITHUB_REPO .
                '/contents/assets/uploads/' . $filename;

            $content = base64_encode(file_get_contents($filePath));

            $data = [
                'message' => 'Upload video: ' . $filename,
                'content' => $content,
                'branch' => UploadConfig::GITHUB_BRANCH
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => [
                    'Authorization: token ' . UploadConfig::GITHUB_TOKEN,
                    'User-Agent: MotiVOItion-Uploader',
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($data)
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status === 201) {
                Logger::log("File uploaded to GitHub: $filename", 'INFO');
                return true;
            } else {
                Logger::log("GitHub upload failed: $response", 'ERROR');
                return false;
            }
        } catch (Exception $e) {
            Logger::log("GitHub error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}

// Database class (using SQLite for simplicity)
class Database
{
    private $pdo;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/database/videos.db';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initDatabase();
    }

    private function initDatabase()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS videos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                original_name TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                category TEXT,
                tags TEXT,
                file_size INTEGER,
                duration INTEGER,
                resolution TEXT,
                upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                upload_ip TEXT,
                thumbnail_path TEXT,
                github_url TEXT,
                is_featured BOOLEAN DEFAULT 0,
                views INTEGER DEFAULT 0
            );
            
            CREATE TABLE IF NOT EXISTS upload_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                video_id INTEGER,
                action TEXT,
                details TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (video_id) REFERENCES videos(id)
            );
        ";

        $this->pdo->exec($sql);
    }

    public function saveVideo($data)
    {
        $sql = "
            INSERT INTO videos (
                filename, original_name, title, description, category, tags,
                file_size, duration, resolution, upload_ip, thumbnail_path, github_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['filename'],
            $data['original_name'],
            $data['title'],
            $data['description'],
            $data['category'],
            $data['tags'],
            $data['file_size'],
            $data['duration'],
            $data['resolution'],
            $data['upload_ip'],
            $data['thumbnail_path'],
            $data['github_url']
        ]);

        return $this->pdo->lastInsertId();
    }

    public function logAction($videoId, $action, $details = '')
    {
        $sql = "INSERT INTO upload_logs (video_id, action, details) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$videoId, $action, $details]);
    }

    public function getAllVideos()
    {
        $sql = "SELECT * FROM videos ORDER BY upload_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Main upload handler
class UploadHandler
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function handleUpload()
    {
        // Check if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if (!$isAjax && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Invalid request method');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            Response::error('Invalid CSRF token');
        }

        // Check if file was uploaded
        if (!isset($_FILES['video'])) {
            Response::error('No video file provided');
        }

        $file = $_FILES['video'];

        // Validate file
        $validationError = FileValidator::validate($file);
        if ($validationError) {
            Response::error($validationError);
        }

        // Process form data
        $formData = $this->getFormData();

        // Generate unique filename
        $originalName = basename($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = $this->generateFilename($extension);

        // Create upload directories
        $this->createDirectories();

        // Move uploaded file
        $uploadPath = UploadConfig::UPLOAD_DIR . $filename;

        // Process video (Compress or Move)
        $compressionLevel = $_POST['compression_level'] ?? 'medium';
        $compressed = VideoProcessor::compressVideo($file['tmp_name'], $uploadPath, $compressionLevel);

        if (!$compressed) {
            // Fallback to standard move if compression/copy failed
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Response::error('Failed to move uploaded file');
            }
        }

        // Handle Thumbnail (Manual Upload or Default)
        $thumbnailPath = '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $thumbOriginalName = basename($_FILES['thumbnail']['name']);
            $thumbExt = pathinfo($thumbOriginalName, PATHINFO_EXTENSION);
            $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $thumbExt;
            $targetThumbPath = UploadConfig::THUMBNAIL_DIR . $thumbFilename;

            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetThumbPath)) {
                $thumbnailPath = $targetThumbPath;
            }
        }

        // If no thumbnail uploaded, use a placeholder or empty string
        // The frontend logic handles missing thumbnails with a default image.

        // Get video information (Basic info only w/o FFmpeg)
        $videoInfo = VideoProcessor::getVideoInfo($uploadPath);

        // Upload to GitHub (optional)
        $githubUrl = null;
        if (!empty($formData['github_integration']) && $formData['github_integration'] === 'true') {
            if (GitHubUploader::uploadToGitHub($uploadPath, $filename)) {
                $githubUrl = 'https://github.com/' . UploadConfig::GITHUB_REPO .
                    '/blob/' . UploadConfig::GITHUB_BRANCH .
                    '/assets/uploads/' . $filename;
            }
        }

        // Save to database
        $videoId = $this->saveVideoData([
            'filename' => $filename,
            'original_name' => $originalName,
            'title' => $formData['title'],
            'description' => $formData['description'],
            'category' => $formData['category'],
            'tags' => $formData['tags'],
            'file_size' => $file['size'],
            'duration' => $videoInfo['duration'],
            'resolution' => $videoInfo['resolution'],
            'upload_ip' => $_SERVER['REMOTE_ADDR'],
            'thumbnail_path' => $thumbnailPath ? 'assets/videos/thumbnails/' . basename($thumbnailPath) : '',
            'github_url' => $githubUrl
        ]);

        // Sync to JSON for frontend
        $this->syncToJson();

        // Log the upload
        $this->db->logAction($videoId, 'upload', 'Video uploaded successfully');

        Logger::log("Video uploaded: $originalName (ID: $videoId)", 'INFO');

        // Return success response
        Response::success([
            'message' => 'Video uploaded successfully',
            'video_id' => $videoId,
            'filename' => $filename,
            'thumbnail' => basename($thumbnailPath),
            'video_info' => $videoInfo,
            'preview_url' => 'assets/videos/' . $filename,
            'github_url' => $githubUrl
        ]);
    }

    private function syncToJson()
    {
        try {
            // Fetch all videos from DB
            $videos = $this->db->getAllVideos();

            // Transform for JSON format if needed (matching frontend expectations)
            $jsonVideos = array_map(function ($v) {
                return [
                    'id' => $v['id'],
                    'title' => $v['title'],
                    'description' => $v['description'],
                    'category' => $v['category'],
                    'tags' => $v['tags'] ? explode(',', $v['tags']) : [],
                    'date' => date('Y-m-d', strtotime($v['upload_date'])),
                    'src' => 'assets/videos/' . $v['filename'],
                    'thumbnail' => $v['thumbnail_path'],
                    'size' => $this->formatBytes($v['file_size']),
                    'duration' => $v['duration'] ? gmdate("H:i:s", $v['duration']) : ''
                ];
            }, $videos);

            file_put_contents(UploadConfig::JSON_FILE, json_encode($jsonVideos, JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            Logger::log("JSON Sync failed: " . $e->getMessage(), 'ERROR');
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function validateCsrfToken()
    {
        if (!isset($_POST['csrf_token'])) {
            return false;
        }

        session_start();
        $token = $_POST['csrf_token'];

        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }

        // Regenerate token for security
        unset($_SESSION['csrf_token']);
        return true;
    }

    private function getFormData()
    {
        $required = ['title', 'description', 'category'];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                Response::error("Field '$field' is required");
            }
        }

        return [
            'title' => htmlspecialchars($_POST['title']),
            'description' => htmlspecialchars($_POST['description']),
            'category' => htmlspecialchars($_POST['category']),
            'tags' => isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : '',
            'github_integration' => $_POST['github_integration'] ?? 'false'
        ];
    }

    private function generateFilename($extension)
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "video_{$timestamp}_{$random}.{$extension}";
    }

    private function createDirectories()
    {
        $dirs = [
            UploadConfig::UPLOAD_DIR,
            UploadConfig::THUMBNAIL_DIR,
            dirname(UploadConfig::LOG_FILE)
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);

                // Add .htaccess for security
                if (strpos($dir, 'uploads') !== false) {
                    $htaccess = $dir . '.htaccess';
                    if (!file_exists($htaccess)) {
                        file_put_contents(
                            $htaccess,
                            "Order Deny,Allow\nDeny from all\n" .
                            "<FilesMatch '\.(mp4|mov|m4v|jpg|jpeg|png)$'>\n" .
                            "    Allow from all\n" .
                            "</FilesMatch>"
                        );
                    }
                }
            }
        }
    }

    private function saveVideoData($data)
    {
        return $this->db->saveVideo($data);
    }
}

// Generate CSRF token for form
function generateCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;

    return $token;
}

// Handle the request
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['csrf_token'])) {
        // Return CSRF token for form
        Response::success([
            'csrf_token' => generateCsrfToken()
        ]);
    } else {
        $handler = new UploadHandler();
        $handler->handleUpload();
    }
} catch (Exception $e) {
    Logger::log("Upload handler error: " . $e->getMessage(), 'ERROR');
    Response::error('An internal server error occurred');
}
?>