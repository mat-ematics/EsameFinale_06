<?php

namespace App\Services\File;

use App\Models\File\Upload;
use App\Models\User\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Receives a big file in sequential chunks into a temporary file, until it is complete */
class ChunkedUploadService
{
    private function disk()
    {
        return Storage::disk('local');          // private: never reachable from the web
    }

    private function relative(Upload $upload): string
    {
        return "uploads/{$upload->id}.part";
    }

    public function path(Upload $upload): string
    {
        return $this->disk()->path($this->relative($upload));
    }

    public function start(User $user, string $filename, int $size): Upload
    {
        if (random_int(1, 20) === 1) $this->prune();             // housekeeping that works without a cron job

        $this->disk()->makeDirectory('uploads');

        $free = @disk_free_space($this->disk()->path('uploads'));
        abort_if($free !== false && $free < $size + 100 * 1024 * 1024, 422, 'Not enough free space on the server');

        $upload = Upload::create(['user_id' => $user->id, 'filename' => $filename, 'size' => $size]);
        $this->disk()->put($this->relative($upload), '');

        return $upload;
    }

    /** Bytes stored so far: the file itself is the source of truth */
    public function received(Upload $upload): int
    {
        $path = $this->path($upload);
        clearstatcache(true, $path);

        return is_file($path) ? (int) filesize($path) : 0;
    }

    /** @return array{ok: bool, received: int, complete: bool} */
    public function append(Upload $upload, int $offset, string $chunk): array
    {
        // The first bytes of an MP4 contain "ftyp": refuse anything else right away, not after gigabytes
        if ($offset === 0 && strlen($chunk) >= 12 && substr($chunk, 4, 4) !== 'ftyp') {
            $this->discard($upload);
            abort(422, 'The file is not a valid MP4 video');
        }

        $handle = fopen($this->path($upload), 'cb');             // create if missing, never truncate
        if ($handle === false) throw new RuntimeException('Could not open the upload file');

        try {
            flock($handle, LOCK_EX);                              // two requests can never write at once
            fseek($handle, 0, SEEK_END);
            $current = (int) ftell($handle);

            if ($current !== $offset) {                           // out of sync: tell the client where we are
                return ['ok' => false, 'received' => $current, 'complete' => false];
            }

            abort_if($current + strlen($chunk) > $upload->size, 422, 'The chunk exceeds the declared size');

            if (fwrite($handle, $chunk) !== strlen($chunk)) throw new RuntimeException('Disk write failed');
            fflush($handle);
            $received = $current + strlen($chunk);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $complete = $received === $upload->size;
        $complete ? $upload->update(['status' => 'complete']) : $upload->touch();

        return ['ok' => true, 'received' => $received, 'complete' => $complete];
    }

    public function discard(Upload $upload): void
    {
        $this->disk()->delete($this->relative($upload));
        $upload->delete();
    }

    /** Removes uploads nobody finished or used */
    public function prune(): int
    {
        $old = Upload::where('updated_at', '<', now()->subHours(config('uploads.ttl_hours')))->get();
        $old->each(fn (Upload $upload) => $this->discard($upload));

        return $old->count();
    }
}