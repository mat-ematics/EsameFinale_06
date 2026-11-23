<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/stream/{path}', function (string $path) {
    $root = realpath(Storage::disk('media')->path(''));
    $file = realpath(Storage::disk('media')->path($path));

    // Only real files inside the media folder: never ../ tricks
    abort_unless($root && $file && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR), 404);

    return response()->file($file);
})->where('path', '.*');