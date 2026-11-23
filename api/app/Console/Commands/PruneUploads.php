<?php

namespace App\Console\Commands;

use App\Services\File\ChunkedUploadService;
use Illuminate\Console\Command;

class PruneUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Deletes abandoned chunked uploads' database records";

    /**
     * Execute the console command.
     */
    public function handle(ChunkedUploadService $uploads): int
    {
        $this->info("Removed {$uploads->prune()} abandoned uploads");
        return self::SUCCESS;
    }
}
