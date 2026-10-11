<?php

namespace App\Listeners;

use App\Events\TvSeriesCountsChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateTvSeriesCounts
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TvSeriesCountsChanged $event): void
    {
        $tvSeries = $event->tvSeries;

        $tvSeries->recalculateCounts();
    }
}
