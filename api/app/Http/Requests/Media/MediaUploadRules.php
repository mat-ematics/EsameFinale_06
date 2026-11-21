<?php

namespace App\Http\Requests\Media;

/** Limits are in KB. PHP's own upload_max_filesize / post_max_size must be at least this large */
trait MediaUploadRules
{
    protected function imageRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    }

    protected function videoRules(): array
    {
        return ['file', 'mimetypes:video/mp4', 'max:51200'];
    }

    protected function maxYear(): int
    {
        return (int) date('Y') + 1;
    }
}