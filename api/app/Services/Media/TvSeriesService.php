<?php

namespace App\Services\Media;

use App\Http\Resources\TvSeries\TvSeriesCollection;
use App\Http\Resources\TvSeries\TvSeriesResource;
use App\Models\File\File;
use App\Models\Media\TvSeries;
use App\Services\File\FilesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TvSeriesService {

    protected FilesService $filesService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }

    /**
     * Ritorna le informazioni associate alla Serie TV passata
     */
    public function getTvSeriesInfo(TvSeries $tvSeries, bool $isAdmin = false) : TvSeriesResource
    {
        return new TvSeriesResource($tvSeries, $isAdmin);
    }

    /**
     * Ritorna le informazioni associate alla Collection o Array di Serie TV passata
     */
    public function getTvSeriesCollectionInfo(Collection|array $tvSeries, bool $isAdmin = false) : array
    {
        return (new TvSeriesCollection($tvSeries, $isAdmin))->toArray();
    }

    /**
     * @param Collection<int, UploadedFile>|UploadedFile[]|UplloadedFile $uploadedFiles
     */
    public function createTvSeries(array $data, UploadedFile|Collection|array $uploadedFiles) : TvSeries
    {
        return DB::transaction(function () use ($data, $uploadedFiles) {
            
            //Creazione Serie TV

            $newSeries = TvSeries::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'directors' => $data['directors'],
                'actors' => $data['actors'],
                'start_year' => $data['start_year'],
                'end_year' => $data['end_year'] ?? null,
            ]);

            //Assegnazione Categoria
            $newSeries->setCategories($data['categories']);

            //Salvataggio File e linkaggio
            $meta = $data['files_meta'];
            $fileModels = $this->filesService->storeOneOrManyFiles($uploadedFiles, $meta, true, $newSeries);

            return $newSeries;
        });
    }

    /**
     * @param Collection<int, File>|File[]|File|null $uploadedFiles
     */
    public function updateTvSeries(
        TvSeries $tvSeries,
        array $data,
        File|Collection|array|null $files = null
    ) : TvSeriesResource
    {

        //Filtra i valori nulli
        $updateData = array_filter(Arr::only($data, [
            'name',
            'directors',
            'actors',
            'start_year',
        ]));

        
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description']; // puo' essere null - ignorato se non passato
        }

        if (array_key_exists('end_year', $data)) {
            $updateData['end_year'] = $data['end_year']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $tvSeries->update($updateData);
        }

        //Aggiornamento Categorie
        if (isset($data['categories']) && !empty($data['categories'])) {
            $tvSeries->setCategories($data['categories']);
        }

        //Aggiornamento File
        if (isset($data['files_meta'])) {
            $meta = array_filter($data['files_meta']);
            $this->filesService->updateOneOrManyFiles($files, $meta);
        }

        return $this->getTvSeriesInfo($tvSeries, true);
    }

    /**
     * Elimina la Serie TV Passata
     */
    public function deleteTvSeries(TvSeries $tvSeries)
    {
        return $tvSeries->deleteOrFail();
    }

    /**
     * Recupera una Serie TV Eliminata Temporaneamente (Soft Deleted)
     */
    public function restoreTvSeries(TvSeries $tvSeries)
    {
        $tvSeries->restore();
        return $this->getTvSeriesInfo($tvSeries, true);
    }

    /**
     * Elimina definitivamente la Serie TV Selezionata (Hard Delete)
     */
    public function forceDeleteTvSeries(TvSeries $tvSeries)
    {
        return $tvSeries->forceDelete();
    }
}