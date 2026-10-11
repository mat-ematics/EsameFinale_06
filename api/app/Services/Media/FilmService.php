<?php

namespace App\Services\Media;

use App\Http\Resources\Film\FilmCollection;
use App\Http\Resources\Film\FilmResource;
use App\Models\File\File;
use App\Models\Media\Film;
use App\Services\File\FilesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FilmService {
    
    protected FilesService $filesService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }

    /**
     * Ritorna le informazioni associate al Film passato
     */
    public function getFilmInfo(Film $film, bool $isAdmin = false) : FilmResource
    {
        return new FilmResource($film, $isAdmin);
    }

    public function getFilmCollectionInfo(Collection|array $films, bool $isAdmin = false) : array
    {
        return (new FilmCollection($films, $isAdmin))->toArray();
    }

    /**
     * @param Collection<int, UploadedFile>|UploadedFile[]|UplloadedFile $uploadedFiles
     */
    public function createFilm(array $data, UploadedFile|Collection|array $uploadedFiles) : Film
    {
        return DB::transaction(function () use ($data, $uploadedFiles) {
            
            //Creazione Film
            $newFilm = Film::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'length' => $data['length'],
                'directors' => $data['directors'],
                'actors' => $data['actors'],
                'year' => $data['year'],
            ]);

            //Assegnazione Categoria
            $newFilm->setCategories($data['categories']);

            //Salvataggio File e Linkaggio
            $meta = $data['files_meta'];
            $fileModels = $this->filesService->storeOneOrManyFiles($uploadedFiles, $meta, true, $newFilm);

            return $newFilm;
        });
    }

    /**
     * @param Collection<int, File>|File[]|File|null $uploadedFiles
     */
    public function updateFilm(Film $film, array $data, File|Collection|array|null $files = null) : FilmResource
    {
        $data = array_filter($data);

        //Filtra i valori nulli
        $updateData = array_filter(Arr::only($data, [
            'name',
            'length',
            'directors',
            'actors',
            'year',
        ]));

        
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $film->update($updateData);
        }

        //Aggiornamento Categorie
        if (isset($data['categories']) && !empty($data['categories'])) {
            $film->setCategories($data['categories']);
        }
        
        //Aggiornamento File
        if (isset($data['files_meta'])) {
            $meta = array_filter($data['files_meta']);
            $this->filesService->updateOneOrManyFiles($files, $meta);
        }

        return $this->getFilmInfo($film, true);
    }

    /**
     * Elimina Il Film Passato
     */
    public function deleteFilm(Film $film)
    {
        return $film->deleteOrFail();
    }

    /**
     * Recupera un Film Eliminato Temporaneamente (Soft Deleted)
     */
    public function restoreFilm(Film $film)
    {
        $film->restore();
        return $this->getFilmInfo($film, true);
    }

    /**
     * Elimina definitivamente il Film Selezionato (Hard Delete)
     */
    public function forceDeleteFilm(Film $film)
    {
        return $film->forceDelete();
    }
}