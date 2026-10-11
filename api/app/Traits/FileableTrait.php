<?php

namespace App\Traits\Traits;

use App\Enums\FileRoleEnum;
use App\Helpers\AppHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait FileableTrait
{
    public function getFileByLabel(string $label)
    {
        return $this->files()->where('label', $label)->first();
    }

    /**
     * @param string[] $labels
     */
    public function getManyFilesByLabels(array $labels)
    {
        return $this->files()->whereIn('label', $labels)->get();
    }

    public function hasFileWithRole(FileRoleEnum|string $role) : bool
    {
        $role = $role instanceof FileRoleEnum ?: FileRoleEnum::tryFrom($role);

        return $this->files()->wherePivot('role', $role)->exists();
    }

    /**
     * @param Collection<int, FileRoleEnum|string>|FileRoleEnum[]|string[]|FileRoleEnum|string $roles
     */
    public function hasFileWithOneOfRoles(Collection|array|FileRoleEnum|string $roles) : bool
    {
        $roles = AppHelpers::collectToArray($roles);

        //Conversione in Enum e filtraggio valori nulli
        $validRoles = array_filter(array_map(function ($role) {
            return $role instanceof FileRoleEnum
                ? $role->value
                : FileRoleEnum::tryFrom($role)?->value;
        }, $roles));

        return $this->files()->wherePivotIn('role', $validRoles)->exists();
    }

    public function getAllFiles()
    {
        return $this->files;
    }
}
