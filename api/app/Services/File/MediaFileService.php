<?php

namespace App\Services\File;

use App\Enums\FileVisibilityEnum;
use App\Enums\MediaRoleEnum;
use App\Models\File\File;
use App\Models\File\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One folder per title: {films|tv-series|episodes}/{id}/{cover|banner|video}.{ext}
 * `files` keeps the metadata; fileables.role (unique per title) says which file is which.
 */
class MediaFileService
{
    public function __construct(protected ChunkedUploadService $uploads) {}

    private function disk()
    {
        return Storage::disk('media');
    }

    /** films/12, tv-series/3, episodes/40 */
    public function directory(Model $media): string
    {
        return Str::plural(Str::kebab(class_basename($media))) . '/' . $media->getKey();
    }

    public function storeMany(Model $media, array $sources): void
    {
        foreach (MediaRoleEnum::cases() as $role) {
            $source = $sources[$role->value] ?? null;
            if ($source instanceof UploadedFile) $this->store($media, $role, $source);
            elseif ($source instanceof Upload) $this->adopt($media, $role, $source);
        }
    }

    /** Stores a file, replacing the previous one of the same role */
    public function store(Model $media, MediaRoleEnum $role, UploadedFile $upload): File
    {
        $extension = strtolower($upload->guessExtension() ?: $upload->getClientOriginalExtension());
        $directory = $this->directory($media);
        $filename  = "{$role->value}.{$extension}";

        $size = $upload->getSize();
        $mime = $upload->getMimeType();
        $hash = hash_file('sha256', $upload->getRealPath());

        if ($this->disk()->putFileAs($directory, $upload, $filename) === false) {
            throw new RuntimeException("Could not store the {$role->value} file");
        }

        try {
            return $this->register($media, $role, $directory, $filename, $size, $mime, $hash, $extension);
        } catch (\Throwable $e) {
            $this->disk()->delete("{$directory}/{$filename}");
            throw $e;
        }
    }

    /** A finished chunked upload: moves it into the title's folder (a rename, not a copy) */
    public function adopt(Model $media, MediaRoleEnum $role, Upload $upload): File
    {
        abort_unless($upload->status === 'complete', 422, 'The video upload is not finished');

        $source = $this->uploads->path($upload);
        abort_unless(is_file($source), 422, 'The uploaded video is missing: upload it again');

        $extension = strtolower(pathinfo($upload->filename, PATHINFO_EXTENSION)) ?: 'mp4';
        $directory = $this->directory($media);
        $filename  = "{$role->value}.{$extension}";

        $this->disk()->makeDirectory($directory);
        $target = $this->disk()->path("{$directory}/{$filename}");

        if (is_file($target)) @unlink($target);                       // Windows does not overwrite on rename
        if (!@rename($source, $target)) {                             // another filesystem: copy, then remove
            if (!copy($source, $target)) throw new RuntimeException('Could not move the uploaded video');
            @unlink($source);
        }

        $size = (int) filesize($target);
        $hash = hash_file('sha256', $target);                         // about 3 seconds per GB

        try {
            $file = $this->register($media, $role, $directory, $filename, $size, 'video/mp4', $hash, $extension);
        } catch (\Throwable $e) {
            @rename($target, $source);                                // put it back: the admin does not have to upload again
            throw $e;
        }

        $upload->delete();                                            // the temp file is gone: only the row is left
        return $file;
    }

    /** The database part, shared by both ways of storing a file */
    private function register(
        Model $media, MediaRoleEnum $role, string $directory, string $filename,
        int $size, string $mime, string $hash, string $extension,
    ): File {
        return DB::transaction(function () use ($media, $role, $directory, $filename, $size, $mime, $hash, $extension) {

            // $this->remove($media, $role, keepFilename: $filename);

            $file = File::create([
                'filename'   => $filename,
                'label'      => $role->value,
                'visibility' => FileVisibilityEnum::default()->value,
                'path'       => $directory,
                'size'       => $size,
                'mime_type'  => $mime,
                'extension'  => $extension,
                'hash'       => $hash,
            ]);

            DB::table('fileables')->upsert(
                [
                    'file_id'       => $file->id,
                    'fileable_type' => $media->getMorphClass(),
                    'fileable_id'   => $media->getKey(),
                    'role'          => $role->value,
                ],
                ['fileable_type', 'fileable_id', 'role'], // conflict target = the unique key
                ['file_id'],                              // on conflict: repoint to the new file
            );

            // Clean up the old file (if the pivot previously pointed elsewhere)
            $this->removeOld($media, $role, $file->id, $filename);

            // $media->files()->attach($file->id, ['role' => $role->value]);
            return $file;
        });
    }

    /** Deletes the files row + disk file of a role, except the new one just registered */
    private function removeOld(Model $media, MediaRoleEnum $role, int $newFileId, string $newFilename): void
    {
        $media->files()
            ->wherePivot('role', $role->value)
            ->where('files.id', '!=', $newFileId)
            ->get()
            ->each(function (File $file) use ($newFilename) {
                if ($file->filename !== $newFilename) {
                    $this->disk()->delete("{$file->path}/{$file->filename}");
                }
                $file->forceDelete();
            });
    }

    /** Removes the file of a role (disk, link and row). Without one, it does nothing */
    public function remove(Model $media, MediaRoleEnum $role, ?string $keepFilename = null): void
    {
        $file = $media->files()->wherePivot('role', $role->value)->first();
        if (!$file) return;

        $media->files()->detach($file->id);
        if ($file->filename !== $keepFilename) {          // same name: the new file already replaced it
            $this->disk()->delete("{$file->path}/{$file->filename}");
        }
        $file->forceDelete();
    }

    /** Everything of a title: rows, links and the folder */
    public function deleteAll(Model $media): void
    {
        $ids = $media->files()->pluck('files.id');
        $media->files()->detach();
        File::withTrashed()->whereIn('id', $ids)->forceDelete();
        $this->purge($this->directory($media));
    }

    public function purge(string $directory): void
    {
        $this->disk()->deleteDirectory($directory);
    }

    public function url(File $file): string
    {
        // The hash in the query string busts the browser cache when a file is replaced
        return $this->disk()->url("{$file->path}/{$file->filename}") . '?v=' . substr((string) $file->hash, 0, 8);
    }

    /** role => url */
    public function urls(Model $media): array
    {
        $media->loadMissing('files');

        return $media->files
            ->mapWithKeys(fn (File $file) => [$file->pivot->role => $this->url($file)])
            ->all();
    }
}