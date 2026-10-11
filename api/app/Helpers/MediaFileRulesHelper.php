<?php

namespace App\Helpers;

use App\Enums\FileRoleEnum;
use App\Enums\FileVisibilityEnum;
use App\Enums\MediaTypeEnum;
use App\Models\Media\Episode;
use App\Models\Media\Film;
use App\Models\Media\TvSeries;
use App\Rules\UniqueRequiredFileRole;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MediaFileRulesHelper
{
    public static function storeRules(MediaTypeEnum|string $mediaType): array
    {
        $mediaType = $mediaType instanceof MediaTypeEnum ? $mediaType : MediaTypeEnum::from($mediaType);

        $rules = [
            //Validazione File
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:jpg,jpeg,png,gif,mp4|max:51200',

            //Validazione Meta dei File
            'files_meta' => [
                'required',
                'array', 
                'min:1', 
            ],
            'files_meta.*.role' => ['required', Rule::in(array_column(FileRoleEnum::cases(), 'value'))],
            'files_meta.*.visibility' => ['nullable', Rule::in(array_column(FileVisibilityEnum::cases(), 'value'))],
            'files_meta.*.label' => ['required', 'string', 'max:255'],
        ];

        
        switch ($mediaType) {
            case MediaTypeEnum::Film:
                $rules['files_meta'][] = new UniqueRequiredFileRole('cover');
                $rules['files_meta'][] = new UniqueRequiredFileRole('film');
                break;
            case MediaTypeEnum::TvSeries:
                $rules['files_meta'][] = new UniqueRequiredFileRole('cover');
                break;
            case MediaTypeEnum::Episode:
                $rules['files_meta'][] = new UniqueRequiredFileRole('cover');
                $rules['files_meta'][] = new UniqueRequiredFileRole('episode');
                break;
        }

        return $rules;
    }

    public static function checkRoleValidity(Film|TvSeries|Episode $media, array $filesMeta)
    {
        $rolesToCheck = match (get_class($media)) {
            Film::class => [FileRoleEnum::Film->value, FileRoleEnum::Cover->value],
            TvSeries::class => [FileRoleEnum::Cover->value],
            Episode::class => [FileRoleEnum::Episode->value, FileRoleEnum::Cover->value],
        };

        $inputRoles = AppHelpers::extractColumnFromArray($filesMeta, 'role');

        foreach ($rolesToCheck as $checkedRole) {
            if ($media->hasFileWithRole($checkedRole) && in_array($checkedRole, $inputRoles, true)) {
                throw new BadRequestHttpException("The Role $checkedRole is already present for the given File.");
            }
        }
    }
}
