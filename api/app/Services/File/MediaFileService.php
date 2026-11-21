<?php

namespace App\Services\File;

use App\Enums\FileVisibilityEnum;
use App\Enums\MediaRoleEnum;
use App\Models\File\File;
use Illuminate\Contracts\Filesystem\Filesystem;
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
    private function disk()
    {
        return Storage::disk('media');
    }

    /** films/12, tv-series/3, episodes/40 */
    public function directory(Model $media): string
    {
        return Str::plural(Str::kebab(class_basename($media))) . '/' . $media->getKey();
    }

    public function storeMany(Model $media, array $uploads): void
    {
        foreach (MediaRoleEnum::cases() as $role) {
            $upload = $uploads[$role->value] ?? null;
            if ($upload instanceof UploadedFile) $this->store($media, $role, $upload);
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
            return DB::transaction(function () use ($media, $role, $directory, $filename, $size, $mime, $hash, $extension) {
                $this->remove($media, $role, keepFilename: $filename);

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

                $media->files()->attach($file->id, ['role' => $role->value]);
                return $file;
            });
        } catch (\Throwable $e) {
            $this->disk()->delete("{$directory}/{$filename}");
            throw $e;
        }
    }

    /** Removes the file of a role (disk, link and row). Without one, it does nothing */
    public function remove(Model $media, MediaRoleEnum $role, ?string $keepFilename = null): void
    {
        $file = $media->files()->wherePivot('role', $role->value)->first();
        if (!$file) return;

        $media->files()->detach($file->id);
        if ($file->filename !== $keepFilename) {          // same name: the new upload already replaced it
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