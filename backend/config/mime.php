<?php

function detect_mime_type($filepath, $fallback = 'application/octet-stream') {
    if (!is_string($filepath) || $filepath === '' || !file_exists($filepath)) {
        return $fallback;
    }

    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = @finfo_file($finfo, $filepath);
            @finfo_close($finfo);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
    }

    if (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($filepath);
        if (is_array($imageInfo) && !empty($imageInfo['mime'])) {
            return $imageInfo['mime'];
        }
    }

    $extension = strtolower((string)pathinfo($filepath, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf'
    ];

    return $map[$extension] ?? $fallback;
}
