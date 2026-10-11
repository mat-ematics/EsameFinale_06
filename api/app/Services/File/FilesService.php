<?php

namespace App\Services\File;

use App\Enums\FileRoleEnum;
use App\Enums\FileVisibilityEnum;
use App\Helpers\AppHelpers;
use App\Http\Resources\File\FileCollection;
use App\Http\Resources\File\FileResource;
use App\Models\File\File;
use App\Models\Media\Film;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File as HttpFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\Flysystem\WhitespacePathNormalizer;

class FilesService {

    public function getFileInfo(File $file, bool $isAdmin = false)
    {
        return new FileResource($file, $isAdmin);
    }

    public function getFilesInfo(File $file, bool $isAdmin = false)
    {
        return (new FileCollection($file, $isAdmin))->toArray();
    }

    public function normalizePath(string $path) : string
    {
        return (new WhitespacePathNormalizer)->normalizePath($path);
    }

    /**
     * @param Collection<int>|int[]|int $ids
     * @param Collection<int, string|FileRoleEnum>|string[]|string $roles
     */
    private function prepareAttach(Collection|array|int $ids, Collection|array|string $roles)
    {
        $idValues = AppHelpers::collectToArray($ids);
        $roleValues = AppHelpers::toSingleKeyValueArray('role', AppHelpers::collectToArray($roles));

        // Controllo uguaglianza
        if (count($idValues) !== count($roleValues)) {
            throw new InvalidArgumentException("Number of IDs and roles must match or one role must be used for all IDs.");
        }

        return AppHelpers::keysToValues($idValues, $roleValues);
    }

    /**
     * @param Collection<int>|int[]|int $ids
     * @param Collection<int, string|FileRoleEnum>|string[]|string $roles
     */
    public function attachFiles(Model $model, Collection|array|int $ids, Collection|array|string $roles)
    {
        $attach = $this->prepareAttach($ids, $roles);
        $model->files()->syncWithoutDetaching($attach);
    }

    /**
     * @param Collection<int>|int[]|int $ids
     * @param Collection<int, string|FileRoleEnum>|string[]|string $roles
     */
    public function detachFiles(Model $model, Collection|array|int $ids, Collection|array|string $roles)
    {
        $attach = $this->prepareAttach($ids, $roles);
        $model->files()->detach($attach);
    }

    /**
     * @param Collection<int>|int[]|int $ids
     * @param Collection<int, string|FileRoleEnum>|string[]|string $roles
     */
    public function syncFiles(Model $model, Collection|array|int $ids, Collection|array|string $roles)
    {
        $attach = $this->prepareAttach($ids, $roles);
        $model->files()->sync($attach);
    }

