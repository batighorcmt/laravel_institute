<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/resources/views/principal/biometric');
$it = new RecursiveIteratorIterator($dir);
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $newContent = str_replace("route('principal.biometric.", "route('principal.institute.biometric.", $content);
        if ($content !== $newContent) {
            file_put_contents($path, $newContent);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";
