<?php 

namespace App\Services\Media;

use App\Events\TvSeriesCountsChanged;
use App\Http\Resources\Episode\EpisodeCollection;
use App\Http\Resources\Episode\EpisodeResource;
use App\Models\File\File;
use App\Models\Media\Episode;
use App\Models\Media\TvSeries;
use App\Services\File\FilesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EpisodeService {

    protected FilesService $filesService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }

    /**
     * Ritorna le informazioni associate all'Episodio passato
     */
    public function getEpisodeInfo(Episode $episode, bool $isAdmin = false) : EpisodeResource
    {
        return new EpisodeResource($episode, $isAdmin);
    }

    /**
     * Ritorna le informazioni associate alla Collection o Array di Episodi passata
     */
    public function getEpisodeCollectionInfo(Collection|array $episodes, bool $isAdmin = false) : array
    {
        return (new EpisodeCollection($episodes, $isAdmin))->toArray();
    }

    public function isEpisodeTaken(int $tvSeriesId, int $seasonNumber, int $episodeNumber, ?int $excludedId = null) : bool
    {
        $query = Episode::where('tv_series_id', $tvSeriesId)
                    ->where('season_number', $seasonNumber)
                    ->where('episode_number', $episodeNumber);

        if ($excludedId) $query->where('id', '!=', $excludedId);

        return $query->exists();
    }

    /**
     * @param Collection<int, UploadedFile>|UploadedFile[]|UplloadedFile $uploadedFiles
     */
    public function createEpisode(int $tvSeriesId, array $data, UploadedFile|Collection|array $uploadedFiles) : Episode
    {
        return DB::transaction(function () use ($tvSeriesId, $data, $uploadedFiles) {
            
            //Creazione Episodio
            $newEpisode = Episode::create([
                'tv_series_id' => $tvSeriesId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'season_number' => $data['season_number'],
                'episode_number' => $data['episode_number'],
                'length' => $data['length'],
                'year' => $data['year'],
            ]);

            //Salvataggio File e linkaggio
            $meta = $data['files_meta'];
            $fileModels = $this->filesService->storeOneOrManyFiles($uploadedFiles, $meta, true, $newEpisode);

            return $newEpisode;
        });
    }

    /**
     * @param Collection<int, File>|File[]|File|null $uploadedFiles
     */
    public function updateEpisode(
        int $tvSeriesId,
        Episode $episode,
        array $data,
        File|Collection|array|null $files = null
    ) : EpisodeResource
    {
        $data = array_filter($data);

        //Filtra i valori nulli
        $updateData = array_filter(Arr::only($data, [
            'tv_series_id',
            'name',
            'season_number',
            'episode_number',
            'length',
            'year',
        ]));

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $episode->update($updateData);
        }

        if (isset($updateData['tv_series_id']) && $updateData['tv_series_id'] !== $tvSeriesId) {
            event(new TvSeriesCountsChanged(TvSeries::find($tvSeriesId)));
            event(new TvSeriesCountsChanged(TvSeries::find($updateData['tv_series_id'])));
        }

        //Aggiornamento File
        if (isset($data['files_meta'])) {
            $meta = array_filter($data['files_meta']);
            $this->filesService->updateOneOrManyFiles($files, $meta);
        }

        return $this->getEpisodeInfo($episode, true);
    }

    /**
     * Elimina l'Episodio Passato
     */
    public function deleteEpisode(Episode $episode)
    {
        return $episode->deleteOrFail();
    }

        /**
     * Recupera un Episodio Eliminato Temporaneamente (Soft Deleted)
     */
    public function restoreEpisode(episode $episode)
    {
        $episode->restore();
        return $this->getEpisodeInfo($episode, true);
    }

    /**
     * Elimina definitivamente l'Episode Selezionato (Hard Delete)
     */
    public function forceDeleteEpisode(episode $episode)
    {
        return $episode->forceDelete();
    }
}