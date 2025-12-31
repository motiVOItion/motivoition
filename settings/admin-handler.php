<?php
/**
 * MotiVOItion Admin Settings Handler
 * Handles saving of site configuration to JSON and image uploads.
 * Designed to prevent data loss by merging sections.
 */

header("Content-Type: application/json");

// Define paths
$configFile = 'site-data.json';
$targetDir = '../assets/images/';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Load current data to merge
    $currentJson = file_exists($configFile) ? file_get_contents($configFile) : '{}';
    $data = json_decode($currentJson, true);
    if (!$data)
        $data = [];

    // Check if we have JSON or multipart
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $inputJson = file_get_contents('php://input');
        $newData = json_decode($inputJson, true);
        if ($newData) {
            $data = array_replace_recursive($data, $newData);
        }
    } else {
        // Handle multipart/form-data (FormData from JS)
        // PHP automatically parses nested brackets into arrays in $_POST
        if (!empty($_POST)) {
            // First, merge simple fields
            foreach ($_POST as $key => $value) {
                if ($key !== 'services') {
                    $data[$key] = array_replace_recursive($data[$key] ?? [], $value);
                }
            }

            // Custom parsing for services to properly handle nested arrays
            if (isset($_POST['services'])) {
                $services = $data['services'] ?? [];

                // Parse services list
                if (isset($_POST['services']['list'])) {
                    $services['list'] = [];
                    foreach ($_POST['services']['list'] as $index => $service) {
                        $services['list'][] = $service;
                    }
                }

                // Parse packages
                if (isset($_POST['services']['packages'])) {
                    $services['packages'] = [];
                    foreach ($_POST['services']['packages'] as $index => $package) {
                        // Handle features array properly
                        if (isset($package['features']) && is_array($package['features'])) {
                            $package['features'] = array_values(array_filter($package['features']));
                        }
                        $services['packages'][] = $package;
                    }
                }

                $data['services'] = $services;
            }
        }

        // Handle File Upload
        if (isset($_FILES['about_photo']) && $_FILES['about_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['about_photo'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'profile.' . $ext;
            $targetPath = $targetDir . $fileName;

            if (!is_dir($targetDir))
                mkdir($targetDir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $data['about']['photo_url'] = 'assets/images/' . $fileName;
            }
        }
    }

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No data to save']);
        exit;
    }

    // Save to file
    if (file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write configuration file.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>