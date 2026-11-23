<?php
return [
    // Keep the chunk below your web server's body limit (nginx's default is only 1 MB)
    'chunk_bytes' => (int) env('UPLOAD_CHUNK_BYTES', 4 * 1024 * 1024),
    'max_bytes'   => (int) env('UPLOAD_MAX_BYTES', 2 * 1024 * 1024 * 1024),
    'ttl_hours'   => (int) env('UPLOAD_TTL_HOURS', 24),
];