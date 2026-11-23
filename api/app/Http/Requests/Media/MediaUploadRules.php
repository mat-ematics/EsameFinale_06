<?php

namespace App\Http\Requests\Media;

use Illuminate\Validation\Rule;

/** Limits are in KB. PHP's own upload_max_filesize / post_max_size must be at least this large */
trait MediaUploadRules
{
    protected function imageRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    }

    /** The video is uploaded first (chunked): the form only references the finished upload */
    protected function uploadRule()
    {
        return Rule::exists('uploads', 'id')
            ->where(fn ($query) => $query->where('user_id', $this->user()->id)->where('status', 'complete'));
    }

    public function messages(): array
    {
        return ['video_upload_id.exists' => 'The video upload is not finished or has expired. Upload it again.'];
    }

    protected function maxYear(): int
    {
        return (int) date('Y') + 1;
    }
}