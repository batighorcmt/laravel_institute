<?php
$file = 'storage/app/firebase-service-account.json';
if (!file_exists($file)) { echo "File not found"; exit; }
$content = file_get_contents($file);
$json = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg();
    // Dump actual content length to check if something's missing
    echo "\nContent length: " . strlen($content);
} else {
    echo "OK: Project ID is " . ($json['project_id'] ?? 'unknown');
}