    public function prepareStoreFile(
        HttpFile $file,
        array $meta,
        ?FileRoleEnum $role = null,
        ?FileVisibilityEnum $visibility = null
    ) : array
    {
        if (!$role) {
            $role = FileRoleEnum::tryFrom($meta['role']) ?? FileRoleEnum::default();
        }

        if (!$visibility) {
            $visibility = FileVisibilityEnum::tryFrom($meta['visibility']) ?? FileVisibilityEnum::default();
        }

        $now = now();

        return [
            'filename' => $file->getBasename(),
            'label' => $meta['label'] ?? null,
            'role' => $role->value,
            'visibility' => $visibility->value,
            'path' => $file->getPath(),
            'size' => $file->getSize() ?: null,
            'mime_type' => $file->getMimeType(),
            'extension' => $file->extension(),
            'hash' => AppHelpers::fileHash($file),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function storeFile(
        UploadedFile $uploadedFile,
        array $meta,
        ?Model $model = null
    ) : File
    {
        return DB::transaction(function () use ($uploadedFile, $meta, $model) {
    
            $role = FileRoleEnum::tryFrom($meta['role']) ?? FileRoleEnum::default();
            $visibility = FileVisibilityEnum::tryFrom($meta['visibility']) ?? FileVisibilityEnum::default();

            $storedPath = $uploadedFile->storePublicly();
    
            $file = new HttpFile($storedPath);

            $fileData = $this->prepareStoreFile($file, $meta, $role, $visibility);

            try {
                $fileModel = File::create($fileData);

                if ($model) {
                    $this->attachFiles($model, $fileModel->id, $role->value);
                }

                return $fileModel;
            } catch (\Throwable $th) {
                //Eliminare il file in caso di errore con il Model
                Storage::disk('public')->delete($storedPath);
                throw $th;
            }
        });
    }

    /**
     * @param Collection<int, UploadedFile>|UploadedFile[] $uploadedFiles
     * @return Collection<int, File>
     */
    public function storeManyFiles(
        Collection|array $uploadedFiles,
        array $meta,
        ?Model $model = null
    ) : Collection
    {
        return DB::transaction(function () use ($uploadedFiles, $meta, $model) {

            if (!$uploadedFiles instanceof Collection) {
                $uploadedFiles = collect($uploadedFiles);
            }

            if (!AppHelpers::isArrayOfArrays($meta)) {
                throw new InvalidArgumentException('Meta must be an array of arrays');
            }

            if ($uploadedFiles->count() !== count($meta)) {
                throw new InvalidArgumentException('The number of uploaded files and meta instances must match');
            }

            $storedPaths = [];
            $fileRoles = [];
            $fileData = [];

            try {

                //Ciclo che salva i File e ne cattura le informazioni da inserire a DB
                foreach($meta as $key => $info)
                {
                    $file = $uploadedFiles->get($key);

                    if (!$file) {
                        throw new InvalidArgumentException('Mismatch between files and meta keys');
                    }

                    $role = FileRoleEnum::tryFrom($info['role']) ?? FileRoleEnum::default();
                    $visibility = FileVisibilityEnum::tryFrom($info['visibility']) ?? FileVisibilityEnum::default();
            
                    $path = $file->storePublicly();
                    $storedPaths[] = $path;
            
                    $storedFile = new HttpFile($path);

                    $fileData[] = $this->prepareStoreFile($storedFile, $info, $role, $visibility);
                    $fileRoles[] = $role->value;
                }

                DB::table(File::getTableName())->insert($fileData);

                $fileModels = File::whereIn('path', $storedPaths)->get();

                if ($model) {
                    $ids = $fileModels->pluck('id');
                    $this->attachFiles($model, $ids, $fileRoles);
                }

                return $fileModels;
            } catch (\Throwable $th) {
                //Eliminare il file in caso di errore con il Model
                foreach ($storedPaths as $storedPath) {
                    Storage::disk('public')->delete($storedPath);
                }
                throw $th;
            }
        });
    }

    /**
     * @param Collection<int, UploadedFile>|UploadedFile[]|UploadedFile $uploadedFiles
     * @return File|Collection<int, File>
     */
    public function storeOneOrManyFiles(
        Collection|array|UploadedFile $uploadedFiles,
        array $meta,
        bool $collectResult = false,
        ?Model $model = null
    ) : File|Collection
    {

        if (!$uploadedFiles instanceof UploadedFile && count($uploadedFiles) === 1) {
            $uploadedFiles = $uploadedFiles->first();
        }

        if ($uploadedFiles instanceof UploadedFile) {
            $file = $this->storeFile($uploadedFiles, $meta, $model);
            return $collectResult ? collect([$file]) : $file;
        }

        return $this->storeManyFiles($uploadedFiles, $meta, $model);
    }

    public function renameFile(File $file, string $newName) : File
    {
        $oldPathname = $file->getFullPath();
        $newPathname = $this->normalizePath(File::mergePathFilename($file->path, $newName));
        Storage::move($oldPathname, $newPathname);
        
        try {
            $file->updateOrFail(['filename' => $newName]);
        } catch (\Throwable $th) {
            //Abortire l'operazione in caso di errore con il Model
            Storage::move($newPathname, $oldPathname);
            throw $th;
        }

        return $file;
    }

    public function moveFile(File $file, string $newPath) : File
    {
        $oldPathname = $file->getFullPath();
        $newPathname = $this->normalizePath(File::mergePathFilename($newPath, $file->filename));
        Storage::move($oldPathname, $newPathname);

        try {
            $file->updateOrFail(['path' => $newPath]);
        } catch (\Throwable $th) {
            //Abortire l'operazione in caso di errore con il Model
            Storage::move($newPathname, $oldPathname);
            throw $th;
        }

        return $file;
    }

    public function prepareUpdateFile(
        File $file, 
        array $meta,
        ?FileRoleEnum $role = null,
        ?FileVisibilityEnum $visibility = null
    ) : array
    {

        if (!$role) {
            $role = FileRoleEnum::tryFrom($meta['role']) ?? null;
        }

        if (!$visibility) {
            $visibility = FileVisibilityEnum::tryFrom($meta['visibility']) ?? null;
        }

        $updateData = array_filter([
            'role' => $role,
            'visibility' => $visibility,
        ]);

        if (isset($meta['label'])) {
            $updateData['label'] = $meta['label'] ?? null;
        }

        return $updateData;
    }

    public function updateFile(File $file, array $meta) : File
    {
        return DB::transaction(function () use ($file, $meta) {

            $oldPathname = $file->getFullPath();
            
            $updateData = $this->prepareUpdateFile($file, $meta);

            try {
                $file->updateOrFail($updateData);
                $file->refresh();

                if (isset($updateData['path'])) {
                    Storage::disk('public')->move($oldPathname, $file->getFullPath());
                }
                
    
                return $file;
            } catch (\Throwable $th) {

                if (isset($updateData['path'])) {
                    Storage::move($file->getFullPath(), $oldPathname);
                }

                throw $th;
            }
        });
    }

    /**
     * @param Collection<int, File>|File[] $files
     * @return File|Collection<int, File>
     */
    public function updateManyFiles(Collection|array $files, array $meta) : Collection
    {
        if (!$files instanceof Collection) {
            $files = collect($files);
        }

        if (!AppHelpers::isArrayOfArrays($meta)) {
            throw new InvalidArgumentException('Meta must be an array of arrays');
        }

        if ($files->count() !== count($meta)) {
            throw new InvalidArgumentException('The number of uploaded files and meta instances must match');
        }

        return DB::transaction(function () use ($files, $meta) {

            $paths = [];
            $updatedFiles = collect();
            
            try {

                foreach ($meta as $key => $info) {

                    $file = $files->get($key);

                    if (!$file) {
                        throw new InvalidArgumentException('Mismatch between files and meta keys');
                    }

                    $updateData = $this->prepareUpdateFile($file, $info);

                    $oldPathname = $file->getFullPath();

                    $file->updateOrFail($updateData);
                    $file->refresh();
    
                    if (isset($updateData['path'])) {
                        Storage::disk('public')->move($oldPathname, $file->getFullPath());
                        $paths[$key] = $oldPathname;
                    }

                    $updatedFiles->push($file);
                }

                return $updatedFiles;
            } catch (\Throwable $th) {

                foreach ($files as $key => $file) {

                    if (isset($paths[$key])) {
                        Storage::move($file->getFullPath(), $paths[$key]);
                    }
                }

                throw $th;
            }
        });
    }

    /**
     * @param Collection<int, File>|File[]|File $files
     * @return File|Collection<int, File>
     */
    public function updateOneOrManyFiles(
        Collection|array|File $files,
        array $meta,
        bool $collectResult = false,
    ) : File|Collection
    {

        if (!$files instanceof File && count($files) === 1) {
            $files = $files->first();
        }

        if ($files instanceof File) {
            $updatedFile = $this->updateFile($files, $meta);
            return $collectResult ? collect([$updatedFile]) : $updatedFile;
        }

        return $this->updateManyFiles($files, $meta);
    }

    public function softDeleteFile(File $file)
    {
        $file->delete();
    }

    public function restoreFile(File $file) : File
    {
        $file->restore();
        return $file;
    }

    public function forceDeleteFile(File $file)
    {
        $path = $file->getFullPath();
        
        //Controllo l'esistenza del file
        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        $file->forceDelete();
    }
}